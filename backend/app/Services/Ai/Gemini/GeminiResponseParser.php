<?php

namespace App\Services\Ai\Gemini;

final class GeminiResponseParser
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function firstCandidateText(array $response): string
    {
        $parts = $response['candidates'][0]['content']['parts'] ?? null;
        if (! is_array($parts)) {
            return '';
        }

        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $text = trim($part['text']);
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }
}
