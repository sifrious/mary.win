<?php

declare(strict_types=1);

/** The real packaged reference data, validated against STATE.md's constraints. */
function lpRawFormats(): array
{
    return require __DIR__ . '/../resources/state-formats.php';
}

// C8: exactly the 50 states, unique codes.
test('C8 exactly fifty states with unique codes', function () {
    $codes = array_column(lpRawFormats(), 'code');

    expect(count($codes))->toBe(50)
        ->and(count(array_unique($codes)))->toBe(50);
});

// C9: every pattern carries at least one letter slot.
test('C9 every pattern has at least one letter slot', function () {
    foreach (lpRawFormats() as $format) {
        expect(substr_count($format['pattern'], 'L'))->toBeGreaterThanOrEqual(1);
    }
});

// Patterns are strictly over {L, N}.
test('patterns use only L and N', function () {
    foreach (lpRawFormats() as $format) {
        expect(preg_match('/^[LN]+$/', $format['pattern']))->toBe(1);
    }
});
