@php
    $terms = collect(auth()->user()->getTopTerms(1000));
    $byLanguage = $terms->groupBy(fn ($t) => $t['language'] ?? 'unknown')
        ->sortByDesc(fn ($group) => $group->count());
@endphp

<x-layouts.kite title="Terms">
    <div class="k-head">
        <div>
            <h1 class="k-head__title">Your <span class="wr-em">vocabulary.</span></h1>
            <p class="k-head__sub">Every function and method extracted from your analyzed code, by language.</p>
        </div>
    </div>

    @if ($terms->isEmpty())
        <div class="k-empty">
            <p class="k-empty__title">No terms yet</p>
            <p class="k-empty__text">Analyze a repository's content ("Kite Read") to start building your vocabulary.</p>
            <a href="{{ route('kite.dashboard') }}" class="wr-btn wr-btn--cta">Go to dashboard →</a>
        </div>
    @else
        @foreach ($byLanguage as $language => $languageTerms)
            <div class="k-section">
                <div class="wr-plate">
                    <div class="wr-plate__head">
                        <span class="wr-label">{{ $language }} — {{ $languageTerms->count() }} terms</span>
                        <span class="wr-plate__bar" aria-hidden="true"></span>
                    </div>
                    <div class="wr-plate__body">
                        <x-kite.term-cloud :terms="$languageTerms->values()->all()" />
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</x-layouts.kite>
