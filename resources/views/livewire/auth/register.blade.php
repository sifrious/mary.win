<div class="wra">
    <div class="wra__head">
        <p class="wr-entry"><span class="wr-dot" aria-hidden="true"></span>NEW PLAYER</p>
        <h1 class="wra__title">Create an <span class="wr-em">account.</span></h1>
        <p class="wra__lede">{{ __('Enter your details below to create your account.') }}</p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="wr-status wra-status">{{ session('status') }}</div>
    @endif

    <div class="wr-plate">
        <div class="wr-plate__head">
            <span class="wr-label">Your Details</span>
            <span class="wr-plate__bar" aria-hidden="true"></span>
        </div>
        <div class="wr-plate__body">
            <form wire:submit="register" class="wra-form">
                <!-- Name -->
                <div class="wr-field">
                    <label class="wr-field__label" for="name">{{ __('Name') }}</label>
                    <input id="name" wire:model="name" type="text" class="wr-input" required autofocus
                        autocomplete="name" placeholder="{{ __('Full name') }}">
                    @error('name') <p class="wr-error">{{ $message }}</p> @enderror
                </div>

                <!-- Email Address -->
                <div class="wr-field">
                    <label class="wr-field__label" for="email">{{ __('Email address') }}</label>
                    <input id="email" wire:model="email" type="email" class="wr-input" required
                        autocomplete="email" placeholder="email@example.com">
                    @error('email') <p class="wr-error">{{ $message }}</p> @enderror
                </div>

                <!-- Password -->
                <div class="wr-field">
                    <label class="wr-field__label" for="password">{{ __('Password') }}</label>
                    <div class="wr-input-wrap" x-data="{ show: false }">
                        <input id="password" wire:model="password" :type="show ? 'text' : 'password'" type="password"
                            class="wr-input" required autocomplete="new-password" placeholder="{{ __('Password') }}">
                        <button type="button" class="wr-reveal" @click="show = !show"
                            x-text="show ? '{{ __('Hide') }}' : '{{ __('Show') }}'">{{ __('Show') }}</button>
                    </div>
                    @error('password') <p class="wr-error">{{ $message }}</p> @enderror
                </div>

                <!-- Confirm Password -->
                <div class="wr-field">
                    <label class="wr-field__label" for="password_confirmation">{{ __('Confirm password') }}</label>
                    <input id="password_confirmation" wire:model="password_confirmation" type="password"
                        class="wr-input" required autocomplete="new-password"
                        placeholder="{{ __('Confirm password') }}">
                    @error('password_confirmation') <p class="wr-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="wr-btn wr-btn--cta wr-btn--block">{{ __('Create account') }} →</button>
            </form>
        </div>
    </div>

    <div class="wra-or">{{ __('or') }}</div>

    <a href="{{ route('github.redirect') }}" class="wr-btn wr-btn--quiet wra-social">
        <svg viewBox="0 0 16 16" aria-hidden="true"><path fill-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path></svg>
        {{ __('Sign up with GitHub') }}
    </a>

    <p class="wra__foot">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" wire:navigate>{{ __('Log in') }}</a>
    </p>
</div>
