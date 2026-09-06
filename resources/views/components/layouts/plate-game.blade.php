<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'license plate game — mary.win' }}</title>

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

        // The game screen: settings mirrors, the current plate, and the round tallies.
        // All rules live on the server (the pure core) — this component only collects
        // input, forwards it, and paints whatever comes back.
        document.addEventListener('alpine:init', () => {
            Alpine.data('lpGame', () => ({
                status: 'idle',        // 'idle' | 'playing' | 'over'
                plate: null,           // {code, name, letters, cells:[{t,ch}]}
                robsAvailable: true,
                draft: '',
                played: [],            // [{word, points}]
                score: 0,
                message: '',
                messageKind: 'ok',
                result: null,          // {score, words} after finish
                // settings mirrors (server-confirmed)
                stateCode: '',
                exclTwo: false,
                exclMore: false,
                robs: false,
                timerOn: false,
                timerSecs: 60,
                // countdown
                deadline: null,
                remaining: null,
                ticker: null,

                reasonText(reason) {
                    return {
                        no_round: 'press NEW PLATE first.',
                        round_over: 'time! this round is over.',
                        empty: 'type a word.',
                        already_played: 'already played that one.',
                        letters_out_of_order: 'the plate letters must appear in order.',
                        not_a_word: 'not in the word list.',
                        robs_rule: "rob's rule: start with the first plate letter, end with the last.",
                        robs_rule_needs_three_letters: "rob's rule needs a plate with 3+ letters.",
                        timer_needs_positive_seconds: 'the timer needs a positive number of seconds.',
                        no_eligible_states: 'no states match those letter settings.',
                        unknown_state: 'unknown state.',
                    }[reason] || 'rejected: ' + reason;
                },

                // A stable "vaguely the state" gradient from its code (decoration only, R19).
                plateStyle() {
                    if (!this.plate) return '';
                    let h = 0;
                    for (const c of this.plate.code) h = (h * 31 + c.charCodeAt(0)) >>> 0;
                    const a = h % 360, b = (a + 40 + ((h >> 8) % 120)) % 360;
                    return `background: linear-gradient(135deg, hsl(${a} 55% 40%), hsl(${b} 60% 28%))`;
                },

                say(text, kind = 'ok') { this.message = text; this.messageKind = kind; },

                applySettings(res) {
                    if (!res) return;
                    if (!res.ok) { this.say(this.reasonText(res.reason), 'err'); this.syncBack(); return; }
                    const s = res.settings;
                    this.stateCode = s.selected_state_code || '';
                    this.exclTwo = s.exclude_two_letter;
                    this.exclMore = s.exclude_more_than_two_letter;
                    this.robs = s.robs_rule_enabled;
                    this.timerOn = s.timer_enabled;
                    if (s.timer_seconds) this.timerSecs = s.timer_seconds;
                    this.message = '';
                },
                // On a rejected toggle, snap the controls back to the confirmed mirrors.
                syncBack() { this.$nextTick(() => { /* x-model bindings re-read mirrors */ }); },

                async pickState() { this.applySettings(await this.$wire.selectState(this.stateCode || null)); },
                async pickPrefs() { this.applySettings(await this.$wire.setLetterPreference(this.exclTwo, this.exclMore)); },
                async pickRobs() {
                    const res = await this.$wire.setRobsRule(this.robs);
                    if (res && !res.ok) this.robs = !this.robs; // snap back
                    this.applySettings(res);
                },
                async pickTimer() {
                    const res = await this.$wire.setTimer(this.timerOn, Number(this.timerSecs) || null);
                    if (res && !res.ok) this.timerOn = !this.timerOn;
                    this.applySettings(res);
                },

                async newPlate() {
                    const res = await this.$wire.newPlate();
                    if (!res.ok) { this.say(this.reasonText(res.reason), 'err'); return; }
                    this.plate = res.plate;
                    this.robsAvailable = res.robs_available;
                    this.played = [];
                    this.score = 0;
                    this.result = null;
                    this.draft = '';
                    this.status = 'playing';
                    this.message = '';
                    this.startCountdown(res.timer_seconds);
                    this.$nextTick(() => this.$refs.word?.focus());
                },

                async play() {
                    if (this.status !== 'playing' || !this.draft.trim()) return;
                    const res = await this.$wire.submit(this.draft);
                    if (res.ok) {
                        this.played.push({ word: res.word, points: res.points });
                        this.score = res.score;
                        this.say(`✓ ${res.word} +${res.points}`);
                        this.draft = '';
                    } else {
                        this.say(this.reasonText(res.reason), 'err');
                        if (res.reason === 'round_over') this.endRound();
                    }
                },

                async endRound() {
                    if (this.status === 'over') return;
                    const res = await this.$wire.finish();
                    if (!res.ok) return;
                    this.stopCountdown();
                    this.result = res;
                    this.score = res.score;
                    this.status = 'over';
                    this.message = '';
                },

                startCountdown(seconds) {
                    this.stopCountdown();
                    if (!seconds) { this.remaining = null; return; }
                    this.deadline = Date.now() + seconds * 1000;
                    this.remaining = seconds;
                    this.ticker = setInterval(() => {
                        this.remaining = Math.max(0, Math.ceil((this.deadline - Date.now()) / 1000));
                        if (this.remaining <= 0) this.endRound(); // server re-checks; this is UX
                    }, 250);
                },
                stopCountdown() {
                    if (this.ticker) clearInterval(this.ticker);
                    this.ticker = null;
                    this.deadline = null;
                },
            }));
        });
    </script>

    @vite(['resources/css/winrar.css'])
    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }

        .lpg {
            min-height: 100vh;
            background: var(--bg);
            color: var(--ink);
            font-family: var(--font-mono);
            display: flex;
            flex-direction: column;
        }
        .lpg .wr-rainbow { flex: 0 0 auto; }

        .lpg__wrap {
            flex: 1 1 auto;
            width: 100%;
            max-width: 720px;
            margin: 0 auto;
            padding: 1.4rem 1.25rem 3rem;
            display: flex;
            flex-direction: column;
            gap: 1.4rem;
        }

        .lpg__head { display: flex; align-items: center; justify-content: space-between; }
        .lpg__mark { font-size: 0.72rem; letter-spacing: 0.24em; color: var(--accent); }
        .lpg__back { font-size: 0.72rem; letter-spacing: 0.1em; color: var(--muted); text-decoration: none; }
        .lpg__back:hover { color: var(--pop); }

        .lpg__settings {
            display: flex;
            flex-wrap: wrap;
            gap: 0.9rem 1.4rem;
            align-items: center;
            font-size: 0.8rem;
            color: var(--soft);
            background: var(--plate-2);
            border: 1px solid var(--hair);
            border-radius: 12px;
            padding: 0.8rem 1rem;
        }
        .lpg__settings label { display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; }
        .lpg__settings select, .lpg__settings input[type="number"] {
            font-family: var(--font-mono);
            font-size: 0.8rem;
            color: var(--ink);
            background: var(--plate);
            border: 1px solid var(--hair);
            border-radius: 8px;
            padding: 0.35rem 0.5rem;
        }
        .lpg__settings input[type="number"] { width: 4.2rem; }
        .lpg__hint { color: var(--muted); font-size: 0.7rem; }

        .lpg__stage { display: flex; flex-direction: column; align-items: center; gap: 1.1rem; text-align: center; }

        .lpg__plate {
            width: min(100%, 420px);
            border-radius: 16px;
            padding: 1.1rem 1rem 0.8rem;
            color: #fff;
            box-shadow: inset 0 0 0 4px rgba(255, 255, 255, 0.5), inset 0 0 0 6px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.6rem;
        }
        .lpg__plate-state { font-size: 0.72rem; letter-spacing: 0.22em; text-transform: uppercase; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,.5); }
        .lpg__plate-chars { display: flex; gap: 0.45rem; }
        .lpg__cell {
            font-family: var(--font-mono);
            font-weight: 600;
            font-size: clamp(1.6rem, 6vw, 2.4rem);
            line-height: 1;
            text-shadow: 0 2px 3px rgba(0, 0, 0, 0.55);
        }
        .lpg__cell--num { opacity: 0.55; }

        .lpg__prompt {
            font-family: var(--font-display);
            font-size: clamp(1.1rem, 4vw, 1.5rem);
            font-style: italic;
            color: var(--soft);
            margin: 0;
        }
        .lpg__prompt b { letter-spacing: 0.2em; }

        .lpg__entry { display: flex; gap: 0.5rem; width: min(100%, 420px); }
        .lpg__entry input {
            flex: 1 1 auto;
            min-width: 0;
            font-family: var(--font-mono);
            font-size: 1rem;
            color: var(--ink);
            background: var(--plate);
            border: 2px solid var(--hair);
            border-radius: 10px;
            padding: 0.6rem 0.8rem;
        }
        .lpg__entry input:focus { outline: none; border-color: var(--wcol-4); }

        .lpg__btn {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #fff;
            background: linear-gradient(120deg, var(--wcol-4), var(--wcol-5));
            border: 0;
            border-radius: 999px;
            padding: 0.7rem 1.3rem;
            cursor: pointer;
            transition: transform .08s ease, filter .12s ease;
        }
        .lpg__btn:hover { filter: brightness(1.06); }
        .lpg__btn:active { transform: translateY(1px); }
        .lpg__btn--ghost { background: transparent; color: var(--muted); border: 1px solid var(--hair); }
        .lpg__btn--ghost:hover { color: var(--pop); }

        .lpg__meta { display: flex; gap: 1.6rem; align-items: center; font-size: 0.72rem; letter-spacing: 0.2em; color: var(--muted); }
        .lpg__meta b { color: var(--accent-2); font-size: 1rem; }
        .lpg__timer.low b { color: var(--pop); }

        .lpg__message { min-height: 1.3em; font-size: 0.85rem; color: var(--soft); margin: 0; }
        .lpg__message.err { color: var(--pop); }

        .lpg__chips { list-style: none; display: flex; flex-wrap: wrap; gap: 0.45rem; justify-content: center; margin: 0; padding: 0; }
        .lpg__chips li {
            background: var(--plate);
            border: 1px solid var(--hair);
            border-radius: 999px;
            padding: 0.25rem 0.8rem;
            font-size: 0.85rem;
            color: var(--ink);
        }
        .lpg__chips li b { color: var(--accent-2); font-size: 0.72rem; margin-left: 0.35rem; }

        .lpg__over-title {
            font-family: var(--font-display);
            font-size: clamp(2rem, 8vw, 3rem);
            font-weight: 900;
            color: var(--ink);
            margin: 0;
        }
        .lpg__note { font-size: 0.82rem; color: var(--muted); margin: 0; }
        .lpg__note a { color: var(--pop); }

        @media (prefers-reduced-motion: reduce) {
            .lpg__btn { transition: none; }
        }
    </style>
</head>

<body>
    {{ $slot }}
    @livewireScripts
</body>

</html>
