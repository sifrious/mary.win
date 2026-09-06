# Recovered production source

Recovered from mary.win deployment `depl-a2626cb2-47f1-4aeb-a0a3-5a665bcf3afe`, prod commit `893869c12a48f172c30460a9e199a2390c4c08a0`. Current GitHub branches do not contain that commit. This independent root records the recovered application rather than claiming ancestry that cannot be verified. `docs/recovery/provenance.json` records the original file hashes.

Excluded environment files, credentials, dependencies, runtime databases, uploads, caches, and compiled assets. Recreate runtime directories and install locked dependencies locally. The original source recovery remains separate from implementation edits. The recovery history is joined to the existing main history by merge commit `9bf387a`, preserving both histories.

## Local verification

Run `composer install`, `cp .env.example .env`, `php artisan key:generate`, `npm ci --ignore-scripts`, and `npm run build`. The example uses an in-memory database and file sessions for guest game checks. No production data is needed. Run `php artisan serve --host=127.0.0.1 --port=8127` and open `/games/four-letter-words`.

MME-3958 changes the composer only, preserving the PHP rules and dictionary. `npm run test:game-input` checks number selection against the production JavaScript. `php artisan test --testsuite=FourLetterWords` and `php artisan test tests/Feature/Games/FourLetterWordsTest.php` verify core and HTTP/component behavior.

Verified locally: the full recovered PHP suite passes 166 tests with 633 assertions, including 37 Four Letter Words core tests and nine game feature tests. Five JavaScript interaction tests and the Vite build also pass. Browser keyboard checks confirm third-box highlighting and CARE to CARD through number selection. Physical phone keyboards have not been checked.

Before release, constrained dependency updates fixed the reported npm and Composer advisories. Both audits report zero vulnerabilities. The full PHP suite, JavaScript tests, and asset build pass with the updated locks. Production deployment verification follows the main push.
