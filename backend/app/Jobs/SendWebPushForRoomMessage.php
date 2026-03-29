<?php

namespace App\Jobs;

use App\Models\ChatMessage;
use App\Services\Push\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWebPushForRoomMessage implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $messageId) {}

    public function uniqueId(): string
    {
        return 'room-message:'.$this->messageId;
    }

    public function handle(WebPushService $webPush): void
    {
        $message = ChatMessage::query()->with('room', 'user')->find($this->messageId);
        if ($message === null) {
            return;
        }

        $webPush->sendRoomMessage($message);
    }
}
