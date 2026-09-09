<?php

namespace App\Games\FourLetterWords;

use FourLetterWords\Core\Word;
use Illuminate\Support\Facades\DB;

final readonly class RunStore
{
    public function __construct(private ValidateRun $validator) {}

    /** @param list<string> $submissions */
    public function save(string $accountId, string $runId, array $submissions): array
    {
        $normalized = array_map(fn (string $word) => Word::of($word)->value, $submissions);
        $result = $this->validator->validate($normalized);
        $release = Release::metadata();

        return DB::transaction(function () use ($accountId, $runId, $normalized, $result, $release) {
            $query = DB::table('four_letter_words_runs')->where('account_id', $accountId)->where('run_id', $runId);
            DB::table('four_letter_words_runs')->insertOrIgnore([
                'account_id' => $accountId, 'run_id' => $runId,
                'rules_version' => $release['rules_version'], 'dictionary_version' => $release['dictionary_version'],
                'submissions' => json_encode($normalized, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $existing = $query->lockForUpdate()->first();
            $previous = json_decode($existing->submissions, true, flags: JSON_THROW_ON_ERROR);
            if ($existing->rules_version !== $release['rules_version'] || $existing->dictionary_version !== $release['dictionary_version']
                || array_slice($normalized, 0, count($previous)) !== $previous) {
                throw new RunConflict('A saved run cannot be replaced or shortened. Reload its current progress.');
            }
            if ($normalized !== $previous) {
                $query->update(['submissions' => json_encode($normalized, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
            }

            return ['run_id' => $runId, 'rules_version' => $release['rules_version'], 'dictionary_version' => $release['dictionary_version'], 'submissions' => $normalized, ...$result];
        });
    }

    public function recent(string $accountId): array
    {
        return DB::table('four_letter_words_runs')->where('account_id', $accountId)
            ->orderByDesc('updated_at')->orderByDesc('id')->limit(50)
            ->get(['run_id', 'updated_at'])->map(fn ($run) => (array) $run)->all();
    }

    public function find(string $accountId, string $runId): ?array
    {
        $run = DB::table('four_letter_words_runs')->where('account_id', $accountId)->where('run_id', $runId)->first();
        if ($run === null) {
            return null;
        }

        // Stored submissions retain their release identity even after the current release changes.
        return [
            'run_id' => $run->run_id, 'rules_version' => $run->rules_version,
            'dictionary_version' => $run->dictionary_version,
            'submissions' => json_decode($run->submissions, true, flags: JSON_THROW_ON_ERROR),
        ];
    }
}
