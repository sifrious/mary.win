<?php

declare(strict_types=1);

use App\Livewire\Games\FourLetterWords;
use Livewire\Livewire;

// R1 — the game page loads with the component mounted.
it('R1 renders the game page', function () {
    $this->withoutVite();

    $this->get('/games/four-letter-words')
        ->assertOk()
        ->assertSeeLivewire(FourLetterWords::class);
});

// R2 — the first real word begins the run at streak one.
it('R2 begins the run at streak one on the first real word', function () {
    Livewire::test(FourLetterWords::class)
        ->call('submit', 'CARE')
        ->assertReturned(['ok' => true, 'streak' => 1])
        ->assertSet('played', ['CARE']);
});

// R4 — a first word that is not real ends the run at streak zero.
it('R4 ends the run when the first word is not real', function () {
    Livewire::test(FourLetterWords::class)
        ->call('submit', 'ZZZZ')
        ->assertReturned(['ok' => false, 'reason' => 'not_a_word', 'streak' => 0, 'log' => []])
        ->assertSet('played', []);
});

// R3 — a legal one-letter change advances the streak.
it('R3 advances on a legal one-letter change', function () {
    Livewire::test(FourLetterWords::class)
        ->call('submit', 'CARE')
        ->call('submit', 'CARD')
        ->assertReturned(['ok' => true, 'streak' => 2])
        ->assertSet('played', ['CARE', 'CARD']);
});

// R5 — changing more than one letter ends the run.
it('R5 ends the run on a multi-letter jump', function () {
    Livewire::test(FourLetterWords::class)
        ->call('submit', 'CARE')
        ->call('submit', 'WORD')
        ->assertReturned(fn ($v) => $v['ok'] === false && $v['reason'] === 'not_one_change');
});

// R6 — replaying an earlier word ends the run.
it('R6 ends the run on a repeat', function () {
    Livewire::test(FourLetterWords::class)
        ->call('submit', 'CARE')
        ->call('submit', 'CORE')
        ->call('submit', 'CARE')
        ->assertReturned(fn ($v) => $v['ok'] === false && $v['reason'] === 'repeat');
});

// R7 — on a loss, the run log comes back most-recent-first with the final streak.
it('R7 returns the run log most-recent-first when the run ends', function () {
    Livewire::test(FourLetterWords::class)
        ->call('submit', 'CARE')
        ->call('submit', 'CARD')
        ->call('submit', 'ZZZZ')
        ->assertReturned(fn ($v) => $v['ok'] === false
            && $v['log'] === ['CARD', 'CARE']
            && $v['streak'] === 2);
});

// R8 — play again starts a fresh run.
it('R8 starts a fresh run on play again', function () {
    Livewire::test(FourLetterWords::class)
        ->call('submit', 'CARE')
        ->call('playAgain')
        ->assertSet('played', []);
});

// Malformed input is ignored at the parse guard, never reaching the core.
it('ignores malformed input without ending the run', function () {
    Livewire::test(FourLetterWords::class)
        ->call('submit', 'CARE')
        ->call('submit', 'CA')
        ->assertReturned(['ok' => false, 'ignored' => true])
        ->assertSet('played', ['CARE']);
});
