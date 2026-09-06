# SPEC — License Plate Game

## Purpose
An arcade game on mary.win: the player is shown a randomly generated license plate themed
to one of the 50 U.S. states, and scores by building real words that contain the plate's
letters in their original order.

## Requirements
| ID  | E/A | Requirement |
|-----|-----|-------------|
| R1  | E   | When a round begins, the system displays a plate on a background that vaguely represents one of the 50 U.S. states. |
| R2  | E   | When a round begins, the system generates a plate whose arrangement and counts of letters and numbers match the chosen state's standard driver-plate format (e.g. Pennsylvania = 3 letters then 4 numbers). |
| R3  | E   | When a round begins, the letters and numbers on the plate are randomly chosen but valid for that state's format, so different rounds can show different plates. |
| R4  | E   | When the player selects a specific state, the round uses that state's background and format. |
| R5  | E   | When the player has not selected a specific state, the system picks a state at random; if a letter-count preference is set, only states matching it are eligible. |
| R6  | E   | The player may set either a specific state or a letter-count preference, but not both — choosing one makes the other option unavailable. |
| R7  | E   | The letter-count preference may exclude plates with exactly two letters. |
| R8  | E   | The letter-count preference may exclude plates with more than two letters. |
| R9  | E   | When the player submits a word, the system accepts it only if the plate's letters appear within it as a subsequence in their original left-to-right order (letters may be added before, between, and after the plate letters, but the plate letters are never reordered). |
| R10 | E   | When the player submits a word, the system accepts it only if it is a real word found in the game's word list. |
| R11 | E   | When "Rob's Rule" is enabled, the system additionally requires an accepted word to begin with the plate's first letter and end with the plate's last letter. |
| R12 | E   | Rob's Rule is available only for plates with three or more letters; for shorter plates it cannot be enabled. |
| R13 | E   | When the player submits a word that fails any active rule (R9, R10, or R11), the system rejects it and reports which rule it failed. |
| R14 | E   | When the player submits an accepted word, the system records it and awards it 1 point. |
| R15 | E   | When an accepted word has eight or more letters, the system awards it 2 points instead of 1. |
| R16 | E   | When a round ends, the system reports the round's total score and the words the player played. |
| R17 | E   | When a round ends, the system does not reveal any word the player did not play. |
| R18 | E   | When the player enables the timer and its time runs out, the system ends the round and scores it. |
| R19 | E   | The plate's numbers and decorative styling never affect whether a word is valid or how it scores — they are decoration only. |

## Out of scope
- Multiple named players, turn-taking, or a declared winner.
- A cumulative score or history carried across rounds or visits (each round scores on its own).
- Vanity / specialty / commercial / temporary plate formats — only each state's *standard driver* plate.
- Territories beyond the 50 states (D.C., provinces, tribal, government, military plates).
- Photo-accurate reproductions of real plate artwork; backgrounds are "vaguely representing," not exact.

## Assumptions
- **"Real word"** is judged against the ENABLE word list (public domain, ~172k entries); proper nouns and abbreviations are excluded.
- **The bonus** is a flat length threshold: any word of 8+ letters is worth 2 points; length beyond 8 gives no extra. The game deliberately never computes or reveals a "best possible" word (R17).
- **When neither a state nor a preference is set**, the random state is drawn from all 50.
- **Round shape**: one plate at a time; the player submits one or more words, then the round ends (by the player finishing, or by the timer in R18). The timer is off by default.
- **Plate format data** is "accurate-ish": county-coded and numeric-only states are approximated with a plausible letter-bearing shape so every plate is playable.

## Vocabulary
- **state**: one of the 50 United States, each with its own background and plate format.
- **background**: the state-themed styling the plate is drawn with (decoration).
- **plate format**: the fixed pattern of letters and numbers on a state's standard driver plate (e.g. `LLLNNNN`).
- **plate letters**: the alphabetic characters on the generated plate, in their left-to-right order.
- **plate numbers**: the numeric characters on the plate; decoration only.
- **subsequence in order**: the plate letters appear in a word in the same order, not necessarily adjacent (plate `MZE` → `aMaZEd`).
- **real word**: an entry in the game's word list.
- **bonus word**: an accepted word of eight or more letters, worth 2 points instead of 1.
- **Rob's Rule**: an optional setting (plates of 3+ letters only) requiring accepted words to begin with the plate's first letter and end with its last letter.
- **letter-count preference**: the player's setting for how many letters a randomly chosen plate may have; mutually exclusive with picking a specific state (R6).
- **timer**: an optional per-round time limit that ends the round when it expires.
