<?php

declare(strict_types=1);

use LicensePlates\Core\Plate;

// R9: MZE → aMaZEd / MaZE — the plate letters as an in-order subsequence.
test('R9 plate letters appear in order within a word', function () {
    $plate = new Plate('PA', 'MZE', '0000');

    expect($plate->lettersAppearInOrderIn('amazed'))->toBeTrue()
        ->and($plate->lettersAppearInOrderIn('maze'))->toBeTrue()
        ->and($plate->lettersAppearInOrderIn('mazes'))->toBeTrue();
});

// R9: reordering or dropping a plate letter fails.
test('R9 reordered or missing plate letters fail the subsequence test', function () {
    $plate = new Plate('PA', 'MZE', '0000');

    expect($plate->lettersAppearInOrderIn('zema'))->toBeFalse() // order violated
        ->and($plate->lettersAppearInOrderIn('me'))->toBeFalse(); // missing Z
});

// R11: Rob's Rule anchors — first plate letter starts the word, last ends it.
test("R11 Rob's Rule anchors require matching first and last letters", function () {
    $plate = new Plate('PA', 'MZE', '0000');

    expect($plate->anchorsSatisfiedBy('maze'))->toBeTrue()   // M…E
        ->and($plate->anchorsSatisfiedBy('amazed'))->toBeFalse(); // a…d
});

// R12: Rob's Rule availability is a plate property — three or more letters.
test("R12 Rob's Rule is available only on plates of three or more letters", function () {
    expect((new Plate('PA', 'MZE', '0000'))->robsRuleAvailable())->toBeTrue()
        ->and((new Plate('RI', 'MZ', '0000'))->robsRuleAvailable())->toBeFalse();
});

// Case-insensitivity: the composer may send any case.
test('subsequence and anchors are case-insensitive', function () {
    $plate = new Plate('PA', 'MZE', '0000');

    expect($plate->lettersAppearInOrderIn('AmAzEd'))->toBeTrue()
        ->and($plate->anchorsSatisfiedBy('MaZe'))->toBeTrue();
});
