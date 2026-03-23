<?php

namespace App\Chat\Slash;

/**
 * Результат обробника slash-команди (T66).
 */
final readonly class SlashHandlerResult
{
    /**
     * @param  list<string>  $clientOnlyLines
     * @param  array<string, mixed>  $slashMetaExtension
     */
    private function __construct(
        public string $kind,
        public ?string $roomMessage,
        public array $clientOnlyLines,
        public ?int $httpStatus,
        public ?string $httpMessage,
        public array $slashMetaExtension,
    ) {}

    public static function roomMessage(string $text, array $slashMetaExtension = []): self
    {
        return new self('room_message', $text, [], null, null, $slashMetaExtension);
    }

    /**
     * @param  list<string>  $lines
     */
    public static function clientOnly(array $lines, array $slashMetaExtension = []): self
    {
        return new self('client_only', null, $lines, null, null, $slashMetaExtension);
    }

    public static function httpError(int $status, string $message, array $slashMetaExtension = []): self
    {
        return new self('http_error', null, [], $status, $message, $slashMetaExtension);
    }
}
