<x-layouts.kite :title="$repository->name . ' — Files'">
    <div class="k-head">
        <div>
            <h1 class="k-head__title">Repo <span class="wr-em">flight.</span></h1>
            <p class="k-head__sub">{{ $repository->full_name }} — stored files</p>
        </div>
        <div class="k-actions">
            <form method="POST" action="{{ route('kite.repositories.kite-read', $repository) }}" data-kite-form="Fetching file contents…" data-kite-files="{{ $repository->relevant_files_count ?? 10 }}">
                @csrf
                <button type="submit" class="wr-btn wr-btn--cta wr-btn--sm">Kite Read ✶</button>
            </form>
            <a href="{{ route('kite.repositories.structure', $repository) }}" class="wr-btn wr-btn--quiet wr-btn--sm">← Structure</a>
        </div>
    </div>

    <x-kite.flash />

    @if ($files->total() > 0)
        <div class="wr-plate">
            <div class="wr-plate__head">
                <span class="wr-label">Stored Files — {{ number_format($files->total()) }}</span>
                <span class="wr-plate__bar" aria-hidden="true"></span>
            </div>
            <div class="wr-plate__body">
                <div style="display: grid; gap: 2px;">
                    @foreach ($files as $i => $file)
                        <div class="k-filerow">
                            <div class="k-filerow__path">
                                <span class="k-w k-w-{{ ($i % 6) + 1 }}" aria-hidden="true">W</span>
                                <a href="{{ route('kite.repository-files.show', [$repository, $file]) }}" class="wr-row__name">{{ $file->path }}</a>
                            </div>
                            <div class="k-filerow__meta">
                                <span class="k-badge">{{ $file->category }}</span>
                                <span>{{ $file->formatted_size }}</span>
                                <span>SHA {{ $file->short_sha }}</span>
                                <span>{{ $file->updated_at?->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($files->hasPages())
            <div class="k-actions" style="justify-content: center; margin-top: 18px;">
                @if ($files->onFirstPage())
                    <span class="wr-btn wr-btn--quiet wr-btn--sm" style="opacity: .4;">← Prev</span>
                @else
                    <a href="{{ $files->previousPageUrl() }}" class="wr-btn wr-btn--quiet wr-btn--sm">← Prev</a>
                @endif
                <span class="wr-meta" style="align-self: center;">Page {{ $files->currentPage() }} of {{ $files->lastPage() }}</span>
                @if ($files->hasMorePages())
                    <a href="{{ $files->nextPageUrl() }}" class="wr-btn wr-btn--quiet wr-btn--sm">Next →</a>
                @else
                    <span class="wr-btn wr-btn--quiet wr-btn--sm" style="opacity: .4;">Next →</span>
                @endif
            </div>
        @endif
    @else
        <div class="k-empty">
            <p class="k-empty__title">No files stored yet</p>
            <p class="k-empty__text">Run "Kite Read" to fetch and store this repository's files.</p>
            <form method="POST" action="{{ route('kite.repositories.kite-read', $repository) }}">
                @csrf
                <button type="submit" class="wr-btn wr-btn--cta">Kite Read ✶</button>
            </form>
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
                        var files = parseInt(form.getAttribute('data-kite-files') || '0', 10);
                        if (files > 0) KiteLoading.simulate(files);
                    });
                });
            });
        </script>
    @endpush
</x-layouts.kite>
