<?php

namespace App\Services\Ai\RudaPanda;

/**
 * Результат маршрутизації: модель + наміри для логів і merge у generateContent payload.
 */
final readonly class RudaPandaModelRoute
{
    /**
     * @param  array<string, mixed>|null  $generationConfigOverlay  merge у верхній рівень generationConfig запиту
     */
    public function __construct(
        public string $modelId,
        public RudaPandaIntent $intent,
        public string $tier,
        public ?array $generationConfigOverlay = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function mergeIntoPayload(array $payload): array
    {
        if ($this->generationConfigOverlay === null || $this->generationConfigOverlay === []) {
            return $payload;
        }

        $existing = [];
        if (isset($payload['generationConfig']) && is_array($payload['generationConfig'])) {
            $existing = $payload['generationConfig'];
        }

        $payload['generationConfig'] = array_replace_recursive($existing, $this->generationConfigOverlay);

        return $payload;
    }
}
