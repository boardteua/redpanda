<?php

namespace App\Services\Moderation;

final class ContentWordFilter
{
    public function __construct(
        private readonly ChatAutomoderationService $automod,
    ) {}

    public function filter(string $message): string
    {
        if ($message === '') {
            return $message;
        }

        return $this->automod->maskPrivateChannelText($message);
    }

    public static function flushCache(): void
    {
        \App\Models\FilterWord::flushModerationCache();
    }
}
