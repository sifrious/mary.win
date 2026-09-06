# BRIEF — Four Letter Words

*Genesis brief. Written before scaffolding. Versioned: changes are commits with reasons.*

## 1. Problem

People who like word games have two modes on offer: the once-a-day ration (Wordle
and its clones — one puzzle, then a 24-hour lockout) and the timed scramble (Boggle-likes
that punish you for hesitating). Neither fits the idle moment this is actually for — a bus
seat, a waiting room, the ninety seconds before a meeting starts — where you want to *keep
playing* for exactly as long as the moment lasts and stop the instant it ends, with no clock
shaming you and no daily gate telling you you're done for the day. Four Letter Words is that
moment's game: start from a four-letter word, change one letter to make another real word,
then again, and again, and see how long a chain you can keep alive. The only way to break the
chain is a word that isn't a word, or one you've already used. Hesitation is free. The reward
is your own longest streak, kept honestly.

## 2. Non-goals (each with a revisit trigger)

1. **No accounts, no server, no cross-device sync.** The game is local-only, on-device
   SQLite. *Revisit when:* players ask to carry a best streak between their phone and the web,
   or a shared leaderboard becomes the actual point of playing.
2. **No timer, ever.** "Hesitation allowed" is a design pillar, not a missing feature. The
   game must never introduce time pressure as a difficulty knob. *Revisit when:* playtesting
   shows the loop is genuinely boring without stakes — and even then, prefer non-clock stakes
   (rarer letters, longer targets) over a timer.
3. **No head-to-head / multiplayer.** Single-player against your own streak only.
   *Revisit when:* single-player retention is proven and players specifically ask to compete.
4. **No configurable dictionary, no "should this word count" settings.** One fixed word list,
   take it or leave it. *Revisit when:* a specific, repeated omission generates real complaints
   (not hypothetical ones).
5. **No shared "game engine" abstraction across games.** Four Letter Words is built
   standalone; License Plate is a separate thing that happens to share a directory.
   *Revisit when:* a third game reveals commonality that actually exists. (See do-not-build.)

## 3. Owned-diff budget

Genesis bar: **framework defaults**, with exactly two pre-authorized deviations, because both
have a real second consumer *today*, not a speculative one:

- **The core/shell split** — `packages/four-letter-words/` with a framework-free `src/Core/`.
  *PAYS WHEN:* the web (mary.win) and native (EDGE app) shells share the rules with zero
  duplication. *CHARGES WHEN:* path-repository wiring, a package that must be kept coherent.
- **NativePHP as a dependency** (dependency-tax entry to follow). *PAYS WHEN:* the same Laravel
  code becomes a native app. *CHARGES WHEN:* build/signing pipeline, plugin surface to track.

Everything else starts at Laravel/framework default. Every new deviation requires its own
PAYS WHEN / CHARGES WHEN entry in OWNED-DIFF.md before merge (diff-zero-genesis). Every
abstraction requires a named trigger condition — no speculative generality.

## 4. First measurable milestone

**A stranger can play one honest chain in a browser at mary.win by ⟨confirm: 2026-08-06⟩.**

End-to-end: they land on the game, are given a valid starting word, type a four-letter word,
and see their streak increment on a legal move — or get clearly rejected on a non-word, a
non-one-letter-change, or a repeat. Best-streak-so-far shown for the current session.

This milestone is deliberately **web-first**: the cheapest shell to prove the `Core/` is fun.
What milestone 1 does *not* need, and therefore what must not appear in the diff yet: EDGE, the
native app project, License Plate, accounts, or streak persistence across sessions/devices.

## 5. Kill criteria

Written now because now is the only time they're honest.

- **The itch isn't real:** if *I* don't reach for this during real idle moments in the
  ⟨confirm: 3⟩ weeks after milestone 1, the need it claims to scratch doesn't exist. Stop.
- **Upkeep too high:** if keeping it alive costs more than ⟨confirm: 2⟩ hrs/week on average
  once past milestone 1, it's not the low-stakes project it was meant to be. Stop.
- **The loop isn't fun:** if the one-letter-change graph turns out to be full of dead ends or
  trivially easy, and dictionary/tuning passes don't fix it, the game doesn't work. Stop.
- **Native half specifically:** if EDGE forces a hybrid-webview workaround for the play
  surface *and* it feels bad, kill the native target and ship web-only. (Scope-kill of the
  native half — not the whole project.)

---

*Re-read the kill criteria out loud at each milestone. At end (shipped or killed), append one
paragraph on which sections were wrong — it feeds the next brief.*

*Pairs with: `OWNED-DIFF.md` (§3 is its budget) · `DO-NOT-BUILD.md` (§2 seeds it) · dependency-tax (NativePHP purchase).*
