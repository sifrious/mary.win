<?php

declare(strict_types=1);

use FourLetterWords\Core\Chain;
use FourLetterWords\Core\Stats;
use FourLetterWords\Core\Word;

function flwRun(string ...$words): Chain
{
    return Chain::fromWords(...array_map(fn (string $w) => Word::of($w), $words));
}

// R22: best streak is the longest run; no runs means zero.
test('R22 best streak is zero with no runs', function () {
    expect((new Stats())->bestStreak([]))->toBe(0);
});

test('R22 best streak is the longest run', function () {
    $runs = [
        flwRun('CARE', 'CARD'),
        flwRun('CARE', 'CARD', 'CORD', 'WORD'),
        flwRun('BARE'),
    ];

    expect((new Stats())->bestStreak($runs))->toBe(4);
});

// R23: word totals count every play across every run.
test('R23 word totals count plays across runs', function () {
    $runs = [
        flwRun('CARE', 'CARD'),
        flwRun('CARE', 'BARE'),
    ];

    expect((new Stats())->wordTotals($runs))->toBe([
        'CARE' => 2,
        'BARE' => 1,
        'CARD' => 1,
    ]);
});

// R23: ordering is deterministic — count descending, ties alphabetical.
test('R23 word totals order by count then alphabetically', function () {
    $runs = [
        flwRun('WARD', 'CARE'),
        flwRun('CARE', 'BARE'),
        flwRun('BARE', 'WARD'),
    ];
    // CARE:2, BARE:2, WARD:2 -> all tie -> alphabetical BARE, CARE, WARD
    expect(array_keys((new Stats())->wordTotals($runs)))->toBe(['BARE', 'CARE', 'WARD']);
});

// R23: most-played respects the limit.
test('R23 most played returns at most the limit', function () {
    $runs = [flwRun('CARE', 'CARE', 'CARD', 'CORD')];

    expect((new Stats())->mostPlayed($runs, 2))->toBe(['CARE' => 2, 'CARD' => 1]);
});

test('R23 most played with a non-positive limit returns nothing', function () {
    $runs = [flwRun('CARE')];

    expect((new Stats())->mostPlayed($runs, 0))->toBe([]);
});
