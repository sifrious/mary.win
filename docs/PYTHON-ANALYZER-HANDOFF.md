# Handoff: MVP LangChain analysis service for Kite

## Your task
Build a small, standalone **Python HTTP service** that analyzes a code repository with **LangChain**
and returns JSON insights. The Laravel app (`mary.win`) already talks to any AI backend over HTTP
through a clean boundary — you are filling the "real backend" slot behind it. When you're done, setting
two env vars in `mary.win` points it at your service and the "Full analysis" button on a repository's
structure page shows live insights.

- **Build here (new, separate deployable):** `/Users/mme/Projects/kite-analyzer/` (sibling of `mary.win`).
- **Do NOT modify** `mary.win` app code — the integration point already exists. The only `mary.win`
  changes are env values (Step 6) and two optional polish items (see "Optional Laravel follow-ups").
- **Reference:** `mary.win/docs/AI_ANALYZER.md` (the boundary), `mary.win/app/Analysis/HttpCodeAnalyzer.php`
  (the exact client), `mary.win/app/Contracts/CodeAnalyzer.php` (the contract docblock).

> **Why this exists.** Kite extracts a repository's structure + function/vocabulary terms, then optionally
> asks an AI backend "what's interesting/architecturally notable here, and what should I study?" That AI
> call is abstracted behind `App\Contracts\CodeAnalyzer`; today it defaults to a `NullCodeAnalyzer`
> ("unavailable"). LangChain is now the **primary** backend — this service is that backend.

---

## STEP 1 — Match the HTTP contract EXACTLY (this is the integration boundary)

`HttpCodeAnalyzer` (in `mary.win`) is the only thing that will ever call you. Match it precisely — do
not invent a different shape.

**Request the service receives:**
- `POST` to whatever URL `AI_ANALYZER_ENDPOINT` is set to (you choose the path; `/analyze` recommended).
- Headers: `Accept: application/json`, `Content-Type: application/json`, and — **only when
  `AI_ANALYZER_API_KEY` is set in Laravel** — `Authorization: Bearer <that key>`.
- Body: a single top-level `repository` object:

```json
{
  "repository": {
    "name": "laravel",
    "description": "The Laravel Framework.",
    "language": "PHP",
    "github_url": "https://github.com/laravel/framework",
    "file_count": 4210,
    "relevant_files": 512,
    "file_structure": [
      { "path": "README.md", "size": 1024, "sha": "abc…", "category": "readme" },
      { "path": "src/Illuminate/Support/Str.php", "size": 20481, "sha": "def…", "category": "code" }
    ],
    "vocabulary_terms": [
      { "id": 12, "function_name": "collect", "language": "php", "framework": "laravel",
        "implementation": "user-defined", "category": "utility",
        "article_frequency": 87, "user_frequency": 0 }
    ]
  }
}
```

Notes on the payload: `language` may be `null` (Kite doesn't store a primary language for the user's own
repos). `file_structure` is empty (`[]`) for the "terms-only" analysis path. `file_structure[].category`
is one of `readme|docs|config|code|other`. `vocabulary_terms` can be large; `user_frequency == 0` means
"a term the user has never used in their own code" (i.e. a candidate to study).

**Response the service must return:**
- **On success:** HTTP `2xx` with a **JSON object**. Whatever object you return becomes
  `AnalysisResult::data` verbatim and is rendered on the structure page (see Step 3 for the shape).
- **On any failure** (LLM error, timeout, bad key): return a **non-2xx** status (e.g. `401`, `422`,
  `502`, `503`). Laravel catches that and shows an honest "AI analysis unavailable" — so you never need
  to fabricate a result. A JSON body like `{"error": "..."}` is nice for logs but not required.
- **Latency budget:** respond within Laravel's `AI_ANALYZER_TIMEOUT` (default **60s**). Keep the LLM
  request timeout under that (~45s) so a slow model returns a clean 5xx rather than a Laravel-side timeout.

---

## STEP 2 — Stack

- **Web:** FastAPI + Uvicorn (async, Pydantic request/response validation, trivial to test).
- **LLM orchestration:** LangChain. Default provider **Claude via `langchain-anthropic`**
  (`ChatAnthropic`, which wraps the official `anthropic` SDK) — authenticates with `ANTHROPIC_API_KEY`.
  Default model **`claude-opus-4-8`** (Anthropic's most capable Opus-tier model). Make the model an env
  var (`LLM_MODEL`) so it can drop to a cheaper tier — `claude-sonnet-5` ($3/$15 per 1M) or
  `claude-haiku-4-5` ($1/$5 per 1M) — without a code change. LangChain is the point here: the same chain
  runs against a different provider by swapping the `init_chat_model(...)` call, so keep the model
  construction in one place.
- **Deps:** `fastapi`, `uvicorn[standard]`, `langchain`, `langchain-anthropic`, `pydantic`,
  `pydantic-settings`, `pytest`, `httpx` (test client). Use `uv` or a plain `requirements.txt`/`pyproject.toml`.

---

## STEP 3 — What the analysis should produce (the `data` shape)

Use LangChain **structured output** (`llm.with_structured_output(RepositoryInsights)`) so the model
returns a validated object, not free text. Recommended `RepositoryInsights` shape — this is what the
Laravel structure page will display:

```jsonc
{
  "summary": "One-paragraph plain-English overview of the repository.",
  "architecture": "Notable structural/architectural observations.",
  "patterns": ["Repository pattern", "Service layer", "…"],   // notable patterns actually evidenced
  "key_functions": [                                          // functions worth studying, from vocabulary_terms
    { "name": "collect", "why": "Ubiquitous helper; understand it before reading Collections." }
  ],
  "study_plan": ["Start with README + src/Foundation", "Then …"],  // suggested reading order
  "model": "claude-opus-4-8"                                  // echo which model produced this
}
```

Keep it honest: derive `key_functions`/`patterns` from the payload you were given (especially
`vocabulary_terms` where `user_frequency == 0`), and don't invent files or functions that aren't in the
input. If `file_structure` is empty (terms-only path), lean on `vocabulary_terms`.

---

## STEP 4 — LangChain implementation notes

- **One structured call is enough for MVP** — this is summarization/insight, not an agent loop. Build a
  `ChatPromptTemplate` (system: "you are a senior engineer helping someone read an unfamiliar codebase";
  human: the repository facts) piped into `llm.with_structured_output(RepositoryInsights)`. No tools, no
  memory, no thinking config required.
- **Control tokens** — `vocabulary_terms` and `file_structure` can be big. Before prompting, summarize
  them server-side: counts per `category`, a sample of representative paths, and the **top ~30**
  vocabulary terms (prioritize `user_frequency == 0`, then by `article_frequency`). Don't dump the whole
  payload into the prompt.
- **Failure = non-2xx.** Wrap the chain call in try/except; on any exception (LLM error, validation
  failure, timeout) return a FastAPI `HTTPException` with a 5xx/4xx status. Do **not** return a 200 with
  a made-up result — the Laravel side is explicitly designed to show "unavailable" on non-2xx.
- **Provider-swappable by design** — construct the model once (e.g.
  `init_chat_model(settings.llm_model, model_provider="anthropic")`); swapping providers later is an env
  change plus the matching `langchain-<provider>` dependency, touching nothing else.

---

## STEP 5 — Endpoints, auth, config

- `POST /analyze` — the contract above.
- `GET /health` — returns `{"status": "ok"}` for readiness checks.
- **Auth:** read `ANALYZER_API_KEY` from env. If set, require `Authorization: Bearer <ANALYZER_API_KEY>`
  on `/analyze` and return `401` otherwise. This value must equal Laravel's `AI_ANALYZER_API_KEY`. If
  unset (local dev), skip the check.
- **Config via env** (`.env` + pydantic-settings; ship a `.env.example`, never a real key):
  `ANTHROPIC_API_KEY`, `ANALYZER_API_KEY` (shared secret with Laravel), `LLM_MODEL`
  (default `claude-opus-4-8`), `LLM_TIMEOUT_SECONDS` (default `45`), `PORT` (default `8001`).

---

## STEP 6 — Wire it to Laravel (no Laravel code changes)

The Laravel side is already built for this. Once the service runs (e.g. `http://localhost:8001`), set in
`mary.win/.env`:

```env
AI_ANALYZER=langchain
AI_ANALYZER_ENDPOINT=http://localhost:8001/analyze
AI_ANALYZER_API_KEY=<same shared secret as the Python ANALYZER_API_KEY>
AI_ANALYZER_TIMEOUT=60
```

On the Python side `.env`: `ANTHROPIC_API_KEY=…`, `ANALYZER_API_KEY=<same shared secret>`,
`LLM_MODEL=claude-opus-4-8`. Then in `mary.win`: `php artisan config:clear`, `php artisan serve`, and on a
repository's structure page click **Full analysis** — the "AI Analysis" plate should show the returned
insights instead of "unavailable". (The provider binding in `AppServiceProvider` switches from
`NullCodeAnalyzer` to `HttpCodeAnalyzer` automatically the moment `AI_ANALYZER_ENDPOINT` is non-empty.)

---

## STEP 7 — Tests (pytest, mock the LLM — never hit the real API in tests)

- `GET /health` → 200.
- `POST /analyze` with a valid payload and a **stubbed** chain (monkeypatch the structured-output runnable
  to return a canned `RepositoryInsights`) → 200 + the expected JSON shape.
- Missing/incorrect `Authorization` when `ANALYZER_API_KEY` is set → 401.
- Malformed body (no `repository`) → 422.
- Chain raises → 502/503 (verify the service does not 200 on LLM failure).

---

## Definition of done
- `POST /analyze` returns structured insights for a real payload; `GET /health` works; bearer auth
  enforced when configured.
- `pytest` green (LLM mocked); a README documents `uv sync`/`pip install`, env vars, and `uvicorn` run.
- With the Python service running and `mary.win/.env` pointed at it, "Full analysis" on a repo shows live
  insights end to end. No secret committed (`.env.example` only).
- The service is provider-swappable: changing `LLM_MODEL` (and, for a different provider, one
  `init_chat_model` line + a `langchain-<provider>` dep) is the entire switch.

## Out of scope (MVP) — note, don't build
- Streaming, response caching, rate limiting, retries/backoff, multi-provider routing, a queue.
- Auth beyond the shared bearer secret; TLS/deployment hardening (do that when you host it).
- The Python service is external to Laravel — `mary.win` must keep building, testing, and running green
  with the service **off** (it falls back to `NullCodeAnalyzer`).

## Optional Laravel follow-ups (small; separate task, not required for MVP)
- `mary.win/resources/views/components/kite/analysis-result.blade.php` currently pretty-prints
  `AnalysisResult::data` as raw JSON. Once the `RepositoryInsights` shape is stable, render its fields
  (summary, patterns, key_functions, study_plan) nicely instead of dumping JSON.
- `mary.win/requirements.txt` is stale reference scaffolding (mentions `langflow`). Replace it with a
  pointer to `kite-analyzer/` or delete it — the real deps now live in the Python service's own manifest.
- `mary.win/config/services.php` `analyzer.driver` comment lists `langflow|langchain|langgraph`; no code
  change needed (selection is by endpoint presence), but you may tidy the comment to reflect langchain-first.
