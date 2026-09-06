<x-layouts.kite title="Kite Reader">
    @auth
        @if (auth()->user()->github_token)
        <div class="k-head">
            <div>
                <h1 class="k-head__title">Read someone's <span class="wr-em">code.</span></h1>
                <p class="k-head__sub">Paste a GitHub repository URL to add it to your reading list.</p>
            </div>
        </div>

        <x-kite.flash />

        <div class="wr-plate" style="max-width: 640px;">
            <div class="wr-plate__head">
                <span class="wr-label">Import a Repository</span>
                <span class="wr-plate__bar" aria-hidden="true"></span>
            </div>
            <div class="wr-plate__body">
                <form method="POST" action="{{ route('kite.repositories.import') }}" data-import-form>
                    @csrf
                    <input type="url" name="repository_url" data-import-input
                        class="k-search" placeholder="https://github.com/user/repo"
                        value="{{ old('repository_url') }}" required>
                    @error('repository_url')
                        <p style="color: var(--pop); font-size: 11px; margin: 8px 0 0;">{{ $message }}</p>
                    @enderror
                    <div style="margin-top: 16px;">
                        <button type="submit" class="wr-btn wr-btn--cta" data-import-btn>Analyze repository →</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script>
                (function () {
                    var form = document.querySelector('[data-import-form]');
                    var input = document.querySelector('[data-import-input]');
                    var btn = document.querySelector('[data-import-btn]');
                    if (!form || !input) return;
                    var pattern = /^https:\/\/github\.com\/[a-zA-Z0-9._-]+\/[a-zA-Z0-9._-]+\/?$/;
                    form.addEventListener('submit', function (e) {
                        if (!pattern.test(input.value.trim())) {
                            e.preventDefault();
                            input.focus();
                            input.style.outline = '2px solid var(--pop)';
                            return;
                        }
                        if (btn) { btn.textContent = 'Analyzing…'; btn.disabled = true; }
                    });
                })();
            </script>
        @endpush
        @else
        {{-- Signed in, but GitHub isn't linked yet — prompt them to connect. --}}
        <div class="k-head">
            <div>
                <h1 class="k-head__title">Connect <span class="wr-em">GitHub.</span></h1>
                <p class="k-head__sub">Link your GitHub account to sync your repositories and start reading code.</p>
            </div>
        </div>

        <x-kite.flash />

        <div class="wr-plate" style="max-width: 640px;">
            <div class="wr-plate__head">
                <span class="wr-label">GitHub Access</span>
                <span class="wr-plate__bar" aria-hidden="true"></span>
            </div>
            <div class="wr-plate__body">
                <p style="color: var(--soft); font-size: 12.5px; line-height: 1.7; margin: 0 0 18px;">
                    Kite reads repository files through your GitHub account. Your access token is stored
                    encrypted and only used to fetch code you point it at.
                </p>
                <a href="{{ route('github.reauth', ['redirect' => route('kite.index')]) }}" class="wr-btn wr-btn--cta">Continue with GitHub →</a>
            </div>
        </div>
        @endif
    @else
        <section class="wr-hero">
            <div class="wr-hero__aurora" aria-hidden="true"></div>
            <div class="wr-hero__inner" style="padding-block: 56px 48px;">
                <p class="wr-entry"><span class="wr-dot" aria-hidden="true"></span>KITE READER — <i>n.</i> READ CODE, BUILD KNOWLEDGE</p>
                <h1 class="wr-display wr-hero__title">Help lightning<br><span class="wr-grad">strike.</span></h1>
                <p class="wr-hero__lede">Point Kite at a GitHub repository to surface the key functions and
                    concepts you might be missing. Read other people's code to build your own vocabulary.</p>
                <div class="wr-stamp wr-stamp--hero">READ · LEARN · SHIP</div>
                <div class="wr-hero__actions">
                    <a href="{{ route('github.redirect') }}" class="wr-btn wr-btn--cta">Continue with GitHub →</a>
                </div>
            </div>
        </section>
    @endauth
</x-layouts.kite>
