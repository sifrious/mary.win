@props(['title' => 'Account'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $title }} — mary.win</title>

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

    @vite(['resources/css/winrar.css'])
    @livewireStyles

    {{-- Logged-in shell + settings-panel composition (form primitives live in winrar.css). --}}
    <style>
        [x-cloak] {
            display: none !important;
        }

        .wr-app-shell {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .wr-app-main {
            flex: 1;
        }

        /* header user bar */
        .wr-userbar {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .wr-userbar__name {
            font-family: var(--font-mono);
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .wr-userbar form {
            margin: 0;
            display: inline;
        }

        .wr-logout {
            background: transparent;
            border: 0;
            padding: 0;
            cursor: pointer;
            font-family: var(--font-mono);
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--accent);
        }

        .wr-logout:hover {
            text-decoration: underline;
        }

        /* page heading */
        .wrs-heading {
            margin-bottom: 26px;
        }

        .wrs-heading__title {
            font-family: var(--font-display);
            font-weight: 900;
            font-size: var(--display-md);
            line-height: 1;
            color: var(--ink);
            margin: 0 0 6px;
        }

        .wrs-heading__sub {
            color: var(--muted);
            font-size: var(--text-caption-size);
            letter-spacing: 0.04em;
            margin: 0;
        }

        /* two-column: nav plate + content plate */
        .wrs {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 22px;
            align-items: start;
        }

        .wrs__navlist {
            display: flex;
            flex-direction: column;
        }

        .wrs__navitem {
            display: block;
            padding: 11px 16px;
            font-family: var(--font-mono);
            font-size: 12px;
            letter-spacing: 0.04em;
            color: var(--soft);
            border-bottom: 1px dotted var(--hair);
        }

        .wrs__navitem:last-child {
            border-bottom: 0;
        }

        .wrs__navitem:hover {
            color: var(--accent);
            text-decoration: none;
        }

        .wrs__navitem.is-active {
            color: var(--cta-fg);
            background: var(--accent);
        }

        .wrs__sub {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
            margin: 0 0 18px;
        }

        .wrs-form {
            display: grid;
            gap: 18px;
        }

        .wrs-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .wrs-sep {
            border: 0;
            border-top: 1px solid var(--hair);
            margin: 28px 0 0;
        }

        /* segmented control (appearance) */
        .wr-seg {
            display: inline-flex;
            border: 1px solid var(--line);
        }

        .wr-seg button {
            padding: 9px 18px;
            font-family: var(--font-mono);
            font-size: 11px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            background: transparent;
            color: var(--muted);
            border: 0;
            border-right: 1px solid var(--line);
            cursor: pointer;
        }

        .wr-seg button:last-child {
            border-right: 0;
        }

        .wr-seg button.is-active {
            background: var(--accent);
            color: var(--cta-fg);
        }

        /* danger zone */
        .wrs-danger {
            border-left: 4px solid var(--pop);
        }

        @media (max-width: 700px) {
            .wrs {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="wr wr-app-shell">
        {{-- ============ HEADER ============ --}}
        <header class="wr-header">
            <div class="wr-rainbow" aria-hidden="true"></div>
            <div class="wr-header__bar">
                <a href="{{ route('home') }}" class="wr-wordmark" style="text-decoration:none;" wire:navigate>MARY.WIN</a>
                <div class="wr-header__right">
                    @auth
                        <div class="wr-userbar">
                            <span class="wr-userbar__name">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="wr-logout">Log out</button>
                            </form>
                        </div>
                    @endauth
                    <button type="button" class="wr-toggle"
                        x-data="{ dark: document.documentElement.dataset.theme === 'dark' }"
                        x-init="$watch('dark', v => {
                            document.documentElement.dataset.theme = v ? 'dark' : 'light';
                            try { localStorage.setItem('winrar-theme', v ? 'dark' : 'light'); } catch (e) {}
                        })"
                        @click="dark = !dark"
                        x-text="dark ? '☀ LIGHTS ON' : '☾ LIGHTS OFF'">☾ LIGHTS OFF</button>
                </div>
            </div>
        </header>

        {{-- ============ CONTENT ============ --}}
        <main class="wr-app-main">
            <div class="wr-wrap" style="padding-block: 44px 28px;">
                {{ $slot }}
            </div>
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

    @livewireScripts
</body>

</html>
