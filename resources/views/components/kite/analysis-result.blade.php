@props(['result' => null])

{{-- Renders an App\Analysis\AnalysisResult flashed by the controller. With the
     default NullCodeAnalyzer this is an honest "unavailable" — never fabricated. --}}
<div class="k-section" id="ai-analysis">
    <div class="wr-plate">
        <div class="wr-plate__head">
            <span class="wr-label">AI Analysis</span>
            <span class="wr-plate__bar wr-plate__bar--sm" aria-hidden="true"></span>
        </div>
        <div class="wr-plate__body">
            @if ($result && $result->success)
                <div class="k-alert k-alert--success">
                    <h4>✶ Analysis complete</h4>
                    <pre>{{ json_encode($result->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @elseif ($result)
                <div class="k-alert k-alert--error">
                    <h4>AI analysis unavailable</h4>
                    <p style="margin: 0; font-size: 12px; color: var(--muted);">{{ $result->error }}</p>
                    <p style="margin: 8px 0 0; font-size: 11.5px; color: var(--muted);">
                        Set <code>AI_ANALYZER_ENDPOINT</code> (Langflow / LangChain / LangGraph) to enable real insights.
                    </p>
                </div>
            @else
                <p style="text-align: center; color: var(--muted); font-size: 12.5px; padding: 20px 0; margin: 0;">
                    Run an analysis to request AI insights for this repository.
                </p>
            @endif
        </div>
    </div>
</div>
