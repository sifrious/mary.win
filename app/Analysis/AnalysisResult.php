<?php

namespace App\Analysis;

final readonly class AnalysisResult
{
    /**
     * @param  array<string, mixed>|null  $data  Backend-specific payload when successful.
     */
    public function __construct(
        public bool $success,
        public ?array $data = null,
        public ?string $error = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function ok(array $data): self
    {
        return new self(success: true, data: $data);
    }

    public static function unavailable(string $error = 'AI analysis unavailable'): self
    {
        return new self(success: false, error: $error);
    }
}
