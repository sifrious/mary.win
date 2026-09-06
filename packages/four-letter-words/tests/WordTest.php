<?php

declare(strict_types=1);

use FourLetterWords\Core\Word;

// C1: a word is exactly four letters.
test('C1 accepts a four-letter word', function () {
    expect(Word::of('CARE')->value)->toBe('CARE');
});

test('C1 normalises case and surrounding whitespace', function () {
    expect(Word::of('  care ')->value)->toBe('CARE');
});

test('C1 rejects words that are not four letters', function (string $bad) {
    expect(fn () => Word::of($bad))->toThrow(InvalidArgumentException::class);
})->with(['', 'CAR', 'CARES', 'CARDS']);

test('C1 rejects non-letters', function (string $bad) {
    expect(fn () => Word::of($bad))->toThrow(InvalidArgumentException::class);
})->with(['CAR3', 'CA E', 'CA-E', '1234']);

// C5 building block: difference counting.
test('differenceCount is zero for equal words', function () {
    expect(Word::of('CARE')->differenceCount(Word::of('care')))->toBe(0);
});

test('differenceCount counts each differing position', function () {
    expect(Word::of('CARE')->differenceCount(Word::of('WORD')))->toBe(3);
});

test('differsInOnePosition is true only for a single change', function () {
    expect(Word::of('CARE')->differsInOnePosition(Word::of('CARD')))->toBeTrue()
        ->and(Word::of('CARE')->differsInOnePosition(Word::of('CARE')))->toBeFalse()
        ->and(Word::of('CARE')->differsInOnePosition(Word::of('CORD')))->toBeFalse();
});

test('equals compares normalised value', function () {
    expect(Word::of('care')->equals(Word::of('CARE')))->toBeTrue();
});
