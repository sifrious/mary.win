<?php

declare(strict_types=1);

namespace App\Games\FourLetterWords;

use FourLetterWords\Core\Dictionary;

/**
 * Shell-side loader for the reference word list (STATE.md: WordList). This is I/O — it reads
 * the packaged words.txt — so it lives in the app (the shell), never in the pure core. The
 * core {@see Dictionary} only ever receives an array of strings.
 */
final class WordList
{
    private const PATH = 'packages/four-letter-words/resources/words.txt';

    /** @var list<string>|null */
    private static ?array $memo = null;

    /** @return list<string> uppercased four-letter words */
    public static function words(): array
    {
        if (self::$memo === null) {
            $lines = file(base_path(self::PATH), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            self::$memo = $lines === false ? [] : $lines;
        }

        return self::$memo;
    }

    public static function dictionary(): Dictionary
    {
        return new Dictionary(self::words());
    }
}
