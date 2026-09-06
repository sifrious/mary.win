<?php

declare(strict_types=1);

namespace LicensePlates\Core;

/**
 * The reference set of real words (STATE.md: Word) — any length, unlike four-letter-words.
 * Immutable, read-only, never produced by play. Built from plain strings so the shell loads
 * the real list and tests inject a tiny fixture — the core never reads a file.
 */
final class Dictionary
{
    /** @var array<string, true> lowercased word => present */
    private readonly array $words;

    /** @param iterable<string> $words */
    public function __construct(iterable $words)
    {
        $set = [];

        foreach ($words as $word) {
            $set[strtolower(trim($word))] = true;
        }

        $this->words = $set;
    }

    public function contains(string $word): bool
    {
        return isset($this->words[strtolower($word)]);
    }

    public function count(): int
    {
        return count($this->words);
    }
}
