<?php

namespace App\Games\FourLetterWords;

final class Release
{
    public const RULES_VERSION = '1';

    public static function metadata(): array
    {
        return [
            'game' => 'four-letter-words',
            'rules_version' => self::RULES_VERSION,
            'dictionary_version' => hash_file('sha256', base_path('packages/four-letter-words/resources/words.txt')),
            'dictionary_license' => 'unresolved',
            'offline_distribution_ready' => false,
        ];
    }
}
