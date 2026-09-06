<?php

namespace App\Contracts;

use App\Analysis\AnalysisResult;

interface CodeAnalyzer
{
    /**
     * Analyze a repository and return AI-derived insights.
     *
     * Implementations MUST NOT throw for expected failures (backend down,
     * timeout, non-2xx) — return AnalysisResult::unavailable() instead so
     * callers never need a try/catch around this.
     *
     * @param  array<string, mixed>  $repositoryData  name, description, language,
     *                                                github_url, file_count, relevant_files, file_structure, vocabulary_terms
     */
    public function analyze(array $repositoryData): AnalysisResult;
}
