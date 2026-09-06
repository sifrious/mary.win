<?php

declare(strict_types=1);

namespace FourLetterWords\Core;

/**
 * The reference set of real four-letter words (STATE.md: WordList).
 *
 * Immutable, read-only, never produced by play. Built from a plain list of strings so the
 * shell can load the real list from wherever it lives and the tests can inject a tiny
 * fixture — the core itself never reads a file.
 */
final class Dictionary
{
    /** @var array<string, true> uppercased word => present */
    private readonly array $words;

    /** @param iterable<string> $words */
    public function __construct(iterable $words)
    {
        $set = [];

        foreach ($words as $word) {
            $set[strtoupper(trim($word))] = true;
        }

        $this->words = $set;
    }

    public function contains(Word $word): bool
    {
        return isset($this->words[$word->value]);
    }

    public function count(): int
    {
        return count($this->words);
    }
}
