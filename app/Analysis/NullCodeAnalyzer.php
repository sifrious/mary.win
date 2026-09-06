<?php

namespace App\Analysis;

use App\Contracts\CodeAnalyzer;

/**
 * Default analyzer used when no AI backend is configured.
 *
 * Returns an honest "unavailable" result so the app runs green with zero
 * AI setup. This is the intentional replacement for any fabricated/fallback
 * analysis — never invent insights here.
 */
final class NullCodeAnalyzer implements CodeAnalyzer
{
    public function analyze(array $repositoryData): AnalysisResult
    {
        return AnalysisResult::unavailable();
    }
}
