@php
    // Fillable slots — Mary supplies these when known.
    $slidesUrl = null; // link to the deck, once it exists.
    $recordingUrl = null; // link to the talk recording, once it exists.

    $sources = [
        ['id' => 's1', 'label' => 'S1', 'title' => 'Render, Publish, and Mount', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/render-publish-mount', 'host' => 'nativephp.com'],
        ['id' => 's2', 'label' => 'S2', 'title' => 'Subtree Reuse', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/subtree-reuse', 'host' => 'nativephp.com'],
        ['id' => 's3', 'label' => 'S3', 'title' => 'Threading Model', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/threading-model', 'host' => 'nativephp.com'],
        ['id' => 's4', 'label' => 'S4', 'title' => 'Embedded PHP', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/embedded-php', 'host' => 'nativephp.com'],
        ['id' => 's5', 'label' => 'S5', 'title' => 'Cross-Platform Implementation', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/cross-platform-implementation', 'host' => 'nativephp.com'],
        ['id' => 's6', 'label' => 'S6', 'title' => 'Glossary', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/glossary', 'host' => 'nativephp.com'],
        ['id' => 's7', 'label' => 'S7', 'title' => 'SuperNative Introduction (docs)', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/super-native', 'host' => 'nativephp.com'],
        ['id' => 's8', 'label' => 'S8', 'title' => 'About the New Architecture', 'url' => 'https://nativephp.com/docs/mobile/4/architecture/about-the-new-architecture', 'host' => 'nativephp.com'],
        ['id' => 's9', 'label' => 'S9', 'title' => 'Blog: SuperNative', 'url' => 'https://nativephp.com/blog/supernative', 'host' => 'nativephp.com'],
        ['id' => 's10', 'label' => 'S10', 'title' => 'Reference app (super-native)', 'url' => 'https://github.com/NativePHP/super-native', 'host' => 'github.com'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Design Patterns in NativePHP — v4’s render cycle</title>
    <meta name="description"
        content="A 10-minute talk on NativePHP v4’s SuperNative render cycle, told as one button press across a language border — with every claim checked against the docs." />

    <link rel="canonical" href="{{ route('talks.nativephp-patterns') }}" />
    <meta property="og:type" content="article" />
    <meta property="og:site_name" content="mary.win" />
    <meta property="og:title" content="Design Patterns in NativePHP — v4’s render cycle" />
    <meta property="og:description"
        content="A 10-minute talk on NativePHP v4’s SuperNative render cycle, told as one button press across a language border — with every claim checked against the docs." />
    <meta property="og:url" content="{{ route('talks.nativephp-patterns') }}" />
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

    {{-- Talk-page composition — built on the winrar tokens only, no new primitives. --}}
    <style>
.np-pattern code,
        .np-note code,
        .np-table code,
        .np-footnote code {
            font-family: var(--font-mono);
            font-size: 0.95em;
            background: var(--plate-2);
            border: 1px solid var(--hair);
            padding: 1px 5px;
            white-space: nowrap;
        }

        .np-note {
            font-size: 12px;
            line-height: 2;
            color: var(--muted);
            max-width: min(72ch, 100%);
            margin: 0 0 18px;
        }

        /* type tags — color never carries the meaning alone; the label does */
        .np-tag {
            display: inline-block;
            font-size: var(--text-label-size);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-weight: 600;
            border: 1px solid currentColor;
            padding: 2px 7px;
            line-height: 1.7;
            /* no nowrap: the long tags ("verified PHP abstraction + …") must
               be able to wrap inside the chip, or they blow the plate column
               out past narrow viewports */
        }

        .np-tag--doc {
            color: var(--accent-2);
        }

        .np-tag--interp {
            color: var(--pop);
        }

        .np-tablewrap {
            overflow-x: auto;
        }

        .np-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 12px;
        }

        .np-table th {
            font-size: var(--text-label-size);
            letter-spacing: var(--text-label-tracking);
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 600;
            padding: 11px 16px;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        .np-table td {
            padding: 13px 16px;
            border-bottom: 1px dotted var(--hair);
            vertical-align: top;
            line-height: 1.7;
        }

        .np-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .np-claim {
            color: var(--ink);
            font-weight: 500;
            min-width: 220px;
        }

        .np-quote {
            color: var(--soft);
            min-width: 280px;
        }

        .np-src {
            min-width: 170px;
        }

        .np-src a {
            display: block;
        }

        /* below ~640px each row folds into a citation card: claim as title
           with the type tag top-right, the quote as a ruled excerpt, and the
           source links as one compact inline row — no repeated field labels */
        @media (max-width: 640px) {
            .np-table thead {
                display: none;
            }

            .np-table,
            .np-table tbody {
                display: block;
            }

            .np-table tr {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                grid-template-areas:
                    "claim type"
                    "quote quote"
                    "src   src";
                gap: 10px 12px;
                padding: 16px;
                border-bottom: 1px solid var(--hair);
            }

            .np-table tbody tr:last-child {
                border-bottom: 0;
            }

            .np-table td {
                display: block;
                border: 0;
                padding: 0;
                min-width: 0;
            }

            .np-claim {
                grid-area: claim;
                font-size: 13px;
            }

            .np-type {
                grid-area: type;
                align-self: start;
            }

            .np-table .np-quote {
                grid-area: quote;
                border-left: 2px solid var(--hair);
                padding-left: 12px;
            }

            .np-src {
                grid-area: src;
            }

            .np-src a {
                display: inline;
            }

            .np-src a + a::before {
                content: "· ";
                color: var(--muted);
            }
        }

        .np-sources {
            list-style: none;
            margin: 0;
        }

        .np-sources li {
            scroll-margin-top: 84px;
        }

        .np-sources__id {
            font-weight: 600;
            color: var(--accent-2);
            width: 30px;
            flex: none;
        }

        .np-footnote {
            font-size: var(--text-caption-size);
            line-height: 1.8;
            color: var(--muted);
            max-width: min(74ch, 100%);
        }

        .np-footnote+.np-footnote {
            margin-top: 8px;
        }

        /* pattern list + slide diagrams (stages imported from the deck, scaled to fit) */
        .np-patterns {
            display: grid;
            gap: 22px;
        }

        .np-pattern {
            padding: 16px;
            display: grid;
            gap: 14px;
        }

        .np-pattern__claim {
            margin: 0;
            font-size: var(--text-body-size);
            line-height: 1.9;
            color: var(--soft);
            max-width: min(72ch, 100%);
        }

        .np-pattern__meta {
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 8px 12px;
        }

        .np-lineage {
            color: var(--muted);
            font-size: var(--text-caption-size);
            line-height: 1.7;
        }

        .np-figure {
            margin: 0;
            display: grid;
            gap: 9px;
        }

        .np-figurewrap {
            overflow-x: auto;
        }

        /* zoom/pan affordances — the fit script marks wraps whose diagram is
           rendering below its legible width (is-zoomable) and, once zoomed,
           wider than the screen (is-scrollable); the cue sticks to the left
           edge while you drag */
        .np-figurewrap.is-zoomable::after,
        .np-figurewrap.is-scrollable::after {
            content: 'tap to zoom';
            display: block;
            position: sticky;
            left: 0;
            width: max-content;
            padding-top: 7px;
            font-size: var(--text-label-size);
            letter-spacing: var(--text-label-tracking);
            text-transform: uppercase;
            font-weight: 600;
            color: var(--accent-2);
        }

        .np-figurewrap.is-scrollable::after {
            content: 'drag to pan · tap to shrink';
        }

        .np-figurewrap.is-zoomable .np-diagram {
            cursor: zoom-in;
        }

        .np-figurewrap.is-scrollable .np-diagram {
            cursor: zoom-out;
        }

        /* the diagrams keep the slides' own white canvas in both themes.
           No width floor: each diagram tracks its container and the fit
           script scales the stage to match, so it resizes with the viewport.
           When that leaves the labels too small to read, tapping restores a
           legible width (820px full / 430px half — the --half class is the
           script's hook) as an inline min-width, and the wrap pans. */
        .np-diagram {
            position: relative;
            background: #ffffff;
            border: 1px solid var(--hair);
            overflow: hidden;
        }

        .np-diagram__stage {
            position: absolute;
            top: 0;
            left: 0;
            transform-origin: top left;
            /* the slides' own ink — the stage is imported artwork on a white canvas */
            color: #111827;
        }

        .np-figcap {
            color: var(--muted);
            font-size: var(--text-caption-size);
            line-height: 1.7;
        }

        .np-figgrid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* grid items don't shrink below their content's min-width by default;
           without this the half diagrams blow the whole plate column out */
        .np-figgrid > div {
            min-width: 0;
        }

        .np-sublabel {
            margin: 0 0 8px;
            font-size: var(--text-label-size);
            letter-spacing: var(--text-label-tracking);
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .np-figgrid {
                grid-template-columns: 1fr;
            }
        }

        /* small phones: the hero tracks the viewport instead of pinning at
           the system's 56px floor */
        @media (max-width: 480px) {
            .wr-hero__title {
                font-size: clamp(42px, 13.5vw, 56px);
            }
        }
    </style>
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
                    <p class="wr-entry"><span class="wr-dot" aria-hidden="true"></span>TALK</p>
                    <h1 class="wr-display wr-hero__title">Design Patterns<br><span class="wr-grad">in NativePHP</span></h1>
                    <p class="wr-hero__lede">Design patterns under pressure in the NativePHP v4 render cycle</p>
                    <div class="wr-hero__actions">
                        <a href="https://nativephp.com/docs/mobile/4/architecture" rel="noopener"
                            class="wr-btn wr-btn--cta">VIEW THE NATIVEPHP DOCS →</a>
                    </div>
                </div>
            </section>

            {{-- ============ THE PATTERNS ============ --}}
            <section class="wr-wrap wr-section" style="padding-top: 22px;">
                <h2 class="wr-display wr-display--h2">The shape of <span class="wr-em">the nine.</span></h2>

                <p class="np-note">Nine design patterns under pressure inside the NativePHP v4 render cycle —
                    where PHP drives SwiftUI and Compose with no webview and no JSON bridge. One request,
                    followed all the way — pipeline order is the argument.</p>

                <div class="np-patterns">
                    <div class="wr-plate">
                        <div class="wr-plate__head">
                            <span class="wr-label">01 — Front controller</span>
                            <span class="wr-row__year">entry</span>
                        </div>
                        <div class="np-pattern">
                            <p class="np-pattern__claim">One entry point that routes every request to the object that handles it.</p>
                            <p class="np-pattern__meta"><span class="np-tag np-tag--doc">verified PHP</span> <span class="np-lineage">classical UML · Gang of Four adjacent · and already in your muscle memory</span></p>
                            <figure class="np-figure">
<div class="np-figurewrap">
<div class="np-diagram" style="aspect-ratio: 1560 / 640;">
<div class="np-diagram__stage" style="width:1560px;height:640px">
    <svg viewBox="0 0 1560 640" style="position:absolute;inset:0;width:100%;height:100%">
      <line x1="250" y1="285" x2="404" y2="285" stroke="#111827" stroke-width="2.5" stroke-dasharray="10 8" />
      <polygon points="420,285 398,275 398,295" fill="#111827" />
      <line x1="820" y1="250" x2="1064" y2="170" stroke="#111827" stroke-width="2.5" />
      <polygon points="1080,165 1057,159 1063,178" fill="#111827" />
      <text x="880" y="185" font-family="ui-monospace,Menlo,monospace" font-size="22" fill="#374151">dispatches to</text>
      <path d="M1115 400 L1115 340 L1280 340 L1280 255" fill="none" stroke="#111827" stroke-width="2.5" />
      <path d="M1425 400 L1425 340 L1280 340" fill="none" stroke="#111827" stroke-width="2.5" />
      <polygon points="1280,225 1259,257 1301,257" fill="#ffffff" stroke="#111827" stroke-width="2.5" />
    </svg>
    <div style="position:absolute;left:20px;top:230px;width:230px;height:110px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:26px;border-bottom:2.5px solid #111827">Client</div>
      <div style="padding:12px 16px;font-size:21px;color:#4b5563">one request</div>
    </div>
    <div style="position:absolute;left:420px;top:190px;width:400px;height:190px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:14px;text-align:center;font-weight:700;font-size:28px;border-bottom:2.5px solid #111827;background:#cbe7d8">FrontController</div>
      <div style="padding:14px 18px;font-size:21px;color:#4b5563;border-bottom:2.5px solid #111827">- handlers: map</div>
      <div style="padding:14px 18px;font-size:21px;color:#111827">+ handle(request)</div>
    </div>
    <div style="position:absolute;left:1080px;top:75px;width:400px;height:150px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:14px;text-align:center;border-bottom:2.5px solid #111827"><span style="display:block;font-size:19px;color:#6b7280;font-style:italic">&laquo;abstract&raquo;</span><span style="font-weight:700;font-size:28px">Handler</span></div>
      <div style="padding:14px 18px;font-size:21px;font-style:italic;color:#111827">+ render(): View</div>
    </div>
    <div style="position:absolute;left:990px;top:400px;width:250px;height:130px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:24px;border-bottom:2.5px solid #111827">HomeHandler</div>
      <div style="padding:12px 16px;font-size:20px;color:#111827">+ render()</div>
    </div>
    <div style="position:absolute;left:1300px;top:400px;width:250px;height:130px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:24px;border-bottom:2.5px solid #111827">TabsHandler</div>
      <div style="padding:12px 16px;font-size:20px;color:#111827">+ render()</div>
    </div>
  </div>
</div>
</div>
                                <figcaption class="np-figcap">Many handlers, one door. The controller knows how to dispatch — nothing else.</figcaption>
                            </figure>
                        </div>
                    </div>
                    <div class="wr-plate">
                        <div class="wr-plate__head">
                            <span class="wr-label">02 — Interpreter</span>
                            <span class="wr-row__year">render</span>
                        </div>
                        <div class="np-pattern">
                            <p class="np-pattern__claim">A DSL with an evaluation step — Tailwind-looking syntax, but nothing CSS ever touches it.</p>
                            <p class="np-pattern__meta"><span class="np-tag np-tag--doc">verified PHP</span> <span class="np-lineage">classical UML · Gang of Four, chapter 5</span></p>
                            <figure class="np-figure">
<div class="np-figurewrap">
<div class="np-diagram" style="aspect-ratio: 1560 / 640;">
<div class="np-diagram__stage" style="width:1560px;height:640px">
    <svg viewBox="0 0 1560 640" style="position:absolute;inset:0;width:100%;height:100%">
      <line x1="250" y1="285" x2="500" y2="200" stroke="#111827" stroke-width="2.5" stroke-dasharray="10 8" />
      <polygon points="516,195 493,189 499,208" fill="#111827" />
      <text x="270" y="200" font-family="ui-monospace,Menlo,monospace" font-size="22" fill="#374151">interpret(ctx)</text>
      <path d="M560 420 L560 340 L726 340 L726 250" fill="none" stroke="#111827" stroke-width="2.5" />
      <path d="M960 420 L960 340 L726 340" fill="none" stroke="#111827" stroke-width="2.5" />
      <polygon points="726,220 705,252 747,252" fill="#ffffff" stroke="#111827" stroke-width="2.5" />
      <line x1="936" y1="140" x2="1274" y2="140" stroke="#111827" stroke-width="2.5" />
      <polygon points="1290,140 1268,130 1268,150" fill="#111827" />
      <text x="1000" y="120" font-family="ui-monospace,Menlo,monospace" font-size="22" fill="#374151">evaluated to a value</text>
    </svg>
    <div style="position:absolute;left:20px;top:230px;width:230px;height:110px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:26px;border-bottom:2.5px solid #111827">Context</div>
      <div style="padding:12px 16px;font-size:21px;color:#4b5563">the template</div>
    </div>
    <div style="position:absolute;left:516px;top:70px;width:420px;height:150px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:14px;text-align:center;border-bottom:2.5px solid #111827;background:#cbe7d8"><span style="display:block;font-size:19px;color:#3f6b57;font-style:italic">&laquo;abstract&raquo;</span><span style="font-weight:700;font-size:26px">AbstractExpression</span></div>
      <div style="padding:14px 18px;font-size:21px;font-style:italic">+ interpret(ctx)</div>
    </div>
    <div style="position:absolute;left:1290px;top:85px;width:250px;height:110px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:24px;border-bottom:2.5px solid #111827">Element</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563">an object</div>
    </div>
    <div style="position:absolute;left:400px;top:420px;width:320px;height:160px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:24px;border-bottom:2.5px solid #111827">TerminalExpression</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563;line-height:1.4">a value: <span style="color:#111827">text-7xl</span><br />&rarr; fontSize(72)</div>
    </div>
    <div style="position:absolute;left:790px;top:420px;width:340px;height:160px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:24px;border-bottom:2.5px solid #111827">Nonterminal</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563;line-height:1.4">holds expressions:<br /><span style="color:#111827">native:column</span></div>
    </div>
  </div>
</div>
</div>
                                <figcaption class="np-figcap">The grammar is the tag set. The evaluation happens before anything crosses the boundary.</figcaption>
                            </figure>
                        </div>
                    </div>
                    <div class="wr-plate">
                        <div class="wr-plate__head">
                            <span class="wr-label">03 — Composite</span>
                            <span class="wr-row__year">render</span>
                        </div>
                        <div class="np-pattern">
                            <p class="np-pattern__claim">Elements form a tree — screen → column → text + button. Higher-level components you compose flatten; only primitives appear in the published tree.</p>
                            <p class="np-pattern__meta"><span class="np-tag np-tag--doc">verified PHP</span> <span class="np-lineage">classical UML · the self-referential one</span></p>
                            <figure class="np-figure">
<div class="np-figurewrap">
<div class="np-diagram" style="aspect-ratio: 1560 / 640;">
<div class="np-diagram__stage" style="width:1560px;height:640px">
    <svg viewBox="0 0 1560 640" style="position:absolute;inset:0;width:100%;height:100%">
      <line x1="250" y1="200" x2="484" y2="160" stroke="#111827" stroke-width="2.5" stroke-dasharray="10 8" />
      <polygon points="500,157 477,152 482,171" fill="#111827" />
      <path d="M600 430 L600 350 L700 350 L700 265" fill="none" stroke="#111827" stroke-width="2.5" />
      <path d="M1000 430 L1000 350 L700 350" fill="none" stroke="#111827" stroke-width="2.5" />
      <polygon points="700,235 679,267 721,267" fill="#ffffff" stroke="#111827" stroke-width="2.5" />
      <path d="M1220 470 L1330 470 L1330 130 L920 130" fill="none" stroke="#111827" stroke-width="2.5" />
      <polygon points="1220,470 1240,458 1260,470 1240,482" fill="#ffffff" stroke="#111827" stroke-width="2.5" />
      <polygon points="904,130 926,120 926,140" fill="#111827" />
      <text x="1350" y="300" font-family="ui-monospace,Menlo,monospace" font-size="22" fill="#374151">children</text>
      <text x="1350" y="330" font-family="ui-monospace,Menlo,monospace" font-size="22" fill="#374151">0..*</text>
    </svg>
    <div style="position:absolute;left:20px;top:145px;width:230px;height:110px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:26px;border-bottom:2.5px solid #111827">Client</div>
      <div style="padding:12px 16px;font-size:21px;color:#4b5563">your render()</div>
    </div>
    <div style="position:absolute;left:500px;top:85px;width:420px;height:150px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:14px;text-align:center;border-bottom:2.5px solid #111827;background:#cbe7d8"><span style="display:block;font-size:19px;color:#3f6b57;font-style:italic">&laquo;abstract&raquo;</span><span style="font-weight:700;font-size:26px">Component</span></div>
      <div style="padding:14px 18px;font-size:21px;font-style:italic">+ render()</div>
    </div>
    <div style="position:absolute;left:460px;top:430px;width:280px;height:140px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:24px;border-bottom:2.5px solid #111827">Leaf</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563;line-height:1.4">no children<br />+ render()</div>
    </div>
    <div style="position:absolute;left:800px;top:430px;width:420px;height:180px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:24px;border-bottom:2.5px solid #111827">Composite</div>
      <div style="padding:12px 16px;font-size:20px;border-bottom:2.5px solid #111827">- children: Component[]</div>
      <div style="padding:12px 16px;font-size:20px;line-height:1.4">+ add(c) &middot; + render()</div>
    </div>
  </div>
</div>
</div>
                                <figcaption class="np-figcap">A composite holds components — including other composites. One uniform node type, arbitrarily deep.</figcaption>
                            </figure>
                        </div>
                    </div>
                    <div class="wr-plate">
                        <div class="wr-plate__head">
                            <span class="wr-label">04 — Command</span>
                            <span class="wr-row__year">render</span>
                        </div>
                        <div class="np-pattern">
                            <p class="np-pattern__claim"><code>@@press="refresh"</code> is registered and replaced with a stable callback ID. Nodes reference handlers by ID; the native event carries the ID back; PHP resolves it to your method.</p>
                            <p class="np-pattern__meta"><span class="np-tag np-tag--doc">verified PHP + documented ID resolution</span> <span class="np-lineage">classical UML · Gang of Four, and the one that had no alternative</span></p>
                            <figure class="np-figure">
<div class="np-figurewrap">
<div class="np-diagram" style="aspect-ratio: 1560 / 620;">
<div class="np-diagram__stage" style="width:1560px;height:620px">
    <svg viewBox="0 0 1560 620" style="position:absolute;inset:0;width:100%;height:100%">
      <line x1="320" y1="200" x2="464" y2="175" stroke="#111827" stroke-width="2.5" stroke-dasharray="10 8" />
      <polygon points="480,172 457,167 462,186" fill="#111827" />
      <text x="120" y="330" font-family="ui-monospace,Menlo,monospace" font-size="21" fill="#dc2626">holds an ID,</text>
      <text x="120" y="358" font-family="ui-monospace,Menlo,monospace" font-size="21" fill="#dc2626">not a reference</text>
      <path d="M640 420 L640 350 L670 350 L670 265" fill="none" stroke="#111827" stroke-width="2.5" />
      <polygon points="670,235 649,267 691,267" fill="#ffffff" stroke="#111827" stroke-width="2.5" />
      <line x1="1000" y1="490" x2="1064" y2="490" stroke="#111827" stroke-width="2.5" />
      <polygon points="1080,490 1058,480 1058,500" fill="#111827" />
      <text x="1000" y="402" font-family="ui-monospace,Menlo,monospace" font-size="21" fill="#374151">receiver</text>
    </svg>
    <div style="position:absolute;left:20px;top:130px;width:300px;height:170px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:26px;border-bottom:2.5px solid #111827">Invoker</div>
      <div style="padding:12px 16px;font-size:20px;border-bottom:2.5px solid #111827;background:#cbe7d8">- token: int</div>
      <div style="padding:12px 16px;font-size:20px">+ fire(token)</div>
    </div>
    <div style="position:absolute;left:480px;top:100px;width:380px;height:150px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:14px;text-align:center;border-bottom:2.5px solid #111827"><span style="display:block;font-size:19px;color:#6b7280;font-style:italic">&laquo;abstract&raquo;</span><span style="font-weight:700;font-size:26px">Command</span></div>
      <div style="padding:14px 18px;font-size:21px;font-style:italic">+ execute()</div>
    </div>
    <div style="position:absolute;left:480px;top:420px;width:520px;height:170px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:24px;border-bottom:2.5px solid #111827">ConcreteCommand</div>
      <div style="padding:12px 16px;font-size:20px;border-bottom:2.5px solid #111827">- receiver: Receiver</div>
      <div style="padding:12px 16px;font-size:20px">+ execute() { receiver.action() }</div>
    </div>
    <div style="position:absolute;left:1080px;top:420px;width:400px;height:170px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:24px;border-bottom:2.5px solid #111827">Receiver</div>
      <div style="padding:12px 16px;font-size:20px;border-bottom:2.5px solid #111827">your component</div>
      <div style="padding:12px 16px;font-size:20px">+ action()</div>
    </div>
  </div>
</div>
</div>
                                <figcaption class="np-figcap">The invoker can be on the other side of a process boundary, because all it needs to send back is the token.</figcaption>
                            </figure>
                        </div>
                    </div>
                    <div class="wr-plate">
                        <div class="wr-plate__head">
                            <span class="wr-label">05 — Structural sharing</span>
                            <span class="wr-row__year">publish</span>
                        </div>
                        <div class="np-pattern">
                            <p class="np-pattern__claim">If only part of the tree changed, unchanged subtrees become tiny reuse markers instead of being re-encoded.</p>
                            <p class="np-pattern__meta"><span class="np-tag np-tag--doc">documented architecture · beta</span> <span class="np-lineage">Flyweight-adjacent — not GoF</span></p>
                            <figure class="np-figure">
<div class="np-figurewrap">
<div class="np-diagram" style="aspect-ratio: 1560 / 600;">
<div class="np-diagram__stage" style="width:1560px;height:600px">
    <svg viewBox="0 0 1560 600" style="position:absolute;inset:0;width:100%;height:100%">
      <line x1="330" y1="120" x2="700" y2="120" stroke="#111827" stroke-width="2.5" stroke-dasharray="10 8" />
      <polygon points="716,120 694,110 694,130" fill="#111827" />
      <line x1="330" y1="420" x2="700" y2="420" stroke="#111827" stroke-width="2.5" stroke-dasharray="10 8" />
      <polygon points="716,420 694,410 694,430" fill="#111827" />
      <line x1="1040" y1="120" x2="1180" y2="260" stroke="#00aaa6" stroke-width="3" />
      <line x1="1040" y1="420" x2="1180" y2="290" stroke="#00aaa6" stroke-width="3" />
      <text x="360" y="100" font-family="ui-monospace,Menlo,monospace" font-size="21" fill="#374151">re-encoded</text>
      <text x="360" y="400" font-family="ui-monospace,Menlo,monospace" font-size="21" fill="#374151">reuse marker</text>
      <text x="1180" y="415" font-family="ui-monospace,Menlo,monospace" font-size="21" fill="#007572">both point at one subtree</text>
    </svg>
    <div style="position:absolute;left:20px;top:60px;width:310px;height:130px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:24px;border-bottom:2.5px solid #111827">Frame n</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563">the text changed</div>
    </div>
    <div style="position:absolute;left:20px;top:360px;width:310px;height:130px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:24px;border-bottom:2.5px solid #111827">Frame n+1</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563">nothing changed</div>
    </div>
    <div style="position:absolute;left:716px;top:60px;width:324px;height:130px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:22px;border-bottom:2.5px solid #111827">full node records</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563">type | layout | style&hellip;</div>
    </div>
    <div style="position:absolute;left:716px;top:360px;width:324px;height:130px;border:2.5px solid #111827;background:#cbe7d8;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:22px;border-bottom:2.5px solid #111827">marker: reuse(k)</div>
      <div style="padding:12px 16px;font-size:20px;color:#3f6b57">a few bytes</div>
    </div>
    <div style="position:absolute;left:1180px;top:180px;width:360px;height:190px;border:2.5px solid #00aaa6;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:14px;text-align:center;font-weight:700;font-size:24px;border-bottom:2.5px solid #00aaa6;color:#007572">shared subtree</div>
      <div style="padding:14px 18px;font-size:20px;color:#4b5563;line-height:1.5">key: 'rows-container'<br />encoded once<br />spliced thereafter</div>
    </div>
  </div>
</div>
</div>
                                <figcaption class="np-figcap">Not Flyweight: nothing is interned for memory. It’s the encode step that’s being saved.</figcaption>
                            </figure>
                        </div>
                    </div>
                    <div class="wr-plate">
                        <div class="wr-plate__head">
                            <span class="wr-label">06 — Producer / consumer</span>
                            <span class="wr-row__year">publish</span>
                        </div>
                        <div class="np-pattern">
                            <p class="np-pattern__claim">The Element Runtime writes each element into shared memory as a node, atomically bumps the frame version, and wakes the native reader — all on the PHP thread, off the UI thread.</p>
                            <p class="np-pattern__meta"><span class="np-tag np-tag--doc">documented architecture · beta</span> <span class="np-lineage">not in the book — honest name · there is no PHP to show you here, and that is itself the point</span></p>
                            <figure class="np-figure">
<div class="np-figurewrap">
<div class="np-diagram" style="aspect-ratio: 1560 / 600;">
<div class="np-diagram__stage" style="width:1560px;height:600px">
    <svg viewBox="0 0 1560 600" style="position:absolute;inset:0;width:100%;height:100%">
      <line x1="360" y1="240" x2="584" y2="240" stroke="#111827" stroke-width="2.5" />
      <polygon points="600,240 578,230 578,250" fill="#111827" />
      <text x="380" y="215" font-family="ui-monospace,Menlo,monospace" font-size="21" fill="#374151">writes nodes</text>
      <line x1="1000" y1="240" x2="1184" y2="240" stroke="#111827" stroke-width="2.5" />
      <polygon points="1200,240 1178,230 1178,250" fill="#111827" />
      <text x="1010" y="215" font-family="ui-monospace,Menlo,monospace" font-size="21" fill="#374151">wakes reader</text>
      <path d="M1370 400 L1370 500 L190 500 L190 370" fill="none" stroke="#111827" stroke-width="2.5" stroke-dasharray="10 8" />
      <polygon points="190,340 180,363 200,363" fill="#111827" />
      <text x="640" y="540" font-family="ui-monospace,Menlo,monospace" font-size="21" fill="#374151">events come back the other way (pattern 4)</text>
    </svg>
    <div style="position:absolute;left:20px;top:170px;width:340px;height:170px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:25px;border-bottom:2.5px solid #111827">Producer</div>
      <div style="padding:12px 16px;font-size:20px;border-bottom:2.5px solid #111827">the PHP thread</div>
      <div style="padding:12px 16px;font-size:20px">+ publish(tree)</div>
    </div>
    <div style="position:absolute;left:600px;top:130px;width:400px;height:250px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:14px;text-align:center;font-weight:700;font-size:25px;border-bottom:2.5px solid #111827;background:#cbe7d8">Shared buffer</div>
      <div style="padding:12px 16px;font-size:20px;border-bottom:2.5px solid #111827">- nodes: byte[]</div>
      <div style="padding:12px 16px;font-size:20px;border-bottom:2.5px solid #111827;background:#cbe7d8">- version: atomic int</div>
      <div style="padding:12px 16px;font-size:20px;line-height:1.4">one memory region<br />no locks on the hot path</div>
    </div>
    <div style="position:absolute;left:1200px;top:170px;width:340px;height:230px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:25px;border-bottom:2.5px solid #111827">Consumer</div>
      <div style="padding:12px 16px;font-size:20px;border-bottom:2.5px solid #111827">native reader thread</div>
      <div style="padding:12px 16px;font-size:20px;line-height:1.4">+ read(version)<br />+ mount()</div>
    </div>
  </div>
</div>
</div>
                                <figcaption class="np-figcap">You bump a version and move on. Nobody waits for anybody.</figcaption>
                            </figure>
                        </div>
                    </div>
                    <div class="wr-plate">
                        <div class="wr-plate__head">
                            <span class="wr-label">07 — Reconciliation</span>
                            <span class="wr-row__year">mount</span>
                        </div>
                        <div class="np-pattern">
                            <p class="np-pattern__claim">A dedicated reader thread decodes the nodes and diffs against the tree it rendered last time. Reuse markers are spliced from the previous tree without decoding. SwiftUI and Compose re-render exactly the views whose nodes changed.</p>
                            <p class="np-pattern__meta"><span class="np-tag np-tag--doc">documented architecture · beta</span> <span class="np-lineage">lineage: virtual DOM — not GoF · this is why a running animation survives a re-render</span></p>
                            <figure class="np-figure">
<div class="np-figurewrap">
<div class="np-diagram" style="aspect-ratio: 1560 / 600;">
<div class="np-diagram__stage" style="width:1560px;height:600px">
    <svg viewBox="0 0 1560 600" style="position:absolute;inset:0;width:100%;height:100%">
      <line x1="340" y1="150" x2="584" y2="255" stroke="#111827" stroke-width="2.5" />
      <polygon points="600,262 577,246 572,265" fill="#111827" />
      <line x1="340" y1="440" x2="584" y2="335" stroke="#111827" stroke-width="2.5" />
      <polygon points="600,328 572,325 577,344" fill="#111827" />
      <line x1="980" y1="295" x2="1164" y2="295" stroke="#111827" stroke-width="2.5" />
      <polygon points="1180,295 1158,285 1158,305" fill="#111827" />
      <text x="1016" y="272" font-family="ui-monospace,Menlo,monospace" font-size="21" fill="#374151">minimal</text>
    </svg>
    <div style="position:absolute;left:20px;top:80px;width:320px;height:150px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:23px;border-bottom:2.5px solid #111827">previous Node Tree</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563;line-height:1.4">what is on screen<br />right now</div>
    </div>
    <div style="position:absolute;left:20px;top:370px;width:320px;height:150px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:23px;border-bottom:2.5px solid #111827">incoming Frame</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563;line-height:1.4">nodes + markers,<br />just published</div>
    </div>
    <div style="position:absolute;left:600px;top:200px;width:380px;height:200px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:14px;text-align:center;font-weight:700;font-size:25px;border-bottom:2.5px solid #111827;background:#cbe7d8">diff</div>
      <div style="padding:14px 18px;font-size:20px;border-bottom:2.5px solid #111827">markers &rarr; splice, don&rsquo;t decode</div>
      <div style="padding:14px 18px;font-size:20px">nodes &rarr; compare field by field</div>
    </div>
    <div style="position:absolute;left:1180px;top:170px;width:360px;height:260px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:23px;border-bottom:2.5px solid #111827">native view tree</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563;border-bottom:2.5px solid #111827">column &mdash; untouched</div>
      <div style="padding:12px 16px;font-size:20px;background:rgba(255,82,82,0.15);color:#b91c1c;border-bottom:2.5px solid #111827;box-shadow:inset 0 0 0 2.5px #dc2626">text &mdash; re-rendered</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563">button &mdash; untouched</div>
    </div>
  </div>
</div>
</div>
                                <figcaption class="np-figcap">One node changed. One view re-rendered. The drag in progress two nodes over never noticed.</figcaption>
                            </figure>
                        </div>
                    </div>
                    <div class="wr-plate">
                        <div class="wr-plate__head">
                            <span class="wr-label">08 — Bridge</span>
                            <span class="wr-row__year">mount</span>
                        </div>
                        <div class="np-pattern">
                            <p class="np-pattern__claim">One abstraction — the node — against two independently varying implementations. Flexbox values on each node drive a native Layout implementation per platform.</p>
                            <p class="np-pattern__meta"><span class="np-tag np-tag--doc">verified PHP abstraction + documented implementations</span> <span class="np-lineage">the word “bridge”, with two arrows pointing at different things — and only one of them is a pattern</span></p>
                            <div class="wr-status">
                                <span class="wr-label">CORRECTION</span> — If you learned NativePHP v3, “bridge” was the name of an IPC channel. Forget that one. The Gang of Four Bridge is here instead.
                            </div>
                            <figure class="np-figure">
<div class="np-figurewrap">
<div class="np-diagram" style="aspect-ratio: 1560 / 620;">
<div class="np-diagram__stage" style="width:1560px;height:620px">
    <svg viewBox="0 0 1560 620" style="position:absolute;inset:0;width:100%;height:100%">
      <line x1="440" y1="150" x2="784" y2="150" stroke="#111827" stroke-width="2.5" />
      <polygon points="800,150 778,140 778,160" fill="#111827" />
      <polygon points="440,150 460,138 480,150 460,162" fill="#111827" />
      <text x="520" y="128" font-family="ui-monospace,Menlo,monospace" font-size="22" fill="#374151">has an implementor</text>
      <path d="M960 440 L960 360 L1030 360 L1030 275" fill="none" stroke="#111827" stroke-width="2.5" />
      <path d="M1400 440 L1400 360 L1030 360" fill="none" stroke="#111827" stroke-width="2.5" />
      <polygon points="1030,245 1009,277 1051,277" fill="#ffffff" stroke="#111827" stroke-width="2.5" />
      <text x="120" y="420" font-family="ui-monospace,Menlo,monospace" font-size="22" fill="#007572">the node crosses once&hellip;</text>
      <text x="120" y="452" font-family="ui-monospace,Menlo,monospace" font-size="22" fill="#007572">&hellip;and forks on the far side</text>
    </svg>
    <div style="position:absolute;left:20px;top:90px;width:420px;height:190px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:14px;text-align:center;border-bottom:2.5px solid #111827;background:#cbe7d8"><span style="display:block;font-size:19px;color:#3f6b57;font-style:italic">&laquo;abstraction&raquo;</span><span style="font-weight:700;font-size:26px">Node</span></div>
      <div style="padding:14px 18px;font-size:20px;border-bottom:2.5px solid #111827">- layout: flexbox values</div>
      <div style="padding:14px 18px;font-size:20px">+ mount()</div>
    </div>
    <div style="position:absolute;left:800px;top:100px;width:460px;height:150px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:14px;text-align:center;border-bottom:2.5px solid #111827"><span style="display:block;font-size:19px;color:#6b7280;font-style:italic">&laquo;implementor&raquo;</span><span style="font-weight:700;font-size:26px">Layout</span></div>
      <div style="padding:14px 18px;font-size:20px;font-style:italic">+ apply(layout)</div>
    </div>
    <div style="position:absolute;left:790px;top:440px;width:340px;height:150px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:23px;border-bottom:2.5px solid #111827">SwiftUILayout</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563;line-height:1.4">iOS &mdash; compiled,<br />not PHP</div>
    </div>
    <div style="position:absolute;left:1200px;top:440px;width:340px;height:150px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
      <div style="padding:12px;text-align:center;font-weight:700;font-size:23px;border-bottom:2.5px solid #111827">ComposeLayout</div>
      <div style="padding:12px 16px;font-size:20px;color:#4b5563;line-height:1.4">Android &mdash; compiled,<br />not PHP</div>
    </div>
  </div>
</div>
</div>
                                <figcaption class="np-figcap">Abstraction and implementation vary independently — that is the definition, not an analogy.</figcaption>
                            </figure>
                        </div>
                    </div>
                    <div class="wr-plate">
                        <div class="wr-plate__head">
                            <span class="wr-label">09 — Proxy</span>
                            <span class="wr-row__year">resolution</span>
                        </div>
                        <div class="np-pattern">
                            <p class="np-pattern__claim">v3: the PHP object was a proxy for native state. v4: native owns the value; PHP holds a handle. The pattern didn’t change — the direction of ownership did.</p>
                            <p class="np-pattern__meta"><span class="np-tag np-tag--doc">verified PHP</span> <span class="np-lineage">classical UML · and then the same diagram with one arrow reversed</span></p>
                            <figure class="np-figure">
<div class="np-figgrid">
<div>
<p class="np-sublabel">the book’s picture</p>
<div class="np-figurewrap">
<div class="np-diagram np-diagram--half" style="aspect-ratio: 760 / 520;">
<div class="np-diagram__stage" style="width:760px;height:520px">
        <svg viewBox="0 0 760 520" style="position:absolute;inset:0;width:100%;height:100%">
          <path d="M120 300 L120 240 L300 240 L300 165" fill="none" stroke="#111827" stroke-width="2.5" />
          <path d="M500 300 L500 240 L300 240" fill="none" stroke="#111827" stroke-width="2.5" />
          <polygon points="300,135 279,167 321,167" fill="#ffffff" stroke="#111827" stroke-width="2.5" />
          <line x1="500" y1="400" x2="290" y2="400" stroke="#111827" stroke-width="2.5" />
          <polygon points="274,400 296,390 296,410" fill="#111827" />
          <text x="300" y="380" font-family="ui-monospace,Menlo,monospace" font-size="19" fill="#374151">holds a reference</text>
        </svg>
        <div style="position:absolute;left:150px;top:0;width:300px;height:135px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
          <div style="padding:12px;text-align:center;font-weight:700;font-size:23px;border-bottom:2.5px solid #111827">Subject</div>
          <div style="padding:12px 16px;font-size:19px;font-style:italic">+ get() / + set(v)</div>
        </div>
        <div style="position:absolute;left:0;top:300px;width:240px;height:120px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
          <div style="padding:12px;text-align:center;font-weight:700;font-size:22px;border-bottom:2.5px solid #111827">RealSubject</div>
          <div style="padding:12px 16px;font-size:19px;color:#4b5563">owns the value</div>
        </div>
        <div style="position:absolute;left:380px;top:300px;width:240px;height:120px;border:2.5px solid #111827;background:#cbe7d8;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
          <div style="padding:12px;text-align:center;font-weight:700;font-size:22px;border-bottom:2.5px solid #111827">Proxy</div>
          <div style="padding:12px 16px;font-size:19px;color:#3f6b57">stands in for it</div>
        </div>
      </div>
</div>
</div>
</div>
<div>
<p class="np-sublabel">the same picture, in v4</p>
<div class="np-figurewrap">
<div class="np-diagram np-diagram--half" style="aspect-ratio: 760 / 520;">
<div class="np-diagram__stage" style="width:760px;height:520px">
        <svg viewBox="0 0 760 520" style="position:absolute;inset:0;width:100%;height:100%">
          <line x1="240" y1="150" x2="484" y2="150" stroke="#dc2626" stroke-width="3" />
          <polygon points="500,150 478,140 478,160" fill="#dc2626" />
          <text x="250" y="128" font-family="ui-monospace,Menlo,monospace" font-size="19" fill="#dc2626">holds a handle</text>
          <line x1="500" y1="330" x2="256" y2="330" stroke="#dc2626" stroke-width="3" />
          <polygon points="240,330 262,320 262,340" fill="#dc2626" />
          <text x="270" y="310" font-family="ui-monospace,Menlo,monospace" font-size="19" fill="#dc2626">updates at frame rate</text>
        </svg>
        <div style="position:absolute;left:0;top:90px;width:240px;height:130px;border:2.5px solid #111827;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
          <div style="padding:12px;text-align:center;font-weight:700;font-size:22px;border-bottom:2.5px solid #111827">PHP</div>
          <div style="padding:12px 16px;font-size:19px;color:#4b5563;line-height:1.4">SharedValue<br />&mdash; the handle</div>
        </div>
        <div style="position:absolute;left:500px;top:70px;width:250px;height:290px;border:2.5px solid #dc2626;background:#fff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;display:flex;flex-direction:column">
          <div style="padding:12px;text-align:center;font-weight:700;font-size:22px;border-bottom:2.5px solid #dc2626;color:#b91c1c">native</div>
          <div style="padding:12px 16px;font-size:19px;border-bottom:2.5px solid #dc2626">owns the value</div>
          <div style="padding:12px 16px;font-size:19px;border-bottom:2.5px solid #dc2626;line-height:1.4">gestures write it<br />on the UI thread</div>
          <div style="padding:12px 16px;font-size:19px;line-height:1.4">at the display&rsquo;s<br />full frame rate</div>
        </div>
      </div>
</div>
</div>
</div>
</div>
                                <figcaption class="np-figcap">Same roles. Same collaboration. The arrow points the other way.</figcaption>
                            </figure>
                        </div>
                    </div>
                </div>

                <p class="np-note" style="margin: 22px 0 0;">Every one of these is a forced move at a boundary —
                    and the cycle resolves into something you can watch happen.</p>
            </section>

            {{-- ============ CITATIONS ============ --}}
            <section class="wr-wrap wr-section">
                <h2 class="wr-display wr-display--h2">Check my <span class="wr-em">work.</span></h2>

                <p class="np-note">Every row below is a claim from the talk.
                    <span class="np-tag np-tag--doc">Documented</span> rows quote the NativePHP v4 docs verbatim — the
                    exact line is shown. <span class="np-tag np-tag--interp">Interpretation</span> rows are my own
                    reading: the Gang-of-Four pattern names are mine to argue for, not the docs'. Quotes are unedited;
                    links go to the page they're pulled from.</p>

                <div class="wr-plate">
                    <div class="wr-plate__head">
                        <span class="wr-label">CLAIM × DOC LINE</span>
                        <span class="wr-plate__bar" aria-hidden="true"></span>
                    </div>
                    <div class="np-tablewrap">
                        <table class="np-table">
                            <thead>
                                <tr>
                                    <th scope="col">Claim in the talk</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Source</th>
                                    <th scope="col">Exact line from the docs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The PHP runtime is embedded
                                        inside the app process; it talks to native over shared memory, not sockets.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/embedded-php" rel="noopener">Embedded PHP</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"PHP ships inside your
                                        app as a library"; "there's no FastCGI, no sockets, no per-request process to
                                        spawn"; "shared memory only works when both sides share a process."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The Element Runtime is a PHP
                                        extension — native code compiled into libphp itself.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/embedded-php" rel="noopener">Embedded PHP</a> <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">"The Element Runtime is
                                        a PHP extension — native code compiled into libphp itself, alongside the
                                        engine."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">It ships as prebuilt binaries
                                        pinned to each release.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/embedded-php" rel="noopener">Embedded PHP</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"every release of
                                        nativephp/mobile pins exact binary builds… produced and shipped together, from
                                        matching sources."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The pipeline has three phases:
                                        Render, Publish, Mount.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">Doc page titled "Render,
                                        Publish, and Mount," describing all three stages.</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Render: PHP builds an Element
                                        Tree.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">"PHP builds an Element
                                        Tree describing what the screen should look like."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Each <code>native:</code> tag
                                        emits an Element — a plain PHP object.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Blade compiles the
                                        template, and each <code>native:</code> tag emits an Element: a plain PHP
                                        object describing one piece of UI."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Utility classes like
                                        <code>p-4</code> / <code>text-2xl</code> are parsed into layout and style
                                        values in PHP, not on the native side.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Utility classes like
                                        <code>p-4</code> and <code>text-2xl</code> are parsed into concrete layout and
                                        style values at this stage."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Composed EDGE components
                                        flatten; only primitives reach the published tree.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Higher-level EDGE
                                        components you compose yourself flatten into these primitives; only primitive
                                        elements appear in the tree."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk"><code>@@press="refresh"</code>
                                        is registered and replaced with a stable callback ID.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">"event handlers like
                                        <code>@@press="refresh"</code> are registered and replaced with stable callback
                                        IDs"; Callback ID: "native events carry the ID back, and PHP resolves it to
                                        your method."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Publish: each element is
                                        written into shared memory as a node — a compact, fixed-layout binary record
                                        (type, layout, style, refs to props and handlers).</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">"writes each element
                                        into shared memory as a node: a compact, fixed-layout binary record carrying
                                        the element's type, layout, style, and references to its props and handlers."
                                    </td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The same pipeline runs for
                                        first paint and for every update after.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"The same pipeline runs
                                        for the first paint of a screen and for every update after it."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Unchanged subtrees become tiny
                                        reuse markers instead of being re-encoded.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/subtree-reuse" rel="noopener">Subtree Reuse</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"any subtree whose
                                        fingerprint matches the previous frame is written as a single tiny reuse marker
                                        instead of being re-encoded."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">An identical frame isn't
                                        published at all.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/subtree-reuse" rel="noopener">Subtree Reuse</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Identical frames are
                                        dropped on the spot — the native side is never even woken."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">On mount, reuse markers are
                                        spliced from the previous tree without decoding.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/subtree-reuse" rel="noopener">Subtree Reuse</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"The native reader
                                        splices the corresponding subtree from the tree it already has, without
                                        decoding anything."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">A dedicated native reader
                                        thread decodes frames and diffs against the previous tree.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/threading-model" rel="noopener">Threading Model</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Each platform has a
                                        background thread that receives published frames, decodes them, and diffs them
                                        against the previous tree."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Publishing uses atomic version
                                        counters; it runs on the PHP thread, off the UI thread.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/threading-model" rel="noopener">Threading Model</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"atomic version counters
                                        on the shared region"; "the PHP thread wakes, runs your handler, re-renders and
                                        publishes."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The loop: press event (carrying
                                        a callback ID) → PHP thread wakes → runs the handler → publishes → reader
                                        thread diffs → UI thread mounts.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/threading-model" rel="noopener">Threading Model</a>
                                        <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"fires a press event
                                        carrying its callback ID into the event channel"; "the PHP thread wakes, runs
                                        your handler, re-renders and publishes → the reader thread diffs → the UI
                                        thread mounts the change."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Scroll, drag, and
                                        SharedValue-driven animation run on the UI thread frame-by-frame; PHP gets one
                                        discrete event, not a per-frame consult.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/threading-model" rel="noopener">Threading Model</a> <a href="https://nativephp.com/docs/mobile/4/architecture/about-the-new-architecture" rel="noopener">About the New Architecture</a>
                                        <a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Gestures and animations
                                        driven by SharedValues are evaluated directly on the UI thread at the display's
                                        frame rate… PHP receives one event when the gesture completes."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The value lives on the native
                                        side; PHP holds a handle to it.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/glossary" rel="noopener">Glossary</a> <a href="https://nativephp.com/docs/mobile/4/architecture/about-the-new-architecture" rel="noopener">About the New Architecture</a>
                                    </td>
                                    <td class="np-quote" data-label="Exact line from the docs">SharedValue: "A value
                                        that lives on the native side… PHP holds a handle."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">One node abstraction, two
                                        native implementations; flexbox values drive a per-platform Layout.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/cross-platform-implementation" rel="noopener">Cross-Platform Implementation</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"each platform
                                        implements flexbox inside its own layout system — a pure-Swift Layout on iOS
                                        and a Compose Layout on Android."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">It renders to SwiftUI on iOS
                                        and Jetpack Compose on Android.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/render-publish-mount" rel="noopener">Render, Publish, and Mount</a> <a href="https://nativephp.com/docs/mobile/4/architecture/cross-platform-implementation" rel="noopener">Cross-Platform Implementation</a>
                                        <a href="https://nativephp.com/docs/mobile/4/architecture/super-native" rel="noopener">SuperNative Introduction (docs)</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"a renderer that maps
                                        each node type to a SwiftUI view or a composable."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">SuperNative is the default
                                        architecture in v4; the web view is opt-in per screen.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/blog/supernative" rel="noopener">Blog: SuperNative</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"SuperNative is the
                                        default architecture"; web view is "explicitly opt-in" via
                                        <code>&lt;native:webview&gt;</code>.</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The UI render/update path has
                                        no serialization step and no web-view bridge.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/super-native" rel="noopener">SuperNative Introduction (docs)</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"no network round-trip,
                                        no serialization overhead, and no waiting on a web view bridge."</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk"><code>super-native</code> is a
                                        beta reference app, not for production.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--doc">Documented</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://github.com/NativePHP/super-native" rel="noopener">Reference app (super-native)</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">"Not for production.
                                        This is a reference app for exploring NativePHP's Element rendering system."
                                    </td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">Reading the render pipeline as
                                        a chain of Gang-of-Four patterns — Interpreter, Composite, Command, Bridge,
                                        Proxy (plus Producer/Consumer and Reconciliation, which aren't GoF) — and
                                        arguing each is a "forced move" at a boundary.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--interp">Interpretation</span></td>
                                    <td class="np-src" data-label="Source">—</td>
                                    <td class="np-quote" data-label="Exact line from the docs">The speaker's framing.
                                        Every pattern sits on a documented mechanism cited above; the names and the
                                        "forced move" thesis are the argument of the talk, not claims in the docs.</td>
                                </tr>
                                <tr>
                                    <td class="np-claim" data-label="Claim in the talk">The node-vs-platform split,
                                        presented as the Gang-of-Four Bridge pattern.</td>
                                    <td class="np-type" data-label="Type"><span
                                            class="np-tag np-tag--interp">Interpretation</span></td>
                                    <td class="np-src" data-label="Source"><a href="https://nativephp.com/docs/mobile/4/architecture/cross-platform-implementation" rel="noopener">Cross-Platform Implementation</a></td>
                                    <td class="np-quote" data-label="Exact line from the docs">Mechanism is documented
                                        (see the per-platform Layout row); calling it "Bridge" is the speaker's
                                        reading.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {{-- ============ SOURCES ============ --}}
            <section class="wr-wrap" style="padding-bottom: 80px;">
                <h2 class="wr-display wr-display--h2">The <span class="wr-em">sources.</span></h2>
                <div class="wr-plate" style="margin-bottom: 26px;">
                    <div class="wr-plate__head">
                        <span class="wr-label">EXTERNAL LINKS — S1–S10</span>
                        <span class="wr-plate__bar wr-plate__bar--sm" aria-hidden="true"></span>
                    </div>
                    <ol class="np-sources wr-plate__body">
                        @foreach ($sources as $source)
                            <li class="wr-row" id="{{ $source['id'] }}">
                                <span class="np-sources__id">{{ $source['label'] }}</span>
                                <a href="{{ $source['url'] }}" rel="noopener"
                                    class="wr-row__name">{{ $source['title'] }}</a>
                                <span class="wr-leader" aria-hidden="true"></span>
                                <span class="wr-row__status">{{ $source['host'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <p class="np-footnote">Claims verified against the live NativePHP v4 docs on 2026-07-30. NativePHP v4
                    is a public beta; APIs may change and the reference app is explicitly not for production.</p>
                {{-- Slides / recording: set $slidesUrl / $recordingUrl at the top when Mary supplies them. --}}
                @if ($slidesUrl || $recordingUrl)
                    <p class="np-footnote">
                        @if ($slidesUrl)
                            <a href="{{ $slidesUrl }}" rel="noopener">slides →</a>
                        @endif
                        @if ($recordingUrl)
                            <a href="{{ $recordingUrl }}" rel="noopener">recording →</a>
                        @endif
                    </p>
                @endif
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

    <script>
        (function () {
            var boxes = document.querySelectorAll('.np-diagram');

            function legibleWidth(box) {
                return box.classList.contains('np-diagram--half') ? 430 : 820;
            }

            function fit() {
                boxes.forEach(function (box) {
                    var stage = box.firstElementChild;
                    stage.style.transform = 'scale(' + box.clientWidth / stage.offsetWidth + ')';

                    var wrap = box.closest('.np-figurewrap');
                    if (!wrap) return;
                    wrap.classList.toggle('is-scrollable', wrap.scrollWidth - wrap.clientWidth > 1);

                    var zoomable = box.style.minWidth !== '' || box.clientWidth < legibleWidth(box) - 1;
                    wrap.classList.toggle('is-zoomable', zoomable);
                    if (zoomable) wrap.setAttribute('tabindex', '0');
                    else wrap.removeAttribute('tabindex');
                });
            }

            boxes.forEach(function (box) {
                var wrap = box.closest('.np-figurewrap');
                if (!wrap) return;

                var panStart = 0;
                wrap.addEventListener('pointerdown', function () { panStart = wrap.scrollLeft; });

                function toggle() {
                    if (!wrap.classList.contains('is-zoomable')) return;
                    if (box.style.minWidth) {
                        box.style.minWidth = '';
                        wrap.scrollLeft = 0;
                    } else {
                        box.style.minWidth = legibleWidth(box) + 'px';
                    }
                    fit();
                }

                wrap.addEventListener('click', function () {
                    // a drag that ends on the diagram fires a click too — only
                    // treat it as a tap if the wrap didn't pan in between
                    if (Math.abs(wrap.scrollLeft - panStart) > 8) return;
                    toggle();
                });

                wrap.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggle();
                    }
                });
            });

            window.addEventListener('resize', fit);
            fit();
        })();
    </script>
</body>

</html>
