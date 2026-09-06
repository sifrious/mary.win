# STATE — License Plate Game

The smallest set of data the game must remember. Two relations are **immutable reference
data** (loaded by the shell, never changed by play); the rest is the live round, held by
the Livewire component for the duration of a visit. Every score and status is Derived —
never stored.

## Essential relations

### StateFormat  *(immutable reference data — `resources/state-formats.php`)*
| field   | type                       | notes |
|---------|----------------------------|-------|
| code    | string, 2 letters (`"PA"`) | key |
| name    | string (`"Pennsylvania"`)  | display label |
| pattern | string over `{L, N}`       | arrangement and counts of letters/numbers, in order |
Key: `code`. Source: R1, R2, R4, R5. The background styling is derived from `code` (decoration).

### Word  *(immutable reference data — `resources/words.txt`, the ENABLE list)*
| field | type                      | notes |
|-------|---------------------------|-------|
| text  | string, lowercase letters | key |
Key: `text`. Source: R10. "Real word" means membership here.

### Settings  *(one per player session)*
| field                        | type           | notes |
|------------------------------|----------------|-------|
| selected_state_code          | string \| null | `null` = random; references `StateFormat.code` |
| exclude_two_letter           | bool           | R7 |
| exclude_more_than_two_letter | bool           | R8 |
| robs_rule_enabled            | bool           | R11 |
| timer_enabled                | bool           | R18 |
| timer_seconds                | int \| null    | meaningful only when `timer_enabled` |
Source: R4–R8, R11, R18.

### Round  *(one per player session; null before the first plate)*
| field         | type      | notes |
|---------------|-----------|-------|
| state_code    | string    | references `StateFormat.code` |
| plate_letters | string    | the letters in left-to-right order — outcome of the seed draw |
| plate_numbers | string    | the numeric characters — decoration only (R19) |
| started_at    | int (ms)  | clock reading when the round began (shell input; timer only) |
| ended         | bool      | true once the player finishes by choice (human action, not derivable) |
| played        | list of string | the accepted words this round, in order, unique |
Source: R1–R3, R9, R14, R16, R18.

## Derived data (computed, never stored)
| name                  | definition                                                                                                    | needed by |
|-----------------------|---------------------------------------------------------------------------------------------------------------|-----------|
| letter_count(state)   | the number of `L` characters in that state's `pattern`.                                                        | R7, R8, R12 |
| eligible_states       | the StateFormats whose `letter_count` satisfies the active letter-count preference.                             | R5, R7, R8 |
| robs_rule_available   | true exactly when the round's `plate_letters` has length ≥ 3.                                                   | R12 |
| is_expired            | `timer_enabled` and `(now − started_at) ≥ timer_seconds`, where `now` is passed in.                             | R18 |
| round_over            | the round's `ended` flag is true or `is_expired` is true.                                                       | R13, R18 |
| seconds_remaining     | when `timer_enabled`, `max(0, timer_seconds − ⌊(now − started_at)/1000⌋)`; countdown display only.              | R18 |
| is_valid(word)        | `word` is in Word, `plate_letters` is an in-order subsequence of `word`, and — if Rob's Rule is active — `word` starts with the first plate letter and ends with the last. | R9–R11, R13 |
| word_score(w)         | 2 if `length(w) ≥ 8`, otherwise 1.                                                                              | R14, R15 |
| round_score           | the sum of `word_score` over every played word.                                                                 | R14–R16 |

## Constraints
- **C1**: A specific state and a letter-count preference are mutually exclusive — if `selected_state_code` is set, both preference flags are false (R6).
- **C2**: `timer_seconds` is set and greater than 0 if and only if `timer_enabled` is true (R18).
- **C3**: `robs_rule_enabled` may be turned on only when the current plate (if any) has ≥ 3 letters (R11, R12).
- **C4**: A Round's `plate_letters` and `plate_numbers` match the counts and arrangement of its state's `pattern` (R2, R3).
- **C5**: `selected_state_code` (when set) and `Round.state_code` each reference an existing `StateFormat.code` (R4, R5).
- **C6**: Every played word satisfies `is_valid` for the round (R9–R11, R14).
- **C7**: No two played words in a round are the same — a word scores at most once (R14).
- **C8**: StateFormat contains exactly the 50 U.S. states, with unique `code`s (R1).
- **C9**: Every state's `pattern` contains at least one `L`, so every plate has at least one letter to build on.
