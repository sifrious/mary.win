# license-plates

The License Plate Game for the mary.win arcade: a state-themed plate is generated, and the
player builds real words that contain the plate's letters **in their original order**
(letters may be added before, between, and after). 1 point per word, 2 points for a word
of 8+ letters; the game never reveals a word the player didn't play.

Ported from a standalone tar-pit prototype (JS) into this app's game architecture,
mirroring `packages/four-letter-words`:

- **docs/** — SPEC.md (19 requirements) and STATE.md (essential state), the artifacts the
  code is downstream of. Changes go through them first.
- **src/Core/** — the pure core: `Rules` (start a round, judge a word), `Settings`
  (transitions enforce C1–C3), `Round`/`Plate`/`Score` (derivations), `Rng` (deterministic
  seed stream). No I/O, no clock, no randomness — time and the seed are parameters.
- **resources/** — reference data: `state-formats.php` (the 50 patterns; entries marked
  `approx` are best guesses awaiting correction) and `words.txt` (ENABLE, ~172k words).
- **tests/** — Pest tests named for the SPEC requirement or STATE constraint they trace.

The imperative shell lives in the app: `App\Games\LicensePlates\*` (file loaders),
`App\Livewire\Games\LicensePlates` (the component — the feature's only clock/seed reads),
and the `plate-game` layout + `livewire.games.license-plates` view (Alpine paints what the
server returns; all rules are server-side).

Run the tests: `./vendor/bin/pest --testsuite=LicensePlates` (core) and
`./vendor/bin/pest tests/Feature/Games/LicensePlatesTest.php` (shell).
