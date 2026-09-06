# AI code analysis

Kite can enrich a repository analysis with AI-derived insights, but the AI backend
is **optional and external**. The Laravel app never runs Python and never shells out;
it talks to a backend over plain HTTP through one small, swappable boundary.

## The boundary

- `App\Contracts\CodeAnalyzer` — one method: `analyze(array $repositoryData): AnalysisResult`.
  Implementations MUST NOT throw for expected failures (backend down, timeout, non-2xx);
  they return `AnalysisResult::unavailable()` instead, so callers never need a try/catch.
- `App\Analysis\AnalysisResult` — a small readonly DTO: `success`, `data`, `error`,
  with `AnalysisResult::ok($data)` / `AnalysisResult::unavailable($error)` factories.
- `App\Analysis\NullCodeAnalyzer` — the **default**. Returns an honest "AI analysis
  unavailable" result. The whole app builds, tests, and runs green with no backend.
- `App\Analysis\HttpCodeAnalyzer` — POSTs `{"repository": {…}}` to a configurable
  endpoint and returns the JSON response. Works identically against Langflow, a
  LangChain/LangServe app, or a LangGraph graph.

Selection happens in `AppServiceProvider`: if `services.analyzer.endpoint` is set, the
container binds `HttpCodeAnalyzer`; otherwise `NullCodeAnalyzer`.

The controller and views only ever talk to the `CodeAnalyzer` interface and flash the
`AnalysisResult`. Swapping backends is **"set an env var"**, touching no controller,
model, or view.

## Enabling a real backend

Set these in `.env` (see `config/services.php` → `analyzer`):

```env
AI_ANALYZER=langflow            # label only: null | langflow | langchain | langgraph
AI_ANALYZER_ENDPOINT=https://your-backend.example/analyze
AI_ANALYZER_API_KEY=            # optional; sent as a Bearer token if set
AI_ANALYZER_TIMEOUT=60
```

The backend receives:

```json
{ "repository": { "name": "...", "description": "...", "language": "...",
  "github_url": "...", "file_count": 0, "relevant_files": 0,
  "file_structure": [ ... ], "vocabulary_terms": [ ... ] } }
```

…and should return a JSON object; whatever it returns becomes `AnalysisResult::data`
and is shown on the repository structure page.

## Standing up a Python backend (optional, out of scope)

`requirements.txt` lists the packages a Langflow-based service would need. Run that
service **separately** (Langflow/LangServe/FastAPI/etc.), expose an HTTP endpoint that
accepts the payload above, and point `AI_ANALYZER_ENDPOINT` at it. The Laravel side
needs no Python installed.
