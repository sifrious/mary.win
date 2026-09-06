# mary.win

mary.win hosts web games, including Four Letter Words and License Plate Game, alongside the existing site tools.

The application source was recovered from the running production deployment after its `prod` commit disappeared from the repository history. The recovery branch joins the existing `main` history without replacing it.

Four Letter Words accepts number keys `1` through `4` to select a letter tile. The next letter replaces that tile.

See `docs/four-letter-words-plan.md` for the shared game API and mobile extraction plan. mary.win will own game rules and dictionaries. mary.is will own shared accounts and sign-in.

## Local checks

Install the locked Composer and npm dependencies, copy `.env.example` to `.env`, and generate a local application key. Run `php artisan test`, `npm run test:game-input`, and `npm run build` before releasing changes.

The application license does not establish the provenance or redistribution rights of `packages/four-letter-words/resources/words.txt`. That investigation remains open before mobile distribution.
