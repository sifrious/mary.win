<?php

declare(strict_types=1);

namespace FourLetterWords\Core;

/**
 * Pure queries over a player's completed runs (STATE.md derived data, R22/R23). A "run"
 * here is its Chain. Nothing is stored: best streak and word totals are recomputed from
 * the runs on demand — the tar-pit rule, derive rather than cache.
 *
 * (The pure logic is complete now; the shell only exercises it once signed-in persistence
 * lands, which is deferred past milestone 1.)
 */
final class Stats
{
    /**
     * R22: the player's longest streak across all their runs (0 if they have none).
     *
     * @param iterable<Chain> $runs
     */
    public function bestStreak(iterable $runs): int
    {
        $best = 0;

        foreach ($runs as $run) {
            $best = max($best, $run->streak());
        }

        return $best;
    }

    /**
     * R23: how many times the player has played each word, across all their runs.
     * Ordered deterministically: most-played first, ties broken alphabetically.
     *
     * @param iterable<Chain> $runs
     * @return array<string, int> uppercased word => count
     */
    public function wordTotals(iterable $runs): array
    {
        $totals = [];

        foreach ($runs as $run) {
            foreach ($run->words() as $word) {
                $totals[$word->value] = ($totals[$word->value] ?? 0) + 1;
            }
        }

        uksort($totals, static function (string $a, string $b) use ($totals): int {
            return [$totals[$b], $a] <=> [$totals[$a], $b];
        });

        return $totals;
    }

    /**
     * R23: the player's most-played words, at most $limit of them.
     *
     * @param iterable<Chain> $runs
     * @return array<string, int>
     */
    public function mostPlayed(iterable $runs, int $limit): array
    {
        return array_slice($this->wordTotals($runs), 0, max(0, $limit), true);
    }
}
