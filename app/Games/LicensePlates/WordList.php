<?php

declare(strict_types=1);

namespace App\Games\LicensePlates;

use LicensePlates\Core\Dictionary;

/**
 * Shell-side loader for the reference word list (STATE.md: Word) — the ENABLE list,
 * ~172k entries. This is I/O, so it lives in the app (the shell), never in the pure core;
 * the core {@see Dictionary} only ever receives strings.
 */
final class WordList
{
    private const PATH = 'packages/license-plates/resources/words.txt';

    private static ?Dictionary $memo = null;

    public static function dictionary(): Dictionary
    {
        if (self::$memo === null) {
            $lines = file(base_path(self::PATH), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            self::$memo = new Dictionary($lines === false ? [] : $lines);
        }

        return self::$memo;
    }
}
