<?php

declare(strict_types=1);

namespace FourLetterWords\Core;

/**
 * The ordered words played in a single run — a Run's Plays, in order (STATE.md: Play;
 * SPEC "run log"). Immutable: {@see play()} returns a new Chain. Streak, current word and
 * the used-words check all derive from it, so it is the run's single source of truth.
 */
final class Chain
{
    /** @var list<Word> chronological: first played → most recently played */
    private readonly array $words;

    /** @param list<Word> $words */
    private function __construct(array $words)
    {
        $this->words = $words;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /** Rebuild a chain from already-played words (shell/storage hydration). */
    public static function fromWords(Word ...$words): self
    {
        return new self(array_values($words));
    }

    /** Return a new chain with $word appended; the original is untouched. */
    public function play(Word $word): self
    {
        return new self([...$this->words, $word]);
    }

    public function isEmpty(): bool
    {
        return $this->words === [];
    }

    /** Derived: streak = number of plays (STATE.md). */
    public function streak(): int
    {
        return count($this->words);
    }

    /** Derived: current word = the last play, or null before the first. */
    public function currentWord(): ?Word
    {
        if ($this->words === []) {
            return null;
        }

        return $this->words[array_key_last($this->words)];
    }

    /** Derived: used-words membership (C4). */
    public function contains(Word $word): bool
    {
        foreach ($this->words as $played) {
            if ($played->equals($word)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<Word> chronological (first → most recent). */
    public function words(): array
    {
        return $this->words;
    }

    /** @return list<Word> most recent → first (SPEC R7 display order). */
    public function reversed(): array
    {
        return array_reverse($this->words);
    }
}
