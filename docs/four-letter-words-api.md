# Four Letter Words API and account saves

The shared implementation remains in `packages/four-letter-words/src/Core/`. `app/Games/FourLetterWords/ValidateRun.php` replays submissions through those rules. No score supplied by a client is trusted.

## Version and validation endpoints

`GET /api/v1/four-letter-words/metadata` returns the rules version and SHA-256 of the canonical dictionary bytes. The dictionary license remains unresolved; this API publishes no downloadable dictionary artifact.

`POST /api/v1/four-letter-words/validate-run` accepts `rules_version`, `dictionary_version`, and a chronological `submissions` array. Each entry must normalize to four ASCII letters. An empty array represents a game before the first word. The response contains `status`, `streak`, `accepted_words`, and `loss_reason`. The last submission may lose. Submissions after a loss or malformed words return 422. Unsupported versions return 409 with current metadata. Requests allow at most 5,000 submissions and 30 requests per minute. These transport limits do not change game rules.

## Account-owned runs

`PUT /api/v1/four-letter-words/runs/{uuid}` takes the same payload and a Bearer access token issued by mary.is for Four Letter Words. It returns the normalized run and derived score. Repeating a request changes nothing. A later request may append submissions, but cannot shorten or replace saved history. A conflict returns 409. A finished run cannot accept later submissions.

`GET /api/v1/four-letter-words/runs/{uuid}` returns that account's saved submissions and versions, or 404. The same UUID can belong to different accounts without exposing or overwriting either account's run.

`app/Http/Middleware/AuthenticateGameAccount.php` calls the reusable `AccountTokenVerifier` at mary.is for every request. Wrong products, suspended accounts, and rejected tokens fail closed. An outage returns 503 without changing saved progress. No credentials or central account records are stored with a run. Caller-provided account IDs are ignored. A valid run is not proof that a human played it.

`app/Games/FourLetterWords/RunStore.php` contains persistence behavior shared by HTTP and browser controllers. `database/migrations/2026_09_09_000000_create_four_letter_words_runs.php` adds one table with a unique account/run pair. Transactions and row locks serialize competing updates. Scores remain derived from submissions. Existing history retains its version identity after a release changes; unsupported old versions cannot resume or append through the current validator.

## Browser play

`GET /games/four-letter-words/play` is a complete HTML form game. Guest play needs no account or JavaScript. The tile game links to this separate play mode and includes a no-JavaScript link. Moving between modes does not transfer the current run.

`GET /auth/mary` starts PKCE sign-in. `GET /auth/mary/callback` consumes the encrypted pending attempt once, checks the callback and account, and rotates the browser session. `app/Games/Accounts/BrowserAccount.php` keeps encrypted app credentials in server-side session storage, refuses cookie session storage, and uses `HostedAuthClient` for refresh and disconnection. All game-account browser routes use session locks. Responses prevent caching and referrer leakage.

Signing in does not silently claim guest history. The player explicitly saves the current run. Once saved, that run cannot be saved under another account. The saved-runs page lists the current account's 50 most recent runs and can resume a selected run. Opening one explicitly replaces current browser progress. Logout clears local credentials even if remote disconnection cannot be confirmed. A lost refresh response clears the consumed credential and requires sign-in again.

## Configuration

| Key | Purpose and consumer |
|---|---|
| `GAMES_ACCOUNTS_ORIGIN` | HTTPS auth origin, default `https://mary.is`; `config/games.php`, `AuthenticateGameAccount`, and `BrowserAccount`. No path, query, credentials, or fragment is accepted. |
| `GAMES_OAUTH_CLIENT_ID` | Public Four Letter Words browser client ID registered at mary.is. Blank disables the sign-in link. Never a service or provider secret. |
| `GAMES_OAUTH_CALLBACK_URI` | Exact registered callback, default `https://mary.win/auth/mary/callback`. Native applications register separate return URIs. |
| `SESSION_DRIVER` | Use `database` in production, with the existing sessions table. Cookie sessions cannot hold game credentials. |
| `SESSION_SECURE_COOKIE` | Set true in production to restrict session cookies to HTTPS. |
| `APP_KEY` | Existing Laravel key encrypts game credentials inside the server session. Preserve the key. |

The Composer artifact `packages-dist/accounts-client.zip` is generated from `dep-accounts-client` using its `bin/export-package.py`. The lockfile records its checksum. No sibling source directory is needed in production.

## Glossary and version policy

A submission is a candidate word. A Play is an accepted submission in `packages/four-letter-words/docs/STATE.md`; a losing submission never becomes a Play. A saved run includes the accepted history and any final losing submission. An account ID is the opaque identity returned by mary.is, never an email address or a local user ID.

`Release::RULES_VERSION` identifies game behavior. Increment it when accepted transitions, loss precedence, or scoring changes. A dictionary version is the SHA-256 of `packages/four-letter-words/resources/words.txt`. Dictionary identity changes automatically with its bytes. Version metadata does not authorize a mobile app to download and execute arbitrary PHP.

## Checks and release limits

Game API and browser tests cover replay, loss rules, stale versions, retries, account isolation, revocation, outages, callback replay, encrypted credentials, explicit ownership, saved-run resume, and complete HTML play. `tests/Feature/Games/RunConcurrencyTest.php` exercises competing processes on a disposable PostgreSQL database. Production uses MySQL 8.4, so PostgreSQL coverage is not MySQL concurrency proof.

Mobile extraction still needs dictionary provenance, native secure storage and return-link registration, and iOS/Android keyboard verification. Production end-to-end sign-in must be recorded separately from mocked HTTP tests.
