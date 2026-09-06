<x-layouts.kite title="Reading">
    <div class="k-head">
        <div>
            <h1 class="k-head__title">Reading <span class="wr-em">room.</span></h1>
            <p class="k-head__sub">Dive into a repository and build your vocabulary.</p>
        </div>
    </div>

    <x-kite.flash />

    @if ($repositoryToRead)
        <div class="wr-plate k-section">
            <div class="wr-plate__head">
                <span class="wr-label">{{ $repositoryToRead->full_name }}</span>
                <span class="wr-plate__bar" aria-hidden="true"></span>
            </div>
            <div class="wr-plate__body">
                <h2 class="k-section__title">{{ $repositoryToRead->name }}</h2>
                @if ($repositoryToRead->description)
                    <p style="color: var(--muted); font-size: 13px; line-height: 1.7; margin: 4px 0 0;">{{ $repositoryToRead->description }}</p>
                @endif

                <dl class="k-meta-grid" style="margin-top: 14px;">
                    <div><dt>Owner</dt><dd>{{ $repositoryToRead->owner }}</dd></div>
                    <div><dt>Branch</dt><dd>{{ $repositoryToRead->default_branch }}</dd></div>
                    <div><dt>Stars</dt><dd>{{ number_format($repositoryToRead->stargazers_count) }}</dd></div>
                    <div><dt>Forks</dt><dd>{{ number_format($repositoryToRead->forks_count) }}</dd></div>
                    @if ($repositoryToRead->language)
                        <div><dt>Language</dt><dd>{{ $repositoryToRead->language }}</dd></div>
                    @endif
                    <div><dt>Added</dt><dd>{{ $repositoryToRead->created_at->format('M j, Y') }}</dd></div>
                </dl>

                <div class="k-actions">
                    <form method="POST" action="{{ route('kite.repositories-to-read.analyze', $repositoryToRead) }}" data-analyze-form>
                        @csrf
                        <button type="submit" class="wr-btn wr-btn--cta" data-analyze-btn>Start analysis ✶</button>
                    </form>
                    <a href="{{ $repositoryToRead->url }}" target="_blank" rel="noopener" class="wr-btn wr-btn--quiet">View on GitHub ↗</a>
                </div>
            </div>
        </div>

        <x-kite.vocabulary-cloud :terms="$vocabularyTerms" title="Vocabulary" />

        <x-kite.loading />

        @push('scripts')
            <script>
                (function () {
                    var form = document.querySelector('[data-analyze-form]');
                    if (!form) return;
                    form.addEventListener('submit', function () {
                        KiteLoading.show();
                        KiteLoading.updateStatus('Fetching files and extracting vocabulary…');
                        var btn = form.querySelector('[data-analyze-btn]');
                        if (btn) { btn.disabled = true; btn.textContent = 'Analyzing…'; }
                        KiteLoading.simulate(20);
                    });
                })();
            </script>
        @endpush
    @else
        <div class="k-empty">
            <p class="k-empty__title">Ready to read code?</p>
            <p class="k-empty__text">Add a repository to your reading list to start a session.</p>
            <a href="{{ route('kite.index') }}" class="wr-btn wr-btn--cta">Add a repository →</a>
        </div>
    @endif
</x-layouts.kite>
