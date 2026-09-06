<?php

namespace App\Analysis;

use App\Contracts\CodeAnalyzer;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Backend-agnostic analyzer that POSTs the repository payload to a configurable
 * HTTP endpoint and returns the JSON response. Works unchanged against Langflow,
 * a LangChain/LangServe app, or a LangGraph graph — swapping backends is a config
 * change (endpoint URL + key), not a code change.
 */
final class HttpCodeAnalyzer implements CodeAnalyzer
{
    public function __construct(
        private readonly Http $http,
        private readonly string $endpoint,
        private readonly ?string $apiKey = null,
        private readonly int $timeout = 60,
    ) {}

    public function analyze(array $repositoryData): AnalysisResult
    {
        try {
            $response = $this->http
                ->timeout($this->timeout)
                ->when($this->apiKey, fn ($request) => $request->withToken($this->apiKey))
                ->acceptJson()
                ->post($this->endpoint, ['repository' => $repositoryData]);

            if ($response->failed()) {
                Log::warning('Code analyzer HTTP call failed', [
                    'status' => $response->status(),
                ]);

                return AnalysisResult::unavailable("Analyzer returned {$response->status()}");
            }

            return AnalysisResult::ok($response->json() ?? []);
        } catch (Throwable $e) {
            Log::error('Code analyzer error', ['message' => $e->getMessage()]);

            return AnalysisResult::unavailable($e->getMessage());
        }
    }
}
