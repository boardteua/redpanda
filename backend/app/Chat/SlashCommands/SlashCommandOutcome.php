<?php

namespace App\Chat\SlashCommands;

/**
 * Результат обробки slash-команди перед збереженням у чат.
 */
final class SlashCommandOutcome
{
    public const MODE_PASS_THROUGH = 'pass_through';

    public const MODE_PUBLIC = 'public';

    public const MODE_CLIENT_ONLY = 'client_only';

    public const MODE_HTTP_ERROR = 'http_error';

    private function __construct(
        public readonly string $mode,
        public readonly string $text,
        /** @var array{name?: string|null, recognized: bool, client_only: bool} */
        public readonly array $slashMeta,
        public readonly ?int $httpStatus = null,
        public readonly ?string $httpMessage = null,
    ) {}

    public static function passThrough(string $rawMessage): self
    {
        return new self(
            self::MODE_PASS_THROUGH,
            $rawMessage,
            ['name' => null, 'recognized' => false, 'client_only' => false],
        );
    }

    /**
     * @param  array{name?: string|null, recognized: bool, client_only?: bool}  $slashMeta
     */
    public static function publicRoomMessage(string $body, array $slashMeta): self
    {
        $meta = $slashMeta + ['client_only' => false];

        return new self(self::MODE_PUBLIC, $body, $meta);
    }

    /**
     * @param  array{name?: string|null, recognized: bool, client_only?: bool}  $slashMeta
     */
    public static function clientOnlyMessage(string $body, array $slashMeta): self
    {
        $meta = $slashMeta + ['client_only' => true];

        return new self(self::MODE_CLIENT_ONLY, $body, $meta);
    }

    public static function httpError(int $status, string $message): self
    {
        return new self(
            self::MODE_HTTP_ERROR,
            '',
            ['name' => null, 'recognized' => false, 'client_only' => false],
            $status,
            $message,
        );
    }
}
