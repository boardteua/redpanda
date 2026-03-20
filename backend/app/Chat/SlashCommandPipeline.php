<?php

namespace App\Chat;

/**
 * Мінімальний конвеєр slash-команд для розширення в наступних задачах.
 */
final class SlashCommandPipeline
{
    /**
     * @return array{message: string, slash: array{name: string|null, recognized: bool}}
     */
    public function transform(string $rawMessage, string $displayUserName): array
    {
        $trimmed = ltrim($rawMessage);
        if ($trimmed === '' || $trimmed[0] !== '/') {
            return [
                'message' => $rawMessage,
                'slash' => ['name' => null, 'recognized' => false],
            ];
        }

        $withoutSlash = substr($trimmed, 1);
        $space = strpos($withoutSlash, ' ');
        $command = $space === false
            ? strtolower($withoutSlash)
            : strtolower(substr($withoutSlash, 0, $space));
        $rest = $space === false ? '' : ltrim(substr($withoutSlash, $space + 1));

        if ($command === 'me') {
            $body = $rest === '' ? '' : $rest;
            $formatted = '*'.$displayUserName.($body === '' ? '' : ' '.$body).'*';

            return [
                'message' => $formatted,
                'slash' => ['name' => 'me', 'recognized' => true],
            ];
        }

        return [
            'message' => $rawMessage,
            'slash' => ['name' => $command !== '' ? $command : null, 'recognized' => false],
        ];
    }
}
