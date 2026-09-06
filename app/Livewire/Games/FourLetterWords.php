<?php

declare(strict_types=1);

namespace App\Livewire\Games;

use App\Games\FourLetterWords\WordList;
use FourLetterWords\Core\Chain;
use FourLetterWords\Core\Rules;
use FourLetterWords\Core\Word;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

/**
 * The imperative shell for Four Letter Words: a thin server validator. It holds the
 * authoritative run (the played words), parses a submitted candidate, hands it to the pure
 * core, and returns the outcome. It makes no game decisions of its own — the only branches
 * here are (a) parse-guard well-formed input and (b) dispatch the core's accept/loss result.
 *
 * The composer UI (typing, cursor, navigation, arming) lives entirely client-side; this
 * component is reached only on submit.
 */
#[Layout('components.layouts.game')]
class FourLetterWords extends Component
{
    /** @var list<string> the run so far — words played, in order (STATE.md: Play) */
    public array $played = [];

    /**
     * Validate one submitted candidate against the current run.
     *
     * @return array{ok: bool, streak?: int, reason?: string|null, log?: list<string>, ignored?: bool}
     */
    public function submit(string $word): array
    {
        // (a) parse guard: only a well-formed four-letter word reaches the core.
        try {
            $candidate = Word::of($word);
        } catch (Throwable) {
            return ['ok' => false, 'ignored' => true];
        }

        $result = (new Rules)->submit(
            Chain::fromWords(...array_map(Word::of(...), $this->played)),
            $candidate,
            WordList::dictionary(),
        );

        // (b) dispatch the core's decision — no game logic here.
        if ($result->accepted) {
            $this->played[] = $candidate->value;

            return ['ok' => true, 'streak' => $result->streak()];
        }

        return [
            'ok' => false,
            'reason' => $result->reason?->value,
            'streak' => $result->streak(),
            'log' => array_reverse($this->played), // most-recent-first (R7)
        ];
    }

    public function playAgain(): void
    {
        $this->played = [];
    }

    public function render()
    {
        return view('livewire.games.four-letter-words');
    }
}
