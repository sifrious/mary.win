<?php

declare(strict_types=1);

namespace LicensePlates\Core;

/**
 * The player's configuration (STATE.md: Settings), immutable. Every transition returns a
 * new Settings and enforces its constraint in core, so the shell never decides anything:
 * C1 (state pick and letter preference are mutually exclusive) lives in the two
 * transitions that could violate it; C2 and C3 return a RejectReason instead of a throw.
 */
final class Settings
{
    public function __construct(
        public readonly ?string $selectedStateCode,
        public readonly bool $excludeTwoLetter,
        public readonly bool $excludeMoreThanTwoLetter,
        public readonly bool $robsRuleEnabled,
        public readonly bool $timerEnabled,
        public readonly ?int $timerSeconds,
    ) {
    }

    public static function defaults(): self
    {
        return new self(null, false, false, false, false, null);
    }

    /** R4 + C1: picking a state clears the letter-count preference. C5: code must exist. */
    public function selectingState(string $code, StateFormat ...$formats): SettingsResult
    {
        foreach ($formats as $format) {
            if ($format->code === $code) {
                return SettingsResult::ok(new self($code, false, false, $this->robsRuleEnabled, $this->timerEnabled, $this->timerSeconds));
            }
        }

        return SettingsResult::rejected(RejectReason::UnknownState);
    }

    /** R5: back to random. */
    public function clearingState(): SettingsResult
    {
        return SettingsResult::ok(new self(null, $this->excludeTwoLetter, $this->excludeMoreThanTwoLetter, $this->robsRuleEnabled, $this->timerEnabled, $this->timerSeconds));
    }

    /** R7/R8 + C1: setting a preference clears the specific-state pick. */
    public function withLetterPreference(bool $excludeTwo, bool $excludeMoreThanTwo): SettingsResult
    {
        return SettingsResult::ok(new self(null, $excludeTwo, $excludeMoreThanTwo, $this->robsRuleEnabled, $this->timerEnabled, $this->timerSeconds));
    }

    /** R11/R12 + C3: Rob's Rule can only be enabled when the current plate has 3+ letters. */
    public function withRobsRule(bool $enabled, ?Plate $plate): SettingsResult
    {
        if ($enabled && $plate !== null && ! $plate->robsRuleAvailable()) {
            return SettingsResult::rejected(RejectReason::RobsRuleNeedsThreeLetters);
        }

        return SettingsResult::ok(new self($this->selectedStateCode, $this->excludeTwoLetter, $this->excludeMoreThanTwoLetter, $enabled, $this->timerEnabled, $this->timerSeconds));
    }

    /** R18 + C2: an enabled timer needs a positive duration; disabling clears it. */
    public function withTimer(bool $enabled, ?int $seconds): SettingsResult
    {
        if ($enabled && ($seconds === null || $seconds <= 0)) {
            return SettingsResult::rejected(RejectReason::TimerNeedsPositiveSeconds);
        }

        return SettingsResult::ok(new self($this->selectedStateCode, $this->excludeTwoLetter, $this->excludeMoreThanTwoLetter, $this->robsRuleEnabled, $enabled, $enabled ? $seconds : null));
    }

    /** STATE.md: eligible_states — the formats allowed by the active preference. @return list<StateFormat> */
    public function eligibleAmong(StateFormat ...$formats): array
    {
        if ($this->selectedStateCode !== null) {
            return array_values(array_filter($formats, fn (StateFormat $f) => $f->code === $this->selectedStateCode));
        }

        return array_values(array_filter(
            $formats,
            fn (StateFormat $f) => $f->allowedBy($this->excludeTwoLetter, $this->excludeMoreThanTwoLetter),
        ));
    }
}
