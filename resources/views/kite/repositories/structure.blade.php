@php
    $categories = [
        'readme' => 'README Files',
        'docs' => 'Documentation',
        'config' => 'Configuration',
        'code' => 'Code Files',
        'other' => 'Other Files',
    ];
    $groupedFiles = collect($fileStructure)->groupBy('category');
@endphp

<x-layouts.kite :title="$repository->name">
    <div class="k-head">
        <div>
            <h1 class="k-head__title">{{ $repository->name }}</h1>
            <p class="k-head__sub">{{ $repository->full_name }}</p>
        </div>
        <div class="k-actions">
            @if ($repository->isAnalyzed())
                <form method="POST" action="{{ route('kite.repositories.analyze', $repository) }}" data-kite-form="Analyzing repository structure…">
                    @csrf
                    <button type="submit" class="wr-btn wr-btn--quiet wr-btn--sm">Re-analyze ↻</button>
                </form>
                @if (! $repository->files_stored)
                    <form method="POST" action="{{ route('kite.repositories.analyze-terms', $repository) }}" data-kite-form="Analyzing file content and extracting terms…">
                        @csrf
                        <button type="submit" class="wr-btn wr-btn--cta wr-btn--sm">Analyze content</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('kite.repositories.kite-read', $repository) }}" data-kite-form="Fetching file contents…" data-kite-files="{{ $repository->relevant_files_count ?? 10 }}">
                    @csrf
                    <button type="submit" class="wr-btn wr-btn--cta wr-btn--sm">Kite Read ✶</button>
                </form>
                <a href="{{ route('kite.repository-files.index', $repository) }}" class="wr-btn wr-btn--quiet wr-btn--sm">Repo Flight ✶</a>
            @else
                <form method="POST" action="{{ route('kite.repositories.analyze', $repository) }}" data-kite-form="Analyzing repository structure…">
                    @csrf
                    <button type="submit" class="wr-btn wr-btn--quiet wr-btn--sm">Analyze structure</button>
                </form>
                <form method="POST" action="{{ route('kite.repositories.full-analysis', $repository) }}" data-kite-form="Running full repository analysis…" data-kite-files="50">
                    @csrf
                    <button type="submit" class="wr-btn wr-btn--cta wr-btn--sm">Full analysis ⚡</button>
                </form>
            @endif
            <a href="{{ $repository->url }}" target="_blank" rel="noopener" class="wr-btn wr-btn--quiet wr-btn--sm">GitHub ↗</a>
        </div>
    </div>

    <x-kite.flash />

    @if ($repository->isAnalyzed())
        <div class="k-stats">
            <div class="k-stat"><p class="k-stat__label">Total Files</p><p class="k-stat__value">{{ number_format($repository->total_files_count) }}</p></div>
            <div class="k-stat"><p class="k-stat__label">Relevant Files</p><p class="k-stat__value">{{ number_format($repository->relevant_files_count) }}</p></div>
            <div class="k-stat"><p class="k-stat__label">Last Analyzed</p><p class="k-stat__value k-stat__value--sm">{{ $repository->file_structure_updated_at?->diffForHumans() ?? 'Never' }}</p></div>
            <div class="k-stat"><p class="k-stat__label">Branch</p><p class="k-stat__value k-stat__value--sm">{{ $repository->default_branch }}</p></div>
        </div>

        <x-kite.analysis-result :result="session('analysis')" />

        @if (count($fileStructure) > 0)
            <div class="k-grid" style="grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));">
                @foreach ($categories as $categoryKey => $categoryTitle)
                    @if ($groupedFiles->has($categoryKey))
                        <div class="wr-plate">
                            <div class="wr-plate__head">
                                <span class="wr-label">{{ $categoryTitle }} — {{ count($groupedFiles[$categoryKey]) }}</span>
                                <span class="wr-plate__bar wr-plate__bar--sm" aria-hidden="true"></span>
                            </div>
                            <div class="wr-plate__body">
                                <div class="k-list">
                                    @foreach ($groupedFiles[$categoryKey] as $i => $file)
                                        <div class="k-filerow">
                                            <div class="k-filerow__path">
                                                <span class="k-w k-w-{{ ($i % 6) + 1 }}" aria-hidden="true">W</span>{{ $file['path'] }}
                                            </div>
                                            <div class="k-filerow__meta">
                                                <span>{{ number_format($file['size']) }} bytes</span>
                                                <span>SHA {{ substr($file['sha'], 0, 7) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="k-empty">
                <p class="k-empty__title">No relevant files found</p>
                <p class="k-empty__text">This repository doesn't contain files we consider relevant for reading or analysis.</p>
            </div>
        @endif

        @if (isset($vocabularyTerms) && count($vocabularyTerms) > 0)
            <x-kite.vocabulary-cloud :terms="$vocabularyTerms" title="Repository vocabulary" />
        @endif
    @else
        <div class="k-empty">
            <p class="k-empty__title">Repository not analyzed</p>
            <p class="k-empty__text">Click "Analyze structure" to discover the file tree and find relevant files for reading.</p>
        </div>
    @endif

    <x-kite.loading />

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-kite-form]').forEach(function (form) {
                    form.addEventListener('submit', function () {
                        KiteLoading.show();
                        KiteLoading.updateStatus(form.getAttribute('data-kite-form'));
                        var btn = form.querySelector('button[type="submit"]');
                        if (btn) { btn.disabled = true; btn.textContent = 'Working…'; }
                        var files = parseInt(form.getAttribute('data-kite-files') || '0', 10);
                        if (files > 0) KiteLoading.simulate(files);
                    });
                });
                @if (session('analysis'))
                    var ai = document.getElementById('ai-analysis');
                    if (ai) setTimeout(function () { ai.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 400);
                @endif
            });
        </script>
    @endpush
</x-layouts.kite>
