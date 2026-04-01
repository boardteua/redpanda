<?php

namespace App\Services\Ai\RudaPanda;

final readonly class RudaPandaImageGenerationResult
{
    public function __construct(
        public string $mime,
        public string $binary,
        public ?string $captionText = null,
    ) {}
}
