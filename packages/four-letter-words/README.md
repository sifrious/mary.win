# four-letter-words

The word-chain game. This package is the **framework-free heart** of the game: pure PHP,
no Laravel, no I/O. (Design notes: `SPEC.md` / `STATE.md` / `INTERACTION.md`.)

## Layout
- `src/Core/` — pure logic (the tar-pit functional core). Same inputs → same output; no
  clock, no randomness, no database, no network. Unit-tested under `tests/`.

## How it's wired (milestone 1)
Autoloaded by the host app (mary.win) via a PSR-4 mapping
(`FourLetterWords\ => packages/four-letter-words/src/`) rather than a formal Composer
path-repository. Formalising it — the package's own `composer.json` + a path repo — is
deferred until the native (NativePHP/EDGE) app becomes a second consumer, per the brief's
owned-diff budget. Until then this stays a module-in-place with a pure boundary.

## What is NOT here
Persistence (Eloquent, migrations), the Livewire/EDGE composer UI, HTTP — all of that is
the imperative shell and lives outside this package. The core only receives values and
returns values.
