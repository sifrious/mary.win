@props(['terms' => []])

{{-- Word cloud for the user's OWN top terms (User::getTopTerms output:
     function_name, language, framework, category, total_frequency). --}}
<div class="k-cloud">
    @forelse ($terms as $index => $term)
        @php
            $lang = $term['language'] ?? 'unknown';
            $priority = $index < 10 ? 'high' : ($index < 30 ? 'medium' : 'low');
        @endphp
        <span class="k-term k-term--{{ $priority }}" data-language="{{ $lang }}"
            title="{{ $term['function_name'] }} · used {{ $term['total_frequency'] ?? 0 }}×{{ !empty($term['framework']) ? ' · ' . ucfirst($term['framework']) : '' }}{{ !empty($term['category']) ? ' · ' . ucfirst($term['category']) : '' }}">
            <span class="k-term__name">{{ $term['function_name'] }}</span>
            <span class="k-term__meta">
                <span class="k-term__count">{{ $term['total_frequency'] ?? 0 }}</span>
                @if ($lang && $lang !== 'unknown')
                    <span class="k-term__lang">{{ $lang }}</span>
                @endif
            </span>
        </span>
    @empty
        <p style="color: var(--muted); font-size: 12.5px; margin: 0;">
            No terms yet. Analyze a repository's content ("Kite Read") to build your vocabulary.
        </p>
    @endforelse
</div>
