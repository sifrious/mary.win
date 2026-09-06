@props(['title' => 'Log in'])

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

    {{-- Auth-page composition (form primitives live in winrar.css). --}}
    <style>
        .wra-shell {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .wra-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 24px 64px;
        }

        .wra {
            width: 100%;
            max-width: 400px;
        }

        .wra__head {
            text-align: center;
            margin-bottom: 24px;
        }

        .wra__title {
            font-family: var(--font-display);
            font-weight: 900;
            font-size: 38px;
            line-height: 1;
            color: var(--ink);
            margin: 10px 0 12px;
        }

        .wra__lede {
            color: var(--soft);
            font-size: 12.5px;
            line-height: 1.7;
            max-width: 34ch;
            margin: 0 auto;
        }

        .wra__foot {
            text-align: center;
            font-size: 12px;
            color: var(--muted);
            margin-top: 22px;
        }

        .wra-status {
            margin-bottom: 18px;
        }

        .wra-form {
            display: grid;
            gap: 18px;
        }

        .wra-forgot {
            font-size: 10px;
            letter-spacing: 0.06em;
        }

        /* "or" divider between the email form and the GitHub option */
        .wra-or {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            color: var(--muted);
            font-family: var(--font-mono);
            font-size: 10px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .wra-or::before,
        .wra-or::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--line);
        }

        /* GitHub OAuth button — icon + label, built on the wr-btn--quiet primitive */
        .wra-social {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .wra-social svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }
    </style>
</head>

<body>
    <div class="wr wra-shell">
        {{-- ============ HEADER ============ --}}
        <header class="wr-header">
            <div class="wr-rainbow" aria-hidden="true"></div>
            <div class="wr-header__bar">
                <a href="{{ route('home') }}" class="wr-wordmark" style="text-decoration:none;" wire:navigate>MARY.WIN</a>
                <div class="wr-header__right">
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
        <main class="wra-main">
            {{ $slot }}
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
