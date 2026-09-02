{{--
    Minimal standalone chassis for guest-facing transactional pages (mailing
    list confirmation and opt-out). It intentionally avoids the Vite bundle so
    these pages render correctly even before front-end assets are built.
--}}
@props(['title'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>{{ $title }} — {{ config('app.name') }}</title>
        <style>
            :root { color-scheme: light dark; --fg: #1b1b18; --muted: #4a4944; --bg: #fdfdfc; }
            @media (prefers-color-scheme: dark) {
                :root { --fg: #ededec; --muted: #b6b5ae; --bg: #0a0a0a; }
            }
            body {
                margin: 0;
                padding: 2rem 1.5rem;
                background: var(--bg);
                color: var(--fg);
                font: 1rem/1.6 system-ui, -apple-system, "Segoe UI", sans-serif;
            }
            main { max-width: 34rem; margin: 0 auto; }
            h1 { font-size: 1.5rem; line-height: 1.25; margin: 0 0 .75rem; }
            p { margin: 0 0 1rem; }
            .muted { color: var(--muted); font-size: .9375rem; }
            a { color: inherit; }
            a:focus-visible, button:focus-visible {
                outline: 3px solid currentColor;
                outline-offset: 2px;
            }
            button {
                font: inherit;
                font-weight: 600;
                padding: .625rem 1.25rem;
                color: var(--bg);
                background: var(--fg);
                border: 1px solid var(--fg);
                border-radius: .375rem;
                cursor: pointer;
            }
            .break { overflow-wrap: anywhere; }
            @media (prefers-reduced-motion: reduce) {
                * { transition-duration: 0ms !important; animation-duration: 0ms !important; }
            }
            @media (forced-colors: active) {
                button { forced-color-adjust: none; color: ButtonText; background: ButtonFace; border: 1px solid CanvasText; }
                a:focus-visible, button:focus-visible { outline: 3px solid Highlight; }
            }
        </style>
    </head>
    <body>
        <main>
            {{ $slot }}
        </main>
    </body>
</html>
