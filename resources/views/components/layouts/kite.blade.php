@props(['title' => 'Kite Reader'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $title }} — Kite Reader</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Two families, no exceptions (matching the winrar landing). --}}
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

    @vite(['resources/css/winrar.css', 'resources/css/kite.css'])
    @stack('styles')
</head>

<body>
    <div class="wr">
        <header class="wr-header">
            <div class="wr-rainbow" aria-hidden="true"></div>
            <div class="wr-header__bar">
                <a href="{{ route('kite.index') }}" class="wr-wordmark" style="text-decoration:none;">KITE ✶ READER</a>
                <div class="wr-header__right">
                    @auth
                        <nav class="wr-nav" aria-label="Primary">
                            <a href="{{ route('kite.dashboard') }}" class="{{ request()->routeIs('kite.dashboard') ? 'is-active' : '' }}">DASHBOARD</a>
                            <a href="{{ route('kite.code') }}" class="{{ request()->routeIs('kite.code') ? 'is-active' : '' }}">MY CODE</a>
                            <a href="{{ route('kite.terms.index') }}" class="{{ request()->routeIs('kite.terms.index') ? 'is-active' : '' }}">TERMS</a>
                            <a href="{{ route('kite.reading-list') }}" class="{{ request()->routeIs('kite.reading-list', 'kite.reading') ? 'is-active' : '' }}">READING</a>
                        </nav>
                        <div class="k-userbar">
                            <span class="k-userbar__name">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="k-logout">Log out</button>
                            </form>
                        </div>
                    @endauth
                    <button type="button" class="wr-toggle" data-theme-toggle>☾ LIGHTS OFF</button>
                </div>
            </div>
        </header>

        <main>
            <div class="wr-wrap" style="padding-block: 40px 24px;">
                {{ $slot }}
            </div>
        </main>

        <footer class="wr-footer">
            <div class="wr-wrap wr-footer__bar">
                <span>KITE READER — READ CODE, BUILD KNOWLEDGE</span>
                <span>HELP LIGHTNING STRIKE ✶</span>
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

    @stack('scripts')
</body>

</html>
