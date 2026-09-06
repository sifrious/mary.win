<?php

declare(strict_types=1);

namespace LicensePlates\Core;

/**
 * The outcome of a settings transition: the new Settings, or a RejectReason and no change.
 * A value, not an exception — invalid preferences are normal player input.
 */
final class SettingsResult
{
    private function __construct(
        public readonly ?Settings $settings,
        public readonly ?RejectReason $reason,
    ) {
    }

    public static function ok(Settings $settings): self
    {
        return new self($settings, null);
    }

    public static function rejected(RejectReason $reason): self
    {
        return new self(null, $reason);
    }

    public function accepted(): bool
    {
        return $this->settings !== null;
    }
}
