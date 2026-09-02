# Email list

Server-rendered signup for the mary.win email list. Ticket: MME-1338.

## Product decisions

The ticket left four decisions open. They were resolved as follows, and the code
follows these choices literally.

| Decision | Choice | Reason |
| --- | --- | --- |
| System of record | The local `mailing_list_subscriptions` table | Consent evidence stays inside the application, survives a provider outage, and does not have to be migrated if the provider is later replaced. Any provider is a downstream mirror. |
| Opt-in model | Confirmed (double) opt-in | A public form otherwise lets anyone subscribe somebody else's address. Confirmation makes consent attributable to the address owner. |
| Consent copy and placement | The sentence in `config/mailing-list.php` (`consent_text`), beside a required checkbox, in a section at the foot of the home page | One consent string, stored verbatim per subscription, so what a person agreed to can always be shown even after the site wording changes. |
| Duplicate / unsubscribed / outage behaviour | See the table below | — |

## Behaviour

| Situation | What happens | What the visitor is told |
| --- | --- | --- |
| New address | Row created as `pending`, confirmation email sent | "Check your inbox…" |
| Address already `subscribed` | Nothing changes; no second row, no second email | "That address is already subscribed." |
| Address still `pending` | Confirmation token is reissued and re-sent; still one row | "We sent another confirmation link." |
| Address previously `unsubscribed` | Row returns to `pending` with a fresh consent record | "Check your inbox…" |
| Same address in different case or with whitespace | Normalized to one row (unique index on `email`) | As above |
| Mail transport unavailable | Row is saved as `pending`; the failure is logged | "We saved your request, but could not send the confirmation email just now." — no false success |
| Provider sync fails at confirmation | Subscription is still confirmed locally; `provider_sync_failed_at` and `provider_sync_error` are set for retry | "You are subscribed" (which is true — the local list is the system of record) |
| Confirmation link expired or unknown | Nothing changes | "That confirmation link is no longer valid." |
| Unsubscribe link opened | Confirmation page only — a `GET` never opts anybody out | "Unsubscribe from the email list?" |

## Consent and unsubscribe semantics

* Consent requires an explicit ticked checkbox plus opening the emailed link.
* Each row stores `consent_text`, `consent_at`, and `consent_ip` as evidence.
* Unsubscribe links are Laravel **signed URLs** — no additional secret is stored,
  and rotating `APP_KEY` invalidates every outstanding link.
* Opting out is a `POST`, so mail scanners and link prefetchers cannot
  unsubscribe someone by merely fetching the URL.
* Confirmation tokens are stored as SHA-256 digests; a database leak does not
  yield working links.

## Accessibility

The repository has no `docs/STYLE.md` yet (the ticket's acceptance criteria
reference one). Until it exists, the form targets WCAG 2.2 AA directly:

* Works with JavaScript disabled — a plain `<form method="POST">`.
* Every control has a visible `<label>`; the email field has a persistent hint
  wired through `aria-describedby`.
* Errors are announced by a `role="alert"` summary that receives focus via
  `tabindex="-1"` + `autofocus`, with in-page links to each failing field, plus
  per-field messages and `aria-invalid`.
* Focus is never suppressed; `:focus-visible` draws a 3px outline with offset.
* Text/background pairs are ≥ 7:1 in both light and dark schemes.
* `prefers-reduced-motion: reduce` disables transitions.
* `forced-colors: active` redraws borders and the focus ring with system colors
  so nothing disappears in high-contrast mode.

## Bot resistance

Both measures work without JavaScript:

1. **Honeypot** — a field hidden from view and from the accessibility tree
   (`aria-hidden`, `tabindex="-1"`, clipped). Any value rejects the submission.
2. **Minimum fill time** — an encrypted render timestamp; submissions arriving
   sooner than `MAILING_LIST_MIN_FILL_SECONDS` are rejected. The value is
   encrypted, so it cannot be forged or replayed with an arbitrary time.

The route is additionally rate limited (`throttle:10,1`).

Escalate only if these prove insufficient in production; a CAPTCHA is a real
accessibility cost and is not warranted by observed abuse today.

## Environment variables

None are secret. No credentials are required while `MAILING_LIST_SYNCER=null`.

| Variable | Default | Purpose |
| --- | --- | --- |
| `MAILING_LIST_SYNCER` | `null` | Downstream provider mirror. `null` = local database only. |
| `MAILING_LIST_CONFIRMATION_TTL_HOURS` | `48` | Confirmation link lifetime. |
| `MAILING_LIST_HONEYPOT_FIELD` | `website_url` | Honeypot field name; rename if scrapers learn it. |
| `MAILING_LIST_MIN_FILL_SECONDS` | `2` | Minimum plausible fill time; `0` disables the check. |
| `MAILING_LIST_PRIVACY_URL` | `/privacy` | Target of the privacy link in the consent copy. |

Mail delivery uses the application's standard `MAIL_*` configuration. **A working
mailer is a hard requirement**: with confirmed opt-in, nobody can subscribe while
mail is down. Submissions are still captured as `pending` during an outage, so
nothing is lost, but they cannot be confirmed until mail recovers.

`MAILING_LIST_PRIVACY_URL` points at `/privacy`, which does not exist yet — the
privacy notice is out of scope for MME-1338 and needs its own ticket before
launch.

## Adding a real provider

1. Implement `App\Services\MailingList\MailingListSyncer`, throwing
   `MailingListSyncException` on any provider error.
2. Register it in `AppServiceProvider::MAILING_LIST_SYNCERS`.
3. Set `MAILING_LIST_SYNCER` to that key and add the provider's credentials to
   the environment's secret store (never to the repository).

No calling code changes. An unknown value for `MAILING_LIST_SYNCER` throws at
resolution rather than silently dropping people from the downstream list.

## Operations

* **Export the list:** `select email from mailing_list_subscriptions where
  status = 'subscribed'`. Never send to `pending` or `unsubscribed` rows.
* **Retry failed provider syncs:** rows where `provider_sync_failed_at is not
  null`. There is no automatic retry yet; add one when a provider is selected.
* **Pending rows** older than the TTL are abandoned signups. They hold an
  unconfirmed address and must never be emailed except for confirmation.
* **Rotating `APP_KEY`** invalidates every outstanding unsubscribe link. Regenerate
  links from `MailingListController::unsubscribeUrl()` when composing mail rather
  than storing them.
