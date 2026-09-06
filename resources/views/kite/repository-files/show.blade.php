<x-layouts.kite :title="$file->filename">
    <div class="k-head">
        <div>
            <h1 class="k-head__title" style="font-size: var(--display-sm); font-style: italic;">{{ $file->filename }}</h1>
            <p class="k-head__sub">{{ $file->path }}</p>
        </div>
        <div class="k-actions">
            <a href="{{ route('kite.repository-files.index', $repository) }}" class="wr-btn wr-btn--quiet wr-btn--sm">← All files</a>
        </div>
    </div>

    <dl class="k-meta-grid">
        <div><dt>Category</dt><dd>{{ $file->category }}</dd></div>
        <div><dt>Extension</dt><dd>{{ $file->extension ?: '—' }}</dd></div>
        <div><dt>Size</dt><dd>{{ $file->formatted_size }}</dd></div>
        <div><dt>SHA</dt><dd>{{ $file->short_sha }}</dd></div>
    </dl>

    @if ($file->hasDocument())
        <div class="wr-plate">
            <div class="wr-plate__head">
                <span class="wr-label">File Contents</span>
                <span class="wr-plate__bar" aria-hidden="true"></span>
            </div>
            <div class="wr-plate__body">
                <pre style="background: var(--plate-2); border: 1px solid var(--hair); padding: 16px; overflow-x: auto; font-family: var(--font-mono); font-size: 12px; line-height: 1.6; margin: 0;">{{ $file->document->content }}</pre>
            </div>
        </div>
    @else
        <div class="k-empty">
            <p class="k-empty__title">No content stored</p>
            <p class="k-empty__text">Run "Kite Read" on this repository to fetch and store file contents.</p>
        </div>
    @endif
</x-layouts.kite>
