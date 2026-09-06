<?php

declare(strict_types=1);

namespace App\Games\LicensePlates;

use LicensePlates\Core\StateFormat;

/**
 * Shell-side loader for the 50 state plate formats (STATE.md: StateFormat). I/O only —
 * reads the packaged reference file and hands the core plain value objects.
 */
final class StateFormatList
{
    private const PATH = 'packages/license-plates/resources/state-formats.php';

    /** @var list<array{code: string, name: string, pattern: string}>|null */
    private static ?array $memo = null;

    /** @return list<array{code: string, name: string, pattern: string}> */
    public static function raw(): array
    {
        return self::$memo ??= require base_path(self::PATH);
    }

    /** @return list<StateFormat> */
    public static function formats(): array
    {
        return array_map(
            fn (array $row) => new StateFormat($row['code'], $row['name'], $row['pattern']),
            self::raw(),
        );
    }

    public static function nameOf(string $code): string
    {
        foreach (self::raw() as $row) {
            if ($row['code'] === $code) {
                return $row['name'];
            }
        }

        return $code;
    }
}
