{{--
    Email list signup.

    Deliberately a plain <form> posting to a server route: the whole flow works
    with JavaScript disabled. Styles are scoped and self-contained so the
    component does not depend on any particular CSS build being present on the
    page that embeds it.
--}}
@php
    $status = session('mailing_list');
    $hasErrors = $errors->any();
    $honeypot = config('mailing-list.honeypot_field');
@endphp

@once
    <style>
        .ml-signup {
            /* Foreground/background pairs below are >= 7:1 on white and on the
               dark surface, comfortably past WCAG 2.2 AA for body text. */
            --ml-fg: #1b1b18;
            --ml-muted: #4a4944;
            --ml-bg: #ffffff;
            --ml-border: #6b6a64;
            --ml-accent: #1b1b18;
            --ml-accent-fg: #ffffff;
            --ml-error: #a1160a;
            --ml-success: #0f5132;

            max-width: 34rem;
            color: var(--ml-fg);
            font-size: 1rem;
            line-height: 1.5;
        }

        @media (prefers-color-scheme: dark) {
            .ml-signup {
                --ml-fg: #ededec;
                --ml-muted: #b6b5ae;
                --ml-bg: #0a0a0a;
                --ml-border: #8a8981;
                --ml-accent: #ededec;
                --ml-accent-fg: #0a0a0a;
                --ml-error: #ff8f7d;
                --ml-success: #7ee2a8;
            }
        }

        .ml-signup h2 { font-size: 1.25rem; line-height: 1.3; margin: 0 0 .25rem; }
        .ml-signup p { margin: 0 0 1rem; color: var(--ml-muted); }
        .ml-signup__field { margin-bottom: 1rem; }
        .ml-signup__label { display: block; font-weight: 600; margin-bottom: .25rem; }
        .ml-signup__hint { display: block; font-size: .875rem; color: var(--ml-muted); margin-bottom: .375rem; }

        .ml-signup__input {
            width: 100%;
            padding: .625rem .75rem;
            font: inherit;
            color: var(--ml-fg);
            background: var(--ml-bg);
            border: 1px solid var(--ml-border);
            border-radius: .375rem;
        }

        .ml-signup__input[aria-invalid="true"] {
            border-color: var(--ml-error);
            border-width: 2px;
        }

        /* Focus is always visible and never removed, including for pointer
           users who tab into the field after clicking elsewhere. */
        .ml-signup :is(input, button, a):focus-visible {
            outline: 3px solid var(--ml-accent);
            outline-offset: 2px;
        }

        .ml-signup__consent { display: flex; gap: .5rem; align-items: flex-start; margin-bottom: 1rem; }
        .ml-signup__consent input { margin-top: .3rem; width: 1.1rem; height: 1.1rem; flex: none; }
        .ml-signup__consent label { color: var(--ml-fg); }

        .ml-signup__button {
            font: inherit;
            font-weight: 600;
            padding: .625rem 1.25rem;
            color: var(--ml-accent-fg);
            background: var(--ml-accent);
            border: 1px solid var(--ml-accent);
            border-radius: .375rem;
            cursor: pointer;
            transition: opacity 150ms ease;
        }

        .ml-signup__button:hover { opacity: .85; }

        .ml-signup__message {
            padding: .75rem 1rem;
            margin-bottom: 1rem;
            border-inline-start: 4px solid currentColor;
            border-radius: .25rem;
        }

        .ml-signup__message--error { color: var(--ml-error); }
        .ml-signup__message--ok { color: var(--ml-success); }
        .ml-signup__message p, .ml-signup__message li { color: inherit; margin: 0; }
        .ml-signup__message ul { margin: .5rem 0 0; padding-inline-start: 1.25rem; }
        .ml-signup__message a { color: inherit; }
        .ml-signup__message:focus { outline: 3px solid currentColor; outline-offset: 2px; }

        .ml-signup__error { display: block; margin-top: .375rem; color: var(--ml-error); font-weight: 600; }

        /* The honeypot is removed from the accessibility tree and from the tab
           order, so only a script filling every field will populate it. */
        .ml-signup__trap {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: -1px;
            padding: 0;
            overflow: hidden;
            clip-path: inset(50%);
            white-space: nowrap;
            border: 0;
        }

        @media (prefers-reduced-motion: reduce) {
            .ml-signup * { transition-duration: 0ms !important; animation-duration: 0ms !important; }
        }

        /* In forced-colors mode the browser replaces our palette; keep the
           borders and focus ring drawn with system colors so nothing vanishes. */
        @media (forced-colors: active) {
            .ml-signup__input,
            .ml-signup__button,
            .ml-signup__message { border: 1px solid CanvasText; }
            .ml-signup__button { forced-color-adjust: none; color: ButtonText; background: ButtonFace; }
            .ml-signup :is(input, button, a):focus-visible { outline: 3px solid Highlight; }
        }
    </style>
@endonce

<section class="ml-signup" id="mailing-list" aria-labelledby="mailing-list-heading">
    <h2 id="mailing-list-heading">Email list</h2>
    <p>Occasional notes on what I am building. No more than a few a year.</p>

    @if ($status)
        {{-- role="alert" announces the outcome; tabindex + autofocus moves
             keyboard focus to it without any JavaScript. --}}
        <div
            class="ml-signup__message ml-signup__message--{{ $status['state'] === 'temporary_failure' ? 'error' : 'ok' }}"
            role="alert"
            tabindex="-1"
            autofocus
        >
            <p>{{ $status['message'] }}</p>
        </div>
    @endif

    @if ($hasErrors)
        <div class="ml-signup__message ml-signup__message--error" role="alert" tabindex="-1" autofocus>
            <p><strong>{{ trans_choice('There is :count problem with your submission.|There are :count problems with your submission.', $errors->count(), ['count' => $errors->count()]) }}</strong></p>
            <ul>
                @foreach ($errors->keys() as $field)
                    <li><a href="#mailing-list-{{ $field }}">{{ $errors->first($field) }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('mailing-list.store') }}" novalidate>
        @csrf

        <input type="hidden" name="form_rendered_at" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">

        <div class="ml-signup__trap" aria-hidden="true">
            <label for="mailing-list-{{ $honeypot }}">Leave this field blank</label>
            <input
                type="text"
                id="mailing-list-{{ $honeypot }}"
                name="{{ $honeypot }}"
                value=""
                tabindex="-1"
                autocomplete="off"
            >
        </div>

        <div class="ml-signup__field">
            <label class="ml-signup__label" for="mailing-list-email">Email address</label>
            <span class="ml-signup__hint" id="mailing-list-email-hint">
                We send a confirmation link first — you are not subscribed until you open it.
            </span>
            <input
                class="ml-signup__input"
                type="email"
                id="mailing-list-email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                spellcheck="false"
                required
                aria-describedby="mailing-list-email-hint @error('email') mailing-list-email-error @enderror"
                @error('email') aria-invalid="true" @enderror
            >
            @error('email')
                <span class="ml-signup__error" id="mailing-list-email-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="ml-signup__consent">
            <input
                type="checkbox"
                id="mailing-list-consent"
                name="consent"
                value="1"
                @checked(old('consent'))
                aria-describedby="@error('consent') mailing-list-consent-error @enderror"
                @error('consent') aria-invalid="true" @enderror
            >
            <label for="mailing-list-consent">
                {{ config('mailing-list.consent_text') }}
                <a href="{{ config('mailing-list.privacy_url') }}">Privacy notice</a>.
                @error('consent')
                    <span class="ml-signup__error" id="mailing-list-consent-error">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <button class="ml-signup__button" type="submit">Subscribe</button>
    </form>
</section>
