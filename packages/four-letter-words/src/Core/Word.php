<?php

declare(strict_types=1);

namespace FourLetterWords\Core;

use InvalidArgumentException;

/**
 * A well-formed game word: exactly four ASCII letters, normalised to uppercase.
 *
 * Well-formedness (length, alphabet) is a structural guarantee — the four-box composer
 * only ever submits four letters — so a malformed string is a caller error and throws.
 * Whether a well-formed Word is a *real* word (dictionary membership) is a separate game
 * question answered by {@see Dictionary}, not here.
 */
final class Word
{
    public const LENGTH = 4;

    private function __construct(public readonly string $value)
    {
    }

    public static function of(string $raw): self
    {
        $normalised = strtoupper(trim($raw));

        if (preg_match('/^[A-Z]{' . self::LENGTH . '}$/', $normalised) !== 1) {
            throw new InvalidArgumentException(
                sprintf('A word must be exactly %d letters; got "%s".', self::LENGTH, $raw)
            );
        }

        return new self($normalised);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /** C5: this word differs from $other in exactly one of the four positions. */
    public function differsInOnePosition(self $other): bool
    {
        return $this->differenceCount($other) === 1;
    }

    /** Number of positions (0–4) at which the two words differ. */
    public function differenceCount(self $other): int
    {
        $count = 0;

        for ($i = 0; $i < self::LENGTH; $i++) {
            if ($this->value[$i] !== $other->value[$i]) {
                $count++;
            }
        }

        return $count;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
