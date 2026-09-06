<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'four letter words — mary.win' }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Playfair+Display:ital,wght@0,700;0,900;1,400;1,700&display=swap"
        rel="stylesheet">

    <script>
        // Restore theme before first paint so it doesn't flash.
        (function () {
            try {
                var t = localStorage.getItem('winrar-theme');
                if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t;
            } catch (e) { }
        })();

        // The dictionary, shipped to the browser once for the instant is-a-word check.
        // The server re-validates every submit — this copy is advisory, never trusted.
        window.FLW_WORDS = @json(\App\Games\FourLetterWords\WordList::words());

        // The composer types into a real (invisible) text field rather than into the window,
        // because a soft keyboard only opens for a focused editable element — buttons don't
        // summon one. The field's value is held at this pad so a backspace always has
        // something to delete, and every edit is read as a delta against it.
        const FLW_PAD = '\u00A0'.repeat(4);

        // The word composer: a tiny editor for a fixed four-letter field. All editing is
        // local and instant; only an armed submit crosses to the server (the pure core).
        document.addEventListener('alpine:init', () => {
            Alpine.data('flwComposer', () => ({
                letters: ['', '', '', ''],
                cursor: 0,        // 0..3, or the string 'submit'
                current: '',      // the current word; '' before the first play
                streak: 0,
                status: 'playing',// 'playing' | 'lost'
                reason: null,
                finalStreak: 0,
                log: [],
                words: null,

                init() { this.words = new Set(window.FLW_WORDS || []); },

                get word() { return this.letters.join(''); },
                get filled() { return this.letters.every(l => l !== ''); },
                get isWord() { return this.filled && this.words.has(this.word); },
                get changed() { return this.current === '' ? this.filled : this.word !== this.current; },
                get armed() { return this.isWord && this.changed; },

                promptText() { return this.current === '' ? 'enter a word with 4 letters' : 'change one letter'; },

                select(i) { this.cursor = i; },
                selectNumber(ch) {
                    if (this.status !== 'playing' || !/^[1-4]$/.test(ch)) return false;
                    this.select(Number(ch) - 1);
                    return true;
                },
                left() { if (this.cursor !== 'submit') this.cursor = Math.max(0, this.cursor - 1); },
                right() { if (this.cursor !== 'submit') this.cursor = Math.min(3, this.cursor + 1); },

                type(ch) {
                    if (this.selectNumber(ch)) return;
                    ch = ch.toUpperCase();
                    if (!/^[A-Z]$/.test(ch)) return;
                    const at = this.cursor === 'submit' ? 3 : this.cursor;
                    this.letters[at] = ch;
                    if (at < 3) this.cursor = at + 1;
                    else this.cursor = this.armed ? 'submit' : 3;
                },
                backspace() {
                    if (this.cursor === 'submit') { this.cursor = 3; return; }
                    this.letters[this.cursor] = '';
                    if (this.cursor > 0) this.cursor--;
                },

                // --- the hidden field: the only thing a phone will open a keyboard for ---

                // Park the value back on the pad, caret at the end, so the next edit is
                // unambiguous: longer means inserted, shorter means deleted.
                resetField() {
                    const el = this.$refs.field;
                    if (!el) return;
                    el.value = FLW_PAD;
                    try { el.setSelectionRange(FLW_PAD.length, FLW_PAD.length); } catch (e) { }
                },

                // Must run inside the tap that asked for it — iOS only raises the keyboard
                // for a focus that a user gesture caused. Refocusing an already-focused
                // field raises nothing, so leave it alone when it already has focus.
                focusField() {
                    const el = this.$refs.field;
                    if (!el || this.status !== 'playing' || document.activeElement === el) return;
                    el.focus();
                    this.resetField();
                },

                // Soft keyboards report edits, not keys (Android sends no usable keydown at
                // all), so letters and backspace are read here rather than from onKey.
                onEdit(e) {
                    const t = e.inputType;
                    if (!t) return; // no inputType to read — let the edit land and diff it below
                    if (t === 'insertText' || t === 'insertReplacementText') {
                        e.preventDefault();
                        for (const ch of (e.data || '')) this.type(ch);
                    } else if (t.startsWith('delete')) {
                        e.preventDefault();
                        this.backspace();
                    } else if (t === 'insertLineBreak' || t === 'insertParagraph') {
                        e.preventDefault();
                        this.trySubmit();
                    }
                },

                // Fallback for keyboards that compose text and so ignore the preventDefault
                // above: read whatever landed as a delta against the pad, then re-park.
                onEditFallback(e) {
                    const v = e.target.value;
                    if (v === FLW_PAD) return;
                    if (v.length > FLW_PAD.length) for (const ch of v.slice(FLW_PAD.length)) this.type(ch);
                    else if (v.length < FLW_PAD.length) this.backspace();
                    this.resetField();
                },

                // Only the keys that produce no edit event reach this; letters and backspace
                // are onEdit's, and taking them here too would apply them twice.
                onFieldKey(e) {
                    if (this.status !== 'playing' || e.ctrlKey || e.metaKey || e.altKey) return;
                    const k = e.key;
                    if (this.selectNumber(k)) { e.preventDefault(); }
                    else if (k === 'ArrowLeft') { e.preventDefault(); this.left(); }
                    else if (k === 'ArrowRight') { e.preventDefault(); this.right(); }
                    else if (k === 'Enter') { e.preventDefault(); this.trySubmit(); }
                    else if (k === 'ArrowUp' || k === 'ArrowDown' || k === 'Home' || k === 'End') e.preventDefault();
                },

                onKey(e) {
                    if (this.status !== 'playing' || e.ctrlKey || e.metaKey || e.altKey) return; // let the end screen use the keyboard normally
                    if (this.$refs.field && document.activeElement === this.$refs.field) return; // the field has it
                    const k = e.key;
                    if (this.selectNumber(k)) { e.preventDefault(); }
                    else if (k === 'ArrowLeft') { e.preventDefault(); this.left(); }
                    else if (k === 'ArrowRight') { e.preventDefault(); this.right(); }
                    else if (k === 'Backspace') { e.preventDefault(); this.backspace(); }
                    else if (k === 'Enter') { e.preventDefault(); this.trySubmit(); }
                    else if (/^[a-zA-Z]$/.test(k)) { e.preventDefault(); this.type(k); }
                },

                async trySubmit() {
                    if (!this.armed) return;
                    const w = this.word;
                    const res = await this.$wire.submit(w);
                    if (!res || res.ignored) return;
                    if (res.ok) {
                        this.current = w;                 // this word is the new base
                        this.streak = res.streak;
                        this.letters = w.split('');       // prefill for the next turn
                        this.cursor = 0;
                    } else {
                        this.status = 'lost';
                        this.reason = res.reason;
                        this.finalStreak = res.streak;
                        this.log = res.log || [];
                        this.$refs.field?.blur(); // drop the phone keyboard so the end screen is visible
                    }
                },

                // Reset first, then talk to the server: the field can only take focus (and so
                // raise the keyboard) while we are still inside the tap that called this.
                async playAgain() {
                    this.letters = ['', '', '', ''];
                    this.cursor = 0;
                    this.current = '';
                    this.streak = 0;
                    this.status = 'playing';
                    this.reason = null;
                    this.finalStreak = 0;
                    this.log = [];
                    this.focusField();
                    await this.$wire.playAgain();
                },
            }));
        });
    </script>

    @vite(['resources/css/winrar.css'])
    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }

        .flw {
            min-height: 100vh;
            min-height: 100dvh; /* the dynamic unit shrinks under a phone keyboard; vh does not */
            background: var(--bg);
            color: var(--ink);
            font-family: var(--font-mono);
            display: flex;
            flex-direction: column;
        }
        .flw .wr-rainbow { flex: 0 0 auto; }

        .flw__wrap {
            position: relative; /* anchors the invisible typing field */
            flex: 1 1 auto;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            padding: 1.4rem 1.25rem 3rem;
            display: flex;
            flex-direction: column;
        }

        .flw__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: clamp(1.5rem, 8vh, 4rem);
        }
        .flw__mark { font-size: 0.72rem; letter-spacing: 0.24em; color: var(--accent); }
        .flw__back { font-size: 0.72rem; letter-spacing: 0.1em; color: var(--muted); text-decoration: none; }
        .flw__back:hover { color: var(--pop); }

        .flw__stage {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.6rem;
            text-align: center;
        }

        .flw__prompt {
            font-family: var(--font-display);
            font-size: clamp(1.4rem, 5vw, 2rem);
            font-style: italic;
            color: var(--soft);
            margin: 0;
        }
        .flw__streak { font-size: 0.7rem; letter-spacing: 0.2em; color: var(--muted); }
        .flw__streak b { color: var(--accent-2); font-size: 1rem; }

        /* Parked over the middle of the wrap, roughly where the boxes are, so that the
           browser's scroll-the-focused-thing-into-view lands on the boxes and not elsewhere. */
        .flw__typing {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 70%;
            height: 3.5rem;
            transform: translate(-50%, -50%);
            margin: 0;
            padding: 0;
            border: 0;
            outline: none;
            -webkit-appearance: none;
            appearance: none;
            background: transparent;
            color: transparent;
            -webkit-text-fill-color: transparent;
            caret-color: transparent; /* the selected box is the cursor */
            font-size: 16px;          /* anything smaller and iOS zooms the page on focus */
            pointer-events: none;     /* taps belong to the boxes underneath */
        }

        .flw__boxes { display: flex; gap: clamp(0.5rem, 2.5vw, 0.9rem); }
        .flw__box {
            touch-action: manipulation;
            -webkit-user-select: none;
            user-select: none;
            width: clamp(58px, 17vw, 92px);
            aspect-ratio: 1 / 1;
            background: var(--plate-2);
            border: 2px solid var(--hair);
            border-radius: 12px;
            font-family: var(--font-mono);
            font-size: clamp(1.7rem, 7vw, 2.7rem);
            font-weight: 600;
            color: var(--ink);
            display: grid;
            place-items: center;
            cursor: pointer;
            padding: 0;
            transition: border-color .12s ease, background .12s ease, box-shadow .12s ease;
        }
        .flw__box.is-selected {
            background: var(--plate);
            border-color: var(--wcol-1);
            box-shadow: 0 0 0 4px color-mix(in oklab, var(--wcol-1) 26%, transparent);
        }

        .flw__submit, .flw__again {
            font-family: var(--font-mono);
            font-size: 0.8rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #fff;
            background: linear-gradient(120deg, var(--wcol-1), var(--wcol-2));
            border: 0;
            border-radius: 999px;
            padding: 0.8rem 1.6rem;
            cursor: pointer;
            transition: transform .08s ease, filter .12s ease, box-shadow .12s ease;
        }
        .flw__submit:hover, .flw__again:hover { filter: brightness(1.06); }
        .flw__submit:active, .flw__again:active { transform: translateY(1px); }
        .flw__submit.is-focused { box-shadow: 0 0 0 4px color-mix(in oklab, var(--wcol-1) 26%, transparent); }

        .flw__over-title {
            font-family: var(--font-display);
            font-size: clamp(2.2rem, 9vw, 3.4rem);
            font-weight: 900;
            color: var(--ink);
            margin: 0;
        }
        .flw__over-sub { font-size: 0.8rem; letter-spacing: 0.2em; color: var(--muted); margin: 0; }
        .flw__over-sub b { color: var(--accent-2); font-size: 1.1rem; }
        .flw__log {
            list-style: none;
            margin: 0.6rem 0 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            font-size: 1.05rem;
            letter-spacing: 0.28em;
            color: var(--soft);
        }
        .flw__log li:first-child { color: var(--pop); font-weight: 600; }
        .flw__note { font-size: 0.82rem; color: var(--muted); margin: 0.4rem 0 0; }
        .flw__note a { color: var(--pop); }

        @media (prefers-reduced-motion: reduce) {
            .flw__box, .flw__submit, .flw__again { transition: none; }
        }
    </style>
</head>

<body>
    {{ $slot }}
    @livewireScripts
</body>

</html>
