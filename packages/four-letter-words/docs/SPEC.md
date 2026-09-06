# SPEC — Four Letter Words

## Purpose
A single player keeps the longest chain they can of real four-letter words, each made by
changing exactly one letter of the previous word, with no repeats and no clock.

## Requirements

| ID | E/A | Requirement |
|----|-----|-------------|
| R1  | E | When the game starts, the system shows a prompt to enter a four-letter word and four empty letter positions. |
| R2  | E | When the player submits a first word that is a real four-letter word, the system begins a run with a streak of one and shows that word as the current word. |
| R3  | E | When the player submits a word that is a real four-letter word, differs from the current word in exactly one position, and has not been submitted earlier in the run, the system increases the streak by one and shows the submitted word as the new current word. |
| R4  | E | When the player submits a word that is not a real four-letter word, the system ends the run and shows the final streak. |
| R5  | E | When the player submits a word that differs from the current word in a number of positions other than exactly one, the system ends the run and shows the final streak. |
| R6  | E | When the player submits a word already submitted earlier in the run, the system ends the run and shows the final streak. |
| R7  | E | When a run ends, the system shows a "you lost" message, the final streak, and the words played in that run listed from most recent to first. |
| R8  | E | When the player starts again after a run ends, the system returns to the first-word prompt with no used words. |
| R9  | A | When a turn after the first begins, the system pre-fills the letter positions with the current word and marks the first position as selected. |
| R10 | A | When the player types a letter, the system places it in the selected position and marks the next position as selected. |
| R11 | A | When the player presses backspace, the system clears the selected position and marks the previous position as selected. |
| R12 | A | When the player clicks or taps a letter position, the system marks that position as selected. |
| R13 | A | When the player presses the left or right arrow, the system marks the position to the left or right as selected. |
| R14 | A | *(Deferred, milestone 1+)* A vim-style overlay — h/l to move, s to submit. Collides with typing those same letters as word input; needs a command mode to disambiguate, so arrow keys and click carry navigation for now. |
| R15 | A | When a position is selected, the system shows it with a distinct outline and fill color from the unselected positions. |
| R16 | A | When the letter positions come to hold a real four-letter word that differs from the current word in at least one position, the system shows the submit control and moves the selection to it. |
| R17 | A | When the letter positions do not hold such a word, the system hides the submit control. |
| R18 | A | When the submit control is shown and the player presses Enter or clicks/taps the control, the system submits the word held in the letter positions. |
| R19 | A | When a word is successfully played, the system hides the submit control until at least one letter is changed. |
| R20 | A | When shown in light appearance, the system draws each position's outline darker than its fill, and its fill darker than the background. |
| R21 | A | When a letter is shown in a position, the system renders it in a fixed-width (monospace) style. |
| R22 | E | When a signed-in player's run ends with a streak longer than their stored best, the system saves it and shows it as their best streak when they return. |
| R23 | E | When a signed-in player plays a word, the system counts it toward that player's running totals, and can show their most-played words. |
| R24 | A | When a run ends and the player is signed in, the system also shows that player's best streak on the end screen. |
| R25 | A | When a run ends and the player is not signed in, the system shows a prompt to sign up so results can be stored. |

## Out of scope
- Building a sign-in or identity system of the game's own — it uses the host site's existing accounts and adds none.
- Remembering anything across sittings for a player who is not signed in — an anonymous run's summary vanishes on reload.
- Any time limit, timer, or time pressure of any kind.
- Head-to-head or multiplayer play.
- A native mobile build (a later phase; this spec is the shared behavior).
- Configurable or player-editable word lists, or a settings screen for "which words count".
- Automatically detecting that no legal move remains (dead-end detection).
- Words of any length other than four.
- Hints, suggested moves, or showing the player which letters could work.

## Assumptions
- The first word of a run may be any real four-letter word; the one-position-change rule applies only from the second word onward. *(R2 vs R3)*
- "Real four-letter word" is judged against one fixed English word list; its exact source and which entries count is a data decision deferred out of this spec.
- Letters are treated case-insensitively and shown in uppercase.
- "Differs in exactly one position" means both words are four letters long with exactly one differing position — no anagrams, insertions, or deletions count as a move.
- A single illegal submit (non-word, multi-letter change, or repeat) ends the run immediately; there are no lives.
- Two tiers of persistence: an anonymous player sees only the current run's summary (streak + words in order), which vanishes on reload; a signed-in player additionally keeps a best streak and per-word totals that persist across sittings and devices.
- The game implements no login of its own; it relies on mary.win's existing accounts. On the later native build the device stands in for the account (local persistence) — deferred.
- Milestone 1 includes the per-run summary (R7) but not signed-in persistence (R22–R23), keeping accounts and storage out of the first slice.
- The run log is the single record of a run's played words; the used-words check (R6) reads from it rather than from a separate stored set.
- A run ends only by an illegal submit or by the player simply stopping; running out of legal moves is not detected.
- The j and k keys are intentionally unbound; only h and l navigate.

## Vocabulary
- **run**: one continuous chain of words, from the first word until the run ends.
- **streak**: how many words have been successfully played in the current run.
- **current word**: the most recently played word; the next word must change it by exactly one position.
- **used words**: the words already played in the current run; replaying one ends the run. Read from the run log, not stored separately.
- **run log**: the ordered list of words played in a run, shown to the player when the run ends.
- **best streak**: a signed-in player's longest streak across all their runs, kept between sittings.
- **signed-in / anonymous player**: whether the player is using a host-site account (persistence) or not (current run only).
- **letter position** (square): one of the four slots holding a single letter.
- **selected position**: the one position that keystrokes currently act on.
- **submit control** ("select"): the control that submits the word held in the letter positions.
- **armed**: the state in which the submit control is shown, because the positions hold a real word differing from the current word.
- **sitting**: one uninterrupted use of the game before a full reload; the span of an anonymous player's memory (a signed-in player's best streak and totals outlast it).
