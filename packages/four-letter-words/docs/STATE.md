# STATE — Four Letter Words

## Essential relations

### Run
| field | type | notes |
|-------|------|-------|
| id | identifier | key |
| player_id | reference → host-site account, nullable | null = anonymous player |

Key: `id`. Source: R2 (a run begins), R8 (start again = new run), R22/R23 (a signed-in player's runs).

### Play
| field | type | notes |
|-------|------|-------|
| run_id | reference → Run | part of key |
| position | integer ≥ 1 | part of key; the order the word was played in the run |
| word | text, four letters | the word the player committed at this step |

Key: `(run_id, position)`. Source: R2 (first word), R3 (each successful move), R7 (the ordered log).

### WordList
| field | type | notes |
|-------|------|-------|
| word | text, four letters | key |

Key: `word`. Source: R2–R4, R16. Reference data — loaded once, read-only, never produced by play.

## Derived data (computed, never stored)
| name | definition (plain sentence) | needed by |
|------|------------------------------|-----------|
| current word | the `word` of the highest-`position` Play in the run | R3, R9 |
| streak | the number of Plays in the run | R2, R3, R7 |
| final streak | the streak of a run at the moment it ends — the same count | R4, R5, R6, R7 |
| used words | the set of `word`s among the run's Plays | R6 |
| run log | the run's Plays ordered by `position` | R7 |
| is-a-word | whether a candidate word appears in WordList | R2, R3, R4, R16 |
| one-position-different | whether a candidate differs from the current word in exactly one of four positions | R3, R5 |
| best streak | for a signed-in player, the largest streak over all that player's Runs | R22 |
| word totals / most-played | for a signed-in player, the count of that player's Plays grouped by `word` | R23 |

## Not state (kept out on purpose)
- **letters, cursor** — the composer's scratch (what the four boxes show mid-typing, which is selected). Shell only; discarded on submit. (INTERACTION.md)
- **armed, filled, is-valid** — composer predicates, recomputed on every keystroke, never stored.
- **streak, best streak, per-word counts, "final" / "last" anything** — all derived above; storing any of them would be a cache to defer to the accidental phase only if measured necessary.

## Constraints
- C1: Every Play.word is four letters long.
- C2: Every Play.word exists in WordList.
- C3: Within a Run, the Plays' positions are exactly 1..n — no gaps, no duplicates.
- C4: Within a Run, no word appears in more than one Play. *(R6 — a repeat never becomes a play; it ends the run instead.)*
- C5: Within a Run, the word at position p+1 differs from the word at position p in exactly one of the four positions. *(R3 — the first word, position 1, has no predecessor and is unconstrained beyond C1/C2.)*
- C6: Every non-null Run.player_id references an existing host-site account.
- C7: Only Runs with a non-null player_id are retained beyond the sitting in which they occur. *(Anonymous runs are session-scoped.)*
