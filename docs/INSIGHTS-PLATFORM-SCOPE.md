# Scope: Kite Insights Platform (free analysis + paid AI insights)

Status: **scoping draft** — decisions below are locked; open questions at the end need answers before build.

## Decisions locked
1. **Tier split** — Free = deterministic extraction (structure + term/vocabulary/reading-list), what Kite
   runs today. Paid = AI insights.
2. **Topology** — Two services, both separate from the `mary.win` Laravel app: a free **Code Analysis
   Service** and a paid **Insights Service**. Laravel orchestrates, owns UI + billing + entitlement.
3. **Billing** — Stripe **subscription** via Laravel Cashier.
4. **Paid analysis depth** — **RAG**: build a semantic index over the repo and answer/analyze against it.

## The "no local download" boundary — reconciled
"No local download" means **no git clone, no package install, no executing the code** — the analysis
works purely against the cloud codebase over the GitHub API.

| | Reads code? | Retains code? | Clones / installs? |
|---|---|---|---|
| Free (Code Analysis) | Yes, over GitHub API (trees + contents) | No — extract terms/structure, discard blobs | No |
| Paid (Insights / RAG) | Yes, over GitHub API | **Yes — chunked text + embeddings in a vector DB** | No |

So the paid tier honors "no clone/install," but it **does persist a semantic index**. That retention is the
thing to get right (see § Data & privacy) — especially for private repos, which Kite can reach because the
user's OAuth token carries `repo` scope.

## Architecture

```mermaid
flowchart LR
  U[User] --> L[mary.win — Laravel<br/>UI · GitHub OAuth · Cashier/Stripe<br/>entitlement gate · orchestration]
  L -->|POST /analyze  (free, sync)| A[Code Analysis Service<br/>deterministic, no LLM<br/>reads GitHub API, discards blobs]
  L -->|subscribed only:<br/>index / insights / query| I[Insights Service<br/>RAG: chunk → embed → retrieve → LLM<br/>langchain-anthropic]
  A -->|structure + terms JSON| L
  I -->|deep insights JSON| L
  I --> V[(Vector DB<br/>per-repo namespaces)]
  L --> S[(Stripe)]
  A -.reads.-> GH[(GitHub API)]
  I -.reads.-> GH
```

Three components:
- **mary.win (Laravel)** — everything user-facing and money-facing: auth, repo management, reading list,
  the "Get more insights" CTA, Stripe/Cashier billing, the subscription **entitlement gate**, and the
  jobs that orchestrate both services. It is the only caller of both services and the only place billing
  logic lives — the services stay dumb and trust Laravel (they're not user-facing).
- **Code Analysis Service (free)** — the deterministic engine currently living in
  `app/Services/{RepositoryAnalysisService,RepositoryFileFrequencyService,ReadingAnalysisService}`,
  extracted into a standalone service. Given `{full_name, branch, token}` it reads the tree + blobs over
  the GitHub API, categorizes files, extracts function/vocabulary terms, and returns JSON. No LLM.
- **Insights Service (paid, RAG)** — the LangChain service from `PYTHON-ANALYZER-HANDOFF.md`, upgraded
  from metadata-only to retrieval-augmented: it ingests the repo (API → chunk → embed → vector store),
  then runs retrieval-grounded analysis and (later) Q&A. `langchain-anthropic` / Claude, as before.

## Component 1 — mary.win (Laravel) changes

- **Billing:** `composer require laravel/cashier`; Stripe products/prices; `Billable` on `User`; a
  pricing + checkout page; Cashier webhooks. Gate paid features with `subscribed()` (middleware/policy).
- **Entitlement gate:** Laravel is the gatekeeper — it only calls the Insights Service for a subscribed
  user, and passes the shared bearer secret. No entitlement logic in the services.
- **CTA / UX:** on the repository structure page (already has an "AI Analysis" plate), free users see a
  **"Get more insights →"** CTA → pricing/checkout. Subscribed users see a **"Build deep insights"**
  action that kicks off async RAG indexing + analysis, with a status indicator, then renders the result.
- **Async:** RAG indexing takes minutes → this is the feature's first real background work. Add a queued
  job + a `repository_insights` status/state row + status polling in the UI. (The free flow stays
  synchronous.) Requires a running queue worker — call that out in deploy docs.
- **Data model:** Cashier tables (`subscriptions`, customer columns) + a new `repository_insights`
  (`repository_id`/`repository_to_read_id`, `user_id`, `index_id`, `status`, `data` JSON,
  `indexed_at`) tracking index state and the returned insights.
- **Contracts:** keep the existing `App\Contracts\CodeAnalyzer` boundary for the paid service; add a
  parallel `App\Contracts\RepositoryAnalyzer` boundary for the free service so the extraction can move
  out of Laravel behind an interface (same Null/Http pattern already proven for `CodeAnalyzer`).

## Component 2 — Code Analysis Service (free)

- **Contract (target):** `POST /analyze` `{full_name, default_branch, token, include_terms}` →
  `{file_structure:[…], total_files_count, relevant_files_count, terms:[…]}`. Bearer auth (shared secret).
- **Behavior:** exactly the current Laravel engine's logic (relevant-file filter, categorize, per-language
  term extraction) — reads over the GitHub API, never clones, discards blobs after extraction.
- **Phasing note:** this already works *inside* Laravel today. Extracting it is a refactor with no new
  user value, so it's **Phase 2** (see § Sequencing) — behind the `RepositoryAnalyzer` interface so the
  swap is "new class + env var," touching no controller/view.

## Component 3 — Insights Service (paid, RAG)

- **Contracts (bearer auth, Laravel-only caller):**
  - `POST /index` `{repo_id, full_name, branch, token}` → `{index_id, status:"queued"}`. Async: fetch
    relevant blobs over the GitHub API → chunk (language-aware) → embed → upsert to the vector store in a
    **namespace keyed by repo + owner** so tenants never mix.
  - `GET /index/{index_id}` → `{status: queued|building|ready|failed, progress, error?}`.
  - `POST /insights` `{index_id}` → retrieval-grounded `RepositoryInsights` (summary, architecture,
    patterns, key_functions, study_plan) — richer than metadata-only because it retrieves real code.
  - `POST /query` `{index_id, question}` → grounded Q&A over the codebase. **Later phase.**
  - `DELETE /index/{index_id}` → purge vectors + chunks (unsubscribe / retention / user request).
- **Pipeline:** LangChain document loaders over API-fetched blobs → `RecursiveCharacterTextSplitter`
  (or language splitters) → embeddings → vector store → retriever + `create_retrieval_chain` →
  `with_structured_output(RepositoryInsights)`. Claude (`claude-opus-4-8` default) for synthesis.
- **Async model:** Laravel dispatches a queued job → `POST /index`, polls `GET /index/{id}` until `ready`
  (or the service calls back a Laravel webhook), then `POST /insights`, stores the result, notifies the
  user. Keep the LLM synthesis call inside Laravel's `AI_ANALYZER_TIMEOUT`; the long part is indexing.

## Billing & entitlement flow
1. Free user analyzes a repo (free service, synchronous) → sees structure + terms + the "Get more
   insights →" CTA.
2. CTA → Cashier checkout (Stripe). On success, the webhook marks the user `subscribed`.
3. Subscribed user triggers deep insights → queued job → Insights Service (index → insights) → stored +
   rendered. Gate every paid route/action on `subscribed()`.

## Private-repo risk & controls
RAG turns "read once" into "retain a copy," so the paid tier's sharpest risk is becoming a custodian of
other people's source code. Kite's two repo paths carry very different risk:

- **Own private repos** (`Repository`, "My Code") — the user owns the code and consents. Real risk
  (secrets, breach) but clean authorization.
- **Foreign private repos** (`RepositoryToRead`, reading list) — the user merely has *access* (e.g. an
  employer's repo), not the *right* to have a third party copy/store it. Highest risk, and it's exactly
  the flow Kite encourages. **Recommend: disallow RAG on foreign private repos outright** — the
  authorization isn't the user's to grant.

**Why storage specifically is the risk:**
- **IP custody** — RAG stores chunk *text* (for the LLM context), so the vector DB is a plaintext copy of
  proprietary code. "Embeddings only" doesn't save you: embedding inversion (vec2text-style) can partially
  reconstruct source from vectors.
- **Secrets travel with the code** — `.env`, keys, and credentials get embedded, sent to the embeddings
  provider + Claude, stored, and logged. A store breach becomes *working credentials into customer infra*
  — a supply-chain compromise, not just a data leak.
- **Broad token = blast radius** — the user's `repo`-scoped OAuth token can read *all* their private repos;
  handing it to an external ingestion service widens exposure far beyond the one repo being analyzed.
- **Stale authorization (confused deputy)** — access is checked at ingest; if the user later loses GitHub
  access, a retained index keeps serving them the code unless access is re-verified on every read.
- **Multi-tenant leaks** — one missing tenant filter on a similarity search returns nearest neighbors
  across *all* customers' code.
- **Deletion becomes yours** — copies persist until actively purged from the live store, backups, and
  provider caches (GDPR/CCPA: code carries author emails / PII).
- **Subprocessor exposure** — code leaves for the embeddings provider + Claude; their train-on-inputs and
  retention terms now apply. Disclose subprocessors, verify no-training + zero-retention options, sign DPAs.
- **Concentration risk** — many companies' private code + secrets in one store is a honeypot; one breach is
  a mass IP-and-credential event that pulls SOC 2 / DPA expectations forward.
- **Prompt injection** — retrieved code/comments are untrusted input to the LLM (especially in Q&A).

**Controls (in impact order):**
1. **Public-repos-only at launch;** if private is added later, **own-private-only** (never foreign).
2. **Ephemeral indexing** — build index → analyze → **delete**; keep the derived insights, not the code.
   Fits "no local download," shrinks the blast radius, trades re-index cost for safety. Default for private.
3. **Secret-scan on ingest** (gitleaks/trufflehog-style); skip/redact `.env`, key files, and detected
   secrets *before* embedding.
4. **GitHub App + per-repo, read-only, fine-grained tokens** for ingestion — not the broad OAuth token.
5. **Re-verify GitHub access on every read**, not just at ingest.
6. **Hard tenant isolation** — namespace per user+repo, server-enforced tenant filter on every query,
   encryption at rest (ideally per-tenant keys).
7. **Retention + deletion plumbing** — TTL; delete on unsubscribe / access-revocation / request; propagate
   to backups + provider caches; a "delete my index" control.
8. **Explicit consent + ToS** attesting the user is authorized to ingest this specific repo.
9. **Cost guardrails** — embedding + vector storage is real per-repo cost the subscription must cover; cap
   repo size / file count per index and log what's skipped.

**Recommendation:** launch **public-repos-only**. Add private later as own-private-only + ephemeral +
secret-scanned + fine-grained-token. Keep foreign private repos off the table indefinitely.

## Sequencing (get value fastest)
- **Phase 1 — monetize the AI tier.** Cashier/Stripe + subscription gate + "Get more insights" CTA +
  the paid Insights Service (RAG) + async job/status UI. Free extraction stays in Laravel (already works).
  This ships the whole free→paid loop.
- **Phase 2 — extract the free engine** into the standalone Code Analysis Service behind the
  `RepositoryAnalyzer` interface. Pure refactor; no user-visible change.
- **Phase 3 — Q&A over the index** (`/query`), re-index on new commits, richer insight rendering.

## Open questions (resolve before Phase 1 build)
1. **Private repos in the paid tier** — confirm **public-repos-only at launch** (recommended). If private
   is added later: own-private-only + ephemeral + secret-scanned; **never** foreign private repos. (See
   § Private-repo risk & controls.)
2. **Vector DB** — pgvector (reuse Postgres; note Kite is sqlite in dev today), or a managed store
   (Qdrant / Pinecone / Chroma)? Drives infra + cost.
3. **Embedding model** — Anthropic has no embeddings API; pick an embeddings provider (e.g. Voyage, which
   Anthropic recommends, or OpenAI). This is the one place a non-Claude provider enters — confirm it's OK.
4. **GitHub App vs user OAuth token** for ingestion — recommended (App) but adds setup.
5. **Subscription shape** — single plan, or tiers (e.g. N indexed repos / month, seat vs usage)? Any
   free-trial or per-repo credit on top of the subscription?
6. **Re-index triggers** — on demand only, or auto on new commits (webhook)?

## Non-goals (for now)
GitLab/Bitbucket, team/org plans, multi-LLM-provider routing, running/executing the analyzed code,
IDE integration. Revisit after Phase 1 proves the loop.
