<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Talks, loved — mary.win</title>
    <meta name="description"
        content="The full list of talks Mary Perry keeps pointing people at — the home page deals out three at random; this is the whole deck." />

    <link rel="canonical" href="{{ route('talks.loved') }}" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="mary.win" />
    <meta property="og:title" content="Talks, loved — mary.win" />
    <meta property="og:description"
        content="The full list of talks Mary Perry keeps pointing people at — the home page deals out three at random; this is the whole deck." />
    <meta property="og:url" content="{{ route('talks.loved') }}" />
    <meta name="twitter:card" content="summary" />

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
                <a href="{{ route('home') }}" class="wr-wordmark" style="text-decoration:none;">MARY.WIN</a>
                <div class="wr-header__right">
                    <nav class="wr-nav" aria-label="Primary">
                        <a href="{{ route('home') }}#talks">TALKS</a>
                        <a href="{{ route('home') }}#games">GAMES</a>
                        <a href="{{ route('home') }}#me">ME</a>
                    </nav>
                    <button type="button" class="wr-toggle" data-theme-toggle>☾ LIGHTS OFF</button>
                </div>
            </div>
        </header>

        <main>
            {{-- ============ TITLE ============ --}}
            <section class="wr-hero">
                <div class="wr-hero__aurora" aria-hidden="true"></div>
                <div class="wr-wrap wr-hero__inner">
                    <p class="wr-entry"><span class="wr-dot" aria-hidden="true"></span>INDEX — SEE ALSO</p>
                    <h1 class="wr-display wr-hero__title">Talks,<br><span class="wr-grad">loved.</span></h1>
                    <p class="wr-hero__lede">Other people’s talks I keep pointing people at. The front page deals out
                        three at random; this is the whole deck, newest first.</p>
                </div>
            </section>

            {{-- ============ THE LIST ============ --}}
            <section class="wr-wrap wr-section" style="padding-top: 22px; padding-bottom: 80px;">
                <div class="wr-plate">
                    <div class="wr-plate__head">
                        <span class="wr-label">LOVED — THE WHOLE LIST</span>
                        <span class="wr-plate__bar" aria-hidden="true"></span>
                    </div>
                    <div class="wr-plate__body">
                        @forelse ($talksLoved as $talk)
                            <div class="wr-row">
                                @if ($talk->url)
                                    <a href="{{ $talk->url }}" rel="noopener"
                                        class="wr-row__name">{{ Str::lower($talk->title) }}</a>
                                @else
                                    <span>{{ Str::lower($talk->title) }}</span>
                                @endif
                                <span class="wr-leader" aria-hidden="true"></span>
                                <span class="wr-row__status">{{ Str::lower($talk->author ?? '') }}{{ $talk->date_published ? ' · ’'.$talk->date_published->format('y') : '' }}</span>
                            </div>
                        @empty
                            <div class="wr-row"><span>the library is out on loan — check back.</span></div>
                        @endforelse
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
