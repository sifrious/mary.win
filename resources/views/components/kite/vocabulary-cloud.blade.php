@props(['terms' => [], 'title' => 'Vocabulary'])

@php
    $terms = $terms ?? [];
    $newCount = collect($terms)->where('user_frequency', 0)->count();
    $familiarCount = collect($terms)->where('user_frequency', '>', 0)->count();
    $langCount = collect($terms)->pluck('language')->unique()->count();
@endphp

{{-- Word cloud for FOREIGN-repo vocabulary: new terms (never used in your own
     code) first, then familiar ones. Data: function_name, language, framework,
     category, article_frequency, user_frequency. --}}
<div class="k-section" data-vocab-cloud>
    <div class="wr-plate">
        <div class="wr-plate__head">
            <span class="wr-label">{{ $title }} — {{ count($terms) }} terms</span>
            <span class="wr-plate__bar" aria-hidden="true"></span>
        </div>
        <div class="wr-plate__body">
            @if (count($terms) > 20)
                <div class="k-cloud-controls">
                    <input type="text" class="k-search" data-term-search placeholder="Search terms…">
                    <div class="k-filterbtns">
                        <button type="button" class="k-filter is-active" data-filter="all">All</button>
                        <button type="button" class="k-filter" data-filter="new">New</button>
                        <button type="button" class="k-filter" data-filter="familiar">Familiar</button>
                    </div>
                </div>
            @endif

            @if (count($terms) > 0)
                <div class="k-cloud" data-cloud>
                    @foreach ($terms as $index => $term)
                        @php
                            $isNew = ($term['user_frequency'] ?? 0) == 0;
                            $lang = $term['language'] ?? 'unknown';
                            $priority = $index < 10 ? 'high' : ($index < 25 ? 'medium' : 'low');
                        @endphp
                        <span class="k-term k-term--{{ $priority }} {{ $isNew ? 'k-term--new' : '' }}"
                            data-term="{{ strtolower($term['function_name']) }}"
                            data-category="{{ $isNew ? 'new' : 'familiar' }}"
                            data-language="{{ $lang }}"
                            title="{{ $isNew ? 'NEW' : 'Familiar' }} · {{ $term['article_frequency'] ?? 0 }}× here{{ $isNew ? '' : ' · you: ' . ($term['user_frequency'] ?? 0) . '×' }}{{ !empty($term['framework']) ? ' · ' . ucfirst($term['framework']) : '' }}{{ !empty($term['category']) ? ' · ' . ucfirst($term['category']) : '' }}">
                            @if ($isNew)
                                <span class="k-term__new">NEW</span>
                            @endif
                            <span class="k-term__name">{{ $term['function_name'] }}</span>
                            <span class="k-term__meta">
                                <span class="k-term__count">{{ $term['article_frequency'] ?? 0 }}</span>
                                @if (!$isNew)
                                    <span class="k-term__count" style="opacity:.6">{{ $term['user_frequency'] ?? 0 }}</span>
                                @endif
                                @if ($lang && $lang !== 'unknown')
                                    <span class="k-term__lang">{{ $lang }}</span>
                                @endif
                            </span>
                        </span>
                    @endforeach
                </div>

                <div class="k-legend">
                    <span><span class="k-legend__dot">NEW</span> Terms you've never used in your own code</span>
                    <span>Familiar terms follow, ordered by how little you've used them</span>
                </div>

                <div class="k-stats" style="margin-top:18px;margin-bottom:0;">
                    <div class="k-stat"><p class="k-stat__label">New Terms</p><p class="k-stat__value">{{ $newCount }}</p></div>
                    <div class="k-stat"><p class="k-stat__label">Familiar</p><p class="k-stat__value">{{ $familiarCount }}</p></div>
                    <div class="k-stat"><p class="k-stat__label">Languages</p><p class="k-stat__value">{{ $langCount }}</p></div>
                </div>
            @else
                <p style="text-align:center;color:var(--muted);font-size:12.5px;padding:20px 0;margin:0;">
                    No vocabulary yet. Click "Start Analysis" to extract learning terms from this repository.
                </p>
            @endif
        </div>
    </div>
</div>

<script>
    (function () {
        var root = document.currentScript.previousElementSibling;
        while (root && !root.matches('[data-vocab-cloud]')) root = root.previousElementSibling;
        if (!root) return;

        var search = root.querySelector('[data-term-search]');
        var filters = root.querySelectorAll('.k-filter');
        var terms = root.querySelectorAll('.k-term');
        var current = 'all';

        function apply() {
            var q = search ? search.value.toLowerCase() : '';
            terms.forEach(function (t) {
                var matchQ = !q || t.getAttribute('data-term').includes(q);
                var matchF = current === 'all' || t.getAttribute('data-category') === current;
                t.style.display = (matchQ && matchF) ? 'inline-flex' : 'none';
            });
        }

        if (search) search.addEventListener('input', apply);
        filters.forEach(function (btn) {
            btn.addEventListener('click', function () {
                filters.forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                current = btn.getAttribute('data-filter');
                apply();
            });
        });
    })();
</script>
