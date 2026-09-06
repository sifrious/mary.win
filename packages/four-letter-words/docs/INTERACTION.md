# INTERACTION — Four Letter Words composer

*Shell design, not core. This is the "word composer": a tiny editor for a fixed four-letter
field whose only output to the game is one candidate string, on submit. It holds accidental
(control) state only — nothing here is persisted, and the game core never sees a cursor.
Covers SPEC requirements R9–R22 and R26. The web implementation is in
`resources/views/components/layouts/game.blade.php`.*

## State (ephemeral, per turn)

| Field | Values | Notes |
|-------|--------|-------|
| `letters` | four slots, each empty or A–Z | the word being composed |
| `cursor` | `0 | 1 | 2 | 3 | 'submit'` | exactly one selection at a time |

Derived (advisory, recomputed on every change — the core re-checks everything on submit):
- `filled` = all four slots non-empty
- `word` = the four letters joined, uppercased
- `isWord` = `word` is in the fixed word list
- `changed` = `word` differs from the current word in ≥1 position
- `armed` = `filled && isWord && changed`  → drives R16/R17/R19

## Turn lifecycle

- **Turn 1:** `letters` empty, `cursor = 0`, prompt "enter a word with 4 letters". *(R1)*
- **Turn ≥2:** `letters` pre-filled with the current word, `cursor = 0`. *(R9)* This is why the
  navigation methods exist — you jump to a slot and change it rather than retype.
- **On a successful play:** the played word becomes the current word and stays in the slots;
  `armed` is therefore false (`changed` = false) until the player alters a letter. *(R19)*

## Transitions

| Trigger | Effect | Req |
|---------|--------|-----|
| type a letter | write at `cursor`; `cursor → min(cursor+1, 3)` | R10 |
| backspace | clear at `cursor`; `cursor → max(cursor-1, 0)` | R11 |
| click / tap a slot | `cursor →` that slot | R12 |
| arrow left / right | `cursor →` clamp(cursor∓1, 0, 3) | R13 |
| `1` / `2` / `3` / `4` | select the numbered box from the left, without editing letters or submitting | R26 |
| *(becomes armed)* | `cursor → 'submit'` | R16 |
| Enter / tap submit | if `armed`: emit `word` to the core | R18 |

All letter keys enter letters. Vim-style commands remain deferred. Ctrl, Command, and Alt combinations are left to the browser. No timer anywhere; every transition above is local and
instant, so there is never a keystroke round-trip.

## The one crossing to the core

The composer emits `word` **only** on an armed submit (R18). The core alone decides the
outcome (R3–R6) — the composer's `armed` gate checks *is-a-word* and *changed*, deliberately
**not** the one-position rule or the repeat rule. Those are the player's skill: a two-letter
jump or a repeat is armable, submittable, and **ends the run**. The composer never blocks a
legal-looking-but-losing move; that tension is the game.

## Visual states (tokens from resources/css/winrar.css)

| Slot state | Fill | Outline | Letter |
|------------|------|---------|--------|
| unselected | `--plate-2` | `--hair` | `--ink`, `--font-mono` |
| selected | `--plate` (brighter) | game identity: `--wcol-1`→`--wcol-2` (wgrad-1) | same |

Outline darker than fill darker than background holds in light mode via these tokens (R20);
dark mode inverts through the same variables for free. The selected slot carries the game's
home-page spectrum identity (Four Letter Words = `wgrad-1`, pink→peach).

## Number selection

`selectNumber` maps the displayed box number to the zero-based cursor. Focused hardware input uses `onFieldKey`; unfocused hardware input uses `onKey`. Phone edits reach `type` through `onEdit` or its fallback. Selection uses the existing outline/fill and an accessible label naming the letter, position, and selected state. Digits outside 1 through 4 do nothing. On the loss screen, shortcuts do nothing.

Run `npm run test:game-input` for interaction checks against the production composer. Physical-device keyboard checks remain part of release verification.
