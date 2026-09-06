@php
    $user = auth()->user();
    $repositoriesToRead = $user->repositoriesToRead()->orderBy('created_at', 'desc')->get();
@endphp

<x-layouts.kite title="Reading List">
    <div class="k-head">
        <div>
            <h1 class="k-head__title">Reading <span class="wr-em">list.</span></h1>
            <p class="k-head__sub">Repositories you've curated to explore and learn from.</p>
        </div>
        <div class="k-actions">
            <a href="{{ route('kite.index') }}" class="wr-btn wr-btn--cta wr-btn--sm">Add repository +</a>
        </div>
    </div>

    <x-kite.flash />

    @if ($repositoriesToRead->isEmpty())
        <div class="k-empty">
            <p class="k-empty__title">No repositories yet</p>
            <p class="k-empty__text">Add an open-source project to your reading list and start building
                vocabulary from other people's code.</p>
            <a href="{{ route('kite.index') }}" class="wr-btn wr-btn--cta">Add a repository →</a>
        </div>
    @else
        <div class="k-grid">
            @foreach ($repositoriesToRead as $repo)
                @php $isCurrent = $user->last_read_repository_to_read_id == $repo->id; @endphp
                <div class="k-repo {{ $isCurrent ? 'k-repo--current' : '' }}">
                    <div class="k-repo__bar" aria-hidden="true"></div>
                    <div class="k-repo__body">
                        <div class="k-repo__head">
                            <div>
                                <h3 class="k-repo__name">{{ $repo->name }}</h3>
                                <p class="k-repo__owner">{{ $repo->owner }}</p>
                            </div>
                            @if ($repo->language)
                                <span class="k-badge">{{ $repo->language }}</span>
                            @endif
                        </div>
                        @if ($repo->description)
                            <p class="k-repo__desc">{{ Str::limit($repo->description, 110) }}</p>
                        @endif
                        <div class="k-repo__meta">
                            <span>★ {{ number_format($repo->stargazers_count) }}</span>
                            <span>Added {{ $repo->created_at->diffForHumans() }}</span>
                            @if ($isCurrent)
                                <span class="k-badge k-badge--current" style="margin-left:auto;">Reading</span>
                            @endif
                        </div>
                        <div class="k-repo__foot">
                            <a href="{{ route('kite.reading', ['repository' => $repo->id]) }}" class="wr-btn wr-btn--cta wr-btn--sm">Start reading →</a>
                            <a href="{{ $repo->url }}" target="_blank" rel="noopener" class="wr-btn wr-btn--quiet wr-btn--sm">GitHub ↗</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.kite>
