# Four Letter Words delivery plan

Accepted 2026-09-06. First delivery is Four Letter Words. License Plate Game and Burdgen follow later.

## Ownership

mary.win is the shared games website and API. Each game retains its own rules. Four Letter Words uses the existing `packages/four-letter-words/src/Core/` and dictionary. mary.win owns runs, scores, and progress linked to an opaque mary.is account ID.

mary.is owns email/password registration, email verification, password reset, GitHub Socialite sign-in, browser sessions, and Passport authorization. Zahir supplies reusable account operations without owning passwords or game data. WorkOS is excluded. Email coincidence never authorizes account linking.

The proposed `sifrious/four-letter-words` application renders SuperNative screens, stores local progress and secure app credentials, and consumes the game API. Offline play uses a versioned build of the canonical rules and dictionary. A changed PHP rule implementation requires an app release; a website cannot silently replace shipped native application code.

## Verified baseline

The mary.win live route `/games/four-letter-words` is served by a Laravel Cloud prod deployment of commit `893869c12a48f172c30460a9e199a2390c4c08a0`, July 30, 2026. The main branch lacked that source at investigation time. Recovery commit `c952b46` records source files and checksums; merge `9bf387a` joins the existing main history. Never include environment secrets, sessions, uploads, runtime databases, caches, or vendor directories in source recovery.

Game source paths in that release:

- `app/Livewire/Games/FourLetterWords.php`
- `app/Games/FourLetterWords/WordList.php`
- `resources/views/components/layouts/game.blade.php`
- `resources/views/livewire/games/four-letter-words.blade.php`
- `packages/four-letter-words/src/Core/`
- `packages/four-letter-words/resources/words.txt`
- `packages/four-letter-words/tests/`
- `packages/four-letter-words/docs/`

The core accepts a dictionary word first, then exactly one changed position per accepted word, without repeats. Streak is accepted chain length. There is no timer. Web progress currently lives in Livewire state; signed-in history is not persisted. Dictionary attribution remains unresolved. `sifrious/edge` is a later prototype with runs, plays, and anonymous sync; its unavailable path dependency and old no-account assumptions must not be treated as a deployed contract.

## Delivery order and checks

1. MME-3958: recover source to an isolated branch and implement number selection in the web game. Pressing 1 through 4 selects the matching tile from the left and highlights the selection. The next letter replaces that tile. Digits neither enter the word nor submit or alter the streak. Cover focused and unfocused input, phone input, arrow keys, backspace, modified keys, and loss state. Preserve the live feature and unrelated work. Record baseline versus changed test results.
2. MME-3959: formalize the existing pure core and versioned dictionary data on mary.win. Keep one canonical source; distribute immutable core/data artifacts for offline consumers. Document hashes, source and license, rule versions, and compatibility. Expose thin game-specific metadata and run-validation endpoints around existing rules, without a generic game engine. Preserve a complete HTML play path for the web. Validate full submitted chains, reject incompatible versions, and make retrying a run upload idempotent. Account-bound writes must resolve the caller through mary.is and enforce product/account isolation.
3. MME-3960 with MME-3949: finish the mary.is host. Add regular auth with Laravel password hashing, verification and reset mechanisms, server-rendered forms, rate limits, and session rotation. Verified local accounts and GitHub accounts use the same Passport authorization flow. Require proof of both identities before linking. A password reset revokes prior app credentials and invalidates prior browser credentials. Test duplicate addresses, invalid/expired/replayed reset tokens, unverified accounts, suspended accounts, and account linking. Review existing Socialite/PKCE work and deployment migrations before release. GitHub environment credentials are already present; Passport keys and auth deployment remain pending. Verify production mail delivery before claiming password recovery works.
4. MME-2369: build the standalone SuperNative app after the web shortcut is verified. Reuse core rules and suitable persistence behavior from edge; implement a native editor with the same number shortcuts. Keep guest play available. Store pending PKCE state and rotated credentials in native secure storage, not plain SQLite. Register exact app return links. Preserve run ownership across login/logout and avoid automatically attributing another person's local history to a new account. Test offline starts, resume after termination, failed sync, and safe retry.
5. Release checks: PHP core parity, HTTP authorization and run validation, web keyboard/HTML checks, native component tests, physical iOS/Android soft and hardware keyboard checks, VoiceOver/TalkBack, large text, safe areas, background/resume, cold-start auth returns, refresh rotation and revocation. Confirm the web game remains available. Document results and unresolved device checks without calling simulated tests a native release.

## Dependency decisions

The games API must not use a mobile-supplied account ID as proof of identity. Start with server-side verification against the existing mary.is account endpoint for the Four Letter Words product; fail closed for account writes when auth is unavailable. Offline guest play remains available. Never distribute service credentials, provider secrets, private signing keys, or direct database access to mobile apps.

Before synchronizing historical web records, define explicit account linking. No automatic email merge. Before shipping a dictionary, resolve provenance or choose a reviewed replacement as an explicit game-data change.

NativePHP Mobile v4 supplies SuperNative. Pin an actual compatible release and test Composer compatibility with the existing Laravel 13 Accounts Client. Browser and secure-storage plugins need their applicable license/access. Native deep links and keyboard dispatch require real-device verification. An untested JavaScript event name is not a native implementation.

## Current work

Source recovery and web number selection were pushed to main at `2152951`. Five JavaScript tests, 166 PHP tests with 633 assertions, Vite build, and local browser checks pass. Dependency audits report zero vulnerabilities after constrained updates. Cloud successfully deployed `2152951` from main in deployment `depl-a2af1e40-a257-47cd-8214-b546c760ffcd`. Live browser checks confirmed third-tile highlighting and CARE to CARD at streak two. The environment now explicitly tracks main.

The MME-3959 branch now implements metadata, replay validation, account-owned run uploads, safe retries, and server-rendered browser play with explicit saving and resume. The shared Accounts Client verifies tokens with mary.is. See `docs/four-letter-words-api.md`. Dictionary distribution and the native app remain pending. GitHub Socialite and Passport are already deployed at mary.is; registration remains disabled while outbound mail is unconfigured.

Tracking: https://linear.app/sifirous/issue/MME-2369 and https://linear.app/sifirous/issue/MME-3949.
