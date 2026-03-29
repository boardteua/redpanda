<?php

namespace App\Jobs;

use App\Models\PrivateMessage;
use App\Services\Push\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWebPushForPrivateMessage implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $messageId) {}

    public function uniqueId(): string
    {
        return 'private-message:'.$this->messageId;
    }

    public function handle(WebPushService $webPush): void
    {
        $message = PrivateMessage::query()->with('sender', 'recipient')->find($this->messageId);
        if ($message === null) {
            return;
        }

        $webPush->sendPrivateMessage($message);
    }
}
