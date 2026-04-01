<?php

namespace App\Services\Ai\RudaPanda;

use App\Services\Ai\Gemini\GeminiClient;

final class RudaPandaImageGenerator
{
    public function __construct(
        private readonly GeminiClient $gemini,
    ) {}

    /**
     * @return RudaPandaImageGenerationResult|null null when model returned no image
     */
    public function generate(string $prompt, string $modelId): ?RudaPandaImageGenerationResult
    {
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                // Gemini image generation: request both text (caption/notes) and image payload.
                // https://ai.google.dev/gemini-api/docs/image-generation
                'responseModalities' => ['TEXT', 'IMAGE'],
            ],
        ];

        $resp = $this->gemini->generateContent($payload, $modelId);

        $parts = $resp['candidates'][0]['content']['parts'] ?? null;
        if (! is_array($parts)) {
            return null;
        }

        $caption = null;
        $img = null;
        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }
            if ($caption === null && isset($part['text']) && is_string($part['text'])) {
                $t = trim($part['text']);
                $caption = $t === '' ? null : $t;
            }
            if (isset($part['inlineData']) && is_array($part['inlineData'])) {
                $img = $part['inlineData'];
                break;
            }
        }

        if (! is_array($img)) {
            return null;
        }

        $mime = $img['mimeType'] ?? null;
        $b64 = $img['data'] ?? null;
        if (! is_string($mime) || ! is_string($b64) || $mime === '' || $b64 === '') {
            return null;
        }

        $bin = base64_decode($b64, true);
        if (! is_string($bin) || $bin === '') {
            return null;
        }

        return new RudaPandaImageGenerationResult(
            mime: $mime,
            binary: $bin,
            captionText: $caption,
        );
    }
}
