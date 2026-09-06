@php
    $talksGiven = [
        ['title' => 'design patterns in laravel', 'when' => 'laracon us ’25', 'url' => 'https://www.youtube.com/watch?v=w7jKcbVcI54'],
        ['title' => 'auth factories in laravel', 'when' => 'laracon au ’25', 'url' => 'https://www.youtube.com/watch?v=KroiagwnV0s'],
        ['title' => 'who watches the watchers?', 'when' => 'bsidesnepa ’25'],
        ['title' => 'the observer pattern', 'when' => 'wire:live ’25', 'url' => 'https://www.youtube.com/watch?v=Rc4Q1D0uWAg'],
        ['title' => 'cleverness is a loan', 'when' => 'laracon us ’26', 'url' => 'https://clever.mary.win'],
        ['title' => 'design patterns in nativephp', 'when' => 'summary + sources', 'url' => route('talks.nativephp-patterns')],
    ];

    // $talksLoved arrives from the route: type=talk rows in the research library.

    $games = [
        [
            'title' => 'four letter words',
            'bar' => 'wgrad-1',
            'text' => 'an off-by-one spelling game. change a letter, make a word, keep the streak. hesitation allowed; repeats aren’t.',
            'url' => route('games.four-letter-words'),
        ],
        [
            'title' => 'license plate game',
            'bar' => 'wgrad-4',
            'text' => 'make words out of whatever plate you see. argue about which acronyms should count. the passenger is always right.',
            'url' => route('games.license-plates'),
        ],
    ];

    $elsewhere = [
        ['label' => 'github', 'note' => '/sifrious', 'url' => 'https://github.com/sifrious'],
        ['label' => 'dev.to', 'note' => 'i got the hat', 'url' => 'https://dev.to/sifrious'],
        ['label' => 'pinkary', 'note' => 'ama', 'url' => 'https://pinkary.com/@sifrious'],
        ['label' => 'twitch', 'note' => 'trying.', 'url' => 'https://www.twitch.tv/sifrious'],
        ['label' => 'email', 'note' => 'hellosifrious@', 'url' => 'mailto:hellosifrious@gmail.com'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>mary.win — wins, in full color</title>
    <meta name="description"
        content="Mary Perry — builder of things. Projects, talks, and small games, filed with the score kept honestly." />

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Two families, no exceptions. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700;1,900&family=IBM+Plex+Mono:ital,wght@0,400;0,500;0,600;1,400&display=swap"
        rel="stylesheet">

    {{-- Restore theme before first paint so it doesn't flash. --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('winrar-theme');
                if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t;
            } catch (e) { }
        })();
    </script>

    @vite(['resources/css/winrar.css'])
</head>

<body>
    <div class="wr">
        {{-- ============ HEADER ============ --}}
        <header class="wr-header">
            <div class="wr-rainbow" aria-hidden="true"></div>
            <div class="wr-header__bar">
                <span class="wr-wordmark">MARY.WIN</span>
                <div class="wr-header__right">
                    <nav class="wr-nav" aria-label="Primary">
                        <a href="#talks">TALKS</a>
                        <a href="#games">GAMES</a>
                        <a href="#me">ME</a>
                    </nav>
                    <button type="button" class="wr-toggle" data-theme-toggle>☾ LIGHTS OFF</button>
                </div>
            </div>
        </header>

        <main>
            {{-- ============ HERO ============ --}}
            <section class="wr-hero">
                <div class="wr-hero__aurora" aria-hidden="true"></div>
                <div class="wr-wrap wr-hero__inner">
                    <p class="wr-entry"><span class="wr-dot" aria-hidden="true"></span>PERRY, MARY — <i>n.</i> BUILDER OF
                        THINGS</p>
                    <h1 class="wr-display wr-hero__title">Wins,<br><span class="wr-grad">in full color.</span></h1>
                    <p class="wr-hero__lede">I write code to solve problems — database-first, full-stack, occasionally
                        for fun. Projects, talks, and small games, filed below with the score kept honestly.</p>
                    {{-- the one stamp on this view --}}
                    <div class="wr-stamp wr-stamp--hero">PERSONAL BEST</div>
                </div>
            </section>

            {{-- ============ TALKS ============ --}}
            <section id="talks" class="wr-wrap wr-section" style="padding-top: 22px;">
                <h2 class="wr-display wr-display--h2">Talks, <span class="wr-em">given &amp; loved.</span></h2>
                <div class="wr-grid-2">
                    <div class="wr-plate">
                        <div class="wr-plate__head"><span class="wr-label">GIVEN — HOME GAMES</span></div>
                        <div class="wr-plate__body">
                            @foreach ($talksGiven as $talk)
                                <div class="wr-row">
                                    @if (isset($talk['url']))
                                        <a href="{{ $talk['url'] }}" class="wr-row__name">{{ $talk['title'] }}</a>
                                    @else
                                        <span>{{ $talk['title'] }}</span>
                                    @endif
                                    <span class="wr-leader" aria-hidden="true"></span>
                                    <span class="wr-row__year">{{ $talk['when'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="wr-plate">
                        <div class="wr-plate__head"><a href="{{ route('talks.loved') }}" class="wr-label">LOVED — SEE ALSO</a></div>
                        <div class="wr-plate__body">
                            @foreach ($talksLoved as $talk)
                                <div class="wr-row">
                                    @if ($talk->url)
                                        <a href="{{ $talk->url }}" class="wr-row__name">{{ Str::lower($talk->title) }}</a>
                                    @else
                                        <span>{{ Str::lower($talk->title) }}</span>
                                    @endif
                                    <span class="wr-leader" aria-hidden="true"></span>
                                    <span class="wr-row__status">{{ Str::lower($talk->author ?? '') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- ============ GAMES ============ --}}
            <section id="games" class="wr-wrap wr-section">
                <h2 class="wr-display wr-display--h2">The <span class="wr-grad">arcade.</span></h2>
                <div class="wr-grid-3">
                    @foreach ($games as $game)
                        <div class="wr-card">
                            <div class="wr-card__bar" style="background: var(--{{ $game['bar'] }});"
                                aria-hidden="true"></div>
                            <div class="wr-card__body">
                                <div class="wr-card__title">{{ $game['title'] }}</div>
                                <p class="wr-card__text">{{ $game['text'] }}</p>
                                <a href="{{ $game['url'] }}" class="wr-btn wr-btn--cta wr-btn--sm wr-card__cta">PRESS START →</a>
                            </div>
                        </div>
                    @endforeach
                    <div class="wr-card wr-card--empty">NEXT CABINET<br>ARRIVING SOON</div>
                </div>
            </section>

            {{-- ============ ME + LINKS ============ --}}
            <section id="me" class="wr-wrap" style="padding-bottom: 80px;">
                <div class="wr-grid-bio">
                    <div>
                        <h2 class="wr-display wr-display--h2" style="margin-bottom: 18px;">Before databases,<br><span
                                class="wr-em">libraries.</span></h2>
                        <p class="wr-bio__text">The stacks got me first — encyclopedias, indexes, reference systems. I
                            wanted to be an academic librarian; the MLS reading list introduced me to databases, and
                            that was that. I’ve been a database-first thinker ever since. Off the clock: cello, ukulele,
                            claymation experiments, and cookbooks I have no intention of cooking from.</p>
                        <div class="wr-stamp wr-stamp--inline">SEE ALSO: EVERYTHING</div>
                    </div>
                    <div class="wr-plate wr-selfstart">
                        <div class="wr-plate__head">
                            <span class="wr-label">ELSEWHERE</span>
                            <span class="wr-plate__bar wr-plate__bar--sm" aria-hidden="true"></span>
                        </div>
                        <div class="wr-plate__body" style="gap: 11px; font-size: 12px;">
                            @foreach ($elsewhere as $link)
                                <div class="wr-row">
                                    <a href="{{ $link['url'] }}" class="wr-row__name">{{ $link['label'] }}</a>
                                    <span class="wr-leader" aria-hidden="true"></span>
                                    <span class="wr-row__status">{{ $link['note'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </main>

        {{-- ============ FOOTER ============ --}}
        <footer class="wr-footer">
            <div class="wr-wrap wr-footer__bar">
                <span>MARY.WIN — SEASON {{ now()->year }}</span>
                <span>FINAL SCORE: MARY 42 · DOUBT 0</span>
            </div>
            <div class="wr-rainbow" aria-hidden="true"></div>
        </footer>
    </div>

    <script>
        (function () {
            var root = document.documentElement;
            var toggle = document.querySelector('[data-theme-toggle]');

            function sync() {
                toggle.textContent = root.dataset.theme === 'dark' ? '☀ LIGHTS ON' : '☾ LIGHTS OFF';
            }

            toggle.addEventListener('click', function () {
                root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem('winrar-theme', root.dataset.theme); } catch (e) { }
                sync();
            });

            sync();
        })();
    </script>
</body>

</html>
