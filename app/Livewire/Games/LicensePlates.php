<?php

declare(strict_types=1);

namespace App\Livewire\Games;

use App\Games\LicensePlates\StateFormatList;
use App\Games\LicensePlates\WordList;
use LicensePlates\Core\Plate;
use LicensePlates\Core\Round;
use LicensePlates\Core\Rules;
use LicensePlates\Core\Settings;
use LicensePlates\Core\SettingsResult;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The imperative shell for the License Plate Game: it holds the authoritative settings and
 * round, reads the clock and draws the seed (the shell's only two outside inputs), hands
 * everything to the pure core, and copies the result back. It makes no game decisions —
 * the only branches here are (a) parse dispatch, (b) dispatch on the core's accept/reject,
 * and (c) "is there a round yet" existence checks.
 */
#[Layout('components.layouts.plate-game')]
class LicensePlates extends Component
{
    // Settings (STATE.md: Settings), as wire-serializable primitives.
    public ?string $selectedStateCode = null;

    public bool $excludeTwoLetter = false;

    public bool $excludeMoreThanTwoLetter = false;

    public bool $robsRuleEnabled = false;

    public bool $timerEnabled = false;

    public ?int $timerSeconds = null;

    /** @var array{state_code: string, letters: string, numbers: string}|null the current plate */
    public ?array $plate = null;

    public int $startedAtMs = 0;

    public bool $ended = false;

    /** @var list<string> accepted words this round, in order (STATE.md: Round.played) */
    public array $played = [];

    // --- settings ---------------------------------------------------------------

    /** @return array parse dispatch: empty code = back to random (R5), else pick (R4). */
    public function selectState(?string $code): array
    {
        $result = ($code === null || $code === '')
            ? $this->settings()->clearingState()
            : $this->settings()->selectingState($code, ...StateFormatList::formats());

        return $this->applySettings($result);
    }

    public function setLetterPreference(bool $excludeTwo, bool $excludeMoreThanTwo): array
    {
        return $this->applySettings($this->settings()->withLetterPreference($excludeTwo, $excludeMoreThanTwo));
    }

    public function setRobsRule(bool $enabled): array
    {
        return $this->applySettings($this->settings()->withRobsRule($enabled, $this->currentPlate()));
    }

    public function setTimer(bool $enabled, ?int $seconds): array
    {
        return $this->applySettings($this->settings()->withTimer($enabled, $seconds));
    }

    // --- the round ----------------------------------------------------------------

    /** Begin a round. The seed parameter exists for tests; players let the shell draw it. */
    public function newPlate(?int $seed = null): array
    {
        $result = (new Rules)->startRound(
            $this->settings(),
            $seed ?? random_int(1, 0x7FFFFFFF),
            $this->nowMs(),
            ...StateFormatList::formats(),
        );

        if (! $result->accepted()) {
            return ['ok' => false, 'reason' => $result->reason?->value];
        }

        $round = $result->round;
        $this->plate = [
            'state_code' => $round->plate->stateCode,
            'letters' => $round->plate->letters,
            'numbers' => $round->plate->numbers,
        ];
        $this->startedAtMs = $round->startedAtMs;
        $this->ended = false;
        $this->played = [];

        return [
            'ok' => true,
            'plate' => $this->platePayload($round->plate),
            'robs_available' => $round->plate->robsRuleAvailable(),
            'timer_seconds' => $this->timerEnabled ? $this->timerSeconds : null,
        ];
    }

    public function submit(string $word): array
    {
        $round = $this->currentRound();

        if ($round === null) {
            return ['ok' => false, 'reason' => 'no_round'];
        }

        $result = (new Rules)->submit($round, $word, $this->settings(), WordList::dictionary(), $this->nowMs());

        if ($result->accepted) {
            $this->played = $result->round->played;

            return [
                'ok' => true,
                'word' => end($this->played),
                'points' => $result->points,
                'score' => $result->score(),
            ];
        }

        return ['ok' => false, 'reason' => $result->reason?->value, 'score' => $result->score()];
    }

    /** R16/R17: end the round — report only the score and the words the player played. */
    public function finish(): array
    {
        $round = $this->currentRound();

        if ($round === null) {
            return ['ok' => false, 'reason' => 'no_round'];
        }

        $round = $round->finish();
        $this->ended = true;

        return ['ok' => true, 'score' => $round->score(), 'words' => $round->played];
    }

    public function render()
    {
        return view('livewire.games.license-plates');
    }

    // --- hydration helpers (mapping only, no decisions) ----------------------------

    private function settings(): Settings
    {
        return new Settings(
            $this->selectedStateCode,
            $this->excludeTwoLetter,
            $this->excludeMoreThanTwoLetter,
            $this->robsRuleEnabled,
            $this->timerEnabled,
            $this->timerSeconds,
        );
    }

    private function currentPlate(): ?Plate
    {
        if ($this->plate === null) {
            return null;
        }

        return new Plate($this->plate['state_code'], $this->plate['letters'], $this->plate['numbers']);
    }

    private function currentRound(): ?Round
    {
        $plate = $this->currentPlate();

        if ($plate === null) {
            return null;
        }

        return Round::restore($plate, $this->startedAtMs, $this->ended, $this->played);
    }

    private function applySettings(SettingsResult $result): array
    {
        if (! $result->accepted()) {
            return ['ok' => false, 'reason' => $result->reason?->value];
        }

        $settings = $result->settings;
        $this->selectedStateCode = $settings->selectedStateCode;
        $this->excludeTwoLetter = $settings->excludeTwoLetter;
        $this->excludeMoreThanTwoLetter = $settings->excludeMoreThanTwoLetter;
        $this->robsRuleEnabled = $settings->robsRuleEnabled;
        $this->timerEnabled = $settings->timerEnabled;
        $this->timerSeconds = $settings->timerSeconds;

        return [
            'ok' => true,
            'settings' => [
                'selected_state_code' => $settings->selectedStateCode,
                'exclude_two_letter' => $settings->excludeTwoLetter,
                'exclude_more_than_two_letter' => $settings->excludeMoreThanTwoLetter,
                'robs_rule_enabled' => $settings->robsRuleEnabled,
                'timer_enabled' => $settings->timerEnabled,
                'timer_seconds' => $settings->timerSeconds,
            ],
        ];
    }

    /** Presentation cells for the plate, interleaved per the state's pattern. */
    private function platePayload(Plate $plate): array
    {
        $pattern = '';
        foreach (StateFormatList::raw() as $row) {
            if ($row['code'] === $plate->stateCode) {
                $pattern = $row['pattern'];
            }
        }

        $cells = [];
        $letterAt = 0;
        $numberAt = 0;
        foreach (str_split($pattern) as $slot) {
            $cells[] = $slot === 'L'
                ? ['t' => 'L', 'ch' => $plate->letters[$letterAt++] ?? '?']
                : ['t' => 'N', 'ch' => $plate->numbers[$numberAt++] ?? '0'];
        }

        return [
            'code' => $plate->stateCode,
            'name' => StateFormatList::nameOf($plate->stateCode),
            'letters' => $plate->letters,
            'cells' => $cells,
        ];
    }

    /** The only clock read in the feature. */
    private function nowMs(): int
    {
        return (int) (microtime(true) * 1000);
    }
}
