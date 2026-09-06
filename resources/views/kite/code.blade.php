@php
    $user = auth()->user();
    $activeRepos = $user->repositories()->where('is_active', true)->latest()->get();
    $publicCount = $activeRepos->where('private', false)->count();
    $privateCount = $activeRepos->where('private', true)->count();
@endphp

<x-layouts.kite title="My Code">
    <div class="k-head">
        <div>
            <h1 class="k-head__title">My <span class="wr-em">code.</span></h1>
            <p class="k-head__sub">The repositories you've activated for analysis.</p>
        </div>
        <div class="k-actions">
            <a href="{{ route('kite.repositories.select') }}" class="wr-btn wr-btn--quiet wr-btn--sm">Manage selection</a>
            <form method="POST" action="{{ route('kite.repositories.sync') }}">
                @csrf
                <button type="submit" class="wr-btn wr-btn--cta wr-btn--sm">Sync new repos ↻</button>
            </form>
        </div>
    </div>

    <x-kite.flash />

    @if ($activeRepos->isNotEmpty())
        <div class="k-grid k-section">
            @foreach ($activeRepos as $repo)
                <div class="k-repo">
                    <div class="k-repo__bar" aria-hidden="true"></div>
                    <div class="k-repo__body">
                        <div class="k-repo__head">
                            <h3 class="k-repo__name">{{ $repo->name }}</h3>
                            <span class="k-badge {{ $repo->private ? 'k-badge--private' : 'k-badge--public' }}">
                                {{ $repo->private ? 'Private' : 'Public' }}
                            </span>
                        </div>
                        @if ($repo->description)
                            <p class="k-repo__desc">{{ Str::limit($repo->description, 120) }}</p>
                        @endif
                        <div class="k-repo__meta"><span>⑂ {{ $repo->default_branch }}</span></div>
                        <div class="k-repo__foot">
                            <a href="{{ route('kite.repositories.structure', $repo) }}" class="wr-btn wr-btn--cta wr-btn--sm">Structure</a>
                            <a href="{{ $repo->url }}" target="_blank" rel="noopener" class="wr-btn wr-btn--quiet wr-btn--sm">GitHub ↗</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="k-empty">
            <p class="k-empty__title">No active repositories</p>
            <p class="k-empty__text">Choose which of your GitHub repositories to include in Kite Reader.</p>
            <a href="{{ route('kite.repositories.fetch-and-select') }}" class="wr-btn wr-btn--cta">Fetch &amp; select repositories →</a>
        </div>
    @endif

    <div class="k-stats">
        <div class="k-stat"><p class="k-stat__label">Active repos</p><p class="k-stat__value">{{ $activeRepos->count() }}</p></div>
        <div class="k-stat"><p class="k-stat__label">Public</p><p class="k-stat__value">{{ $publicCount }}</p></div>
        <div class="k-stat"><p class="k-stat__label">Private</p><p class="k-stat__value">{{ $privateCount }}</p></div>
    </div>
</x-layouts.kite>
