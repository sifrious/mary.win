# Four Letter Words API

Implemented on the MME-3959 branch. Deployment is pending.

`GET /api/v1/four-letter-words/metadata` reports the rules version and SHA-256 of the exact canonical dictionary bytes. The dictionary license remains unresolved. No downloadable dictionary artifact is published by this API.

`POST /api/v1/four-letter-words/validate-run` accepts `rules_version`, `dictionary_version`, and a chronological `submissions` array. Read the current versions from metadata. Each entry must normalize to four ASCII letters. Empty submissions represent a run before the first word.

The response contains `status`, `streak`, `accepted_words`, and `loss_reason`. The final submission may be a losing word. Submissions after a loss return HTTP 422. A malformed word also returns 422. A mismatched version returns 409 with current metadata. The endpoint limits each request to 5,000 submissions and 30 requests per minute. These limits protect the API and do not change game rules.

`app/Games/FourLetterWords/ValidateRun.php` replays submissions through `packages/four-letter-words/src/Core/Rules.php`. The API never accepts a claimed score as authoritative. `app/Http/Controllers/Games/FourLetterWordsController.php` handles HTTP validation and response formatting. There is no separate CLI implementation.

Validation is anonymous and stores nothing. Successful validation is not proof that a human played the run. Account ownership, durable run uploads, retry idempotency, and mary.is token verification remain pending. The web game continues to use the same core and has no dependency on these endpoints.

## Version changes

`app/Games/FourLetterWords/Release.php` defines `RULES_VERSION`. Increment the version whenever accepted words, transitions, loss precedence, or scoring behavior changes. Dictionary identity changes automatically when dictionary bytes change. A version identifies compatibility; it does not permit a mobile app to download and execute arbitrary PHP.

## Glossary

A submission is a candidate word sent to the rules. A Play is an accepted submission in the existing glossary at `packages/four-letter-words/docs/STATE.md`. A losing submission never becomes a Play.

A rules version identifies the accepted game behavior. A dictionary version is the SHA-256 of `packages/four-letter-words/resources/words.txt`. A validation response describes a replay, not a stored Run or account-owned score.

No new environment variables are required. Rate and request limits live in `routes/api.php` and `app/Http/Controllers/Games/FourLetterWordsController.php`.

## Checks

`tests/Feature/Games/FourLetterWordsApiTest.php` covers dictionary identity, normalized submissions, existing loss precedence, empty runs, malformed words, submissions after loss, incompatible versions, and untrusted caller fields. Existing core and web tests continue to define gameplay behavior.
