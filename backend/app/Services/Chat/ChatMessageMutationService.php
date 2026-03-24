<?php

namespace App\Services\Chat;

use App\Events\MessageDeleted;
use App\Events\MessageUpdated;
use App\Http\Requests\Chat\UpdateChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Moderation\ChatAutomoderationService;
use App\Services\Moderation\ContentWordFilter;
use App\Services\Moderation\UserPostingGate;
use App\Support\ChatMessageBodyStyle;
use Illuminate\Http\JsonResponse;

/**
 * Редагування та м’яке видалення повідомлень у кімнаті (T106).
 * Авторизація кімнати/політики лишається в контролері.
 */
final class ChatMessageMutationService
{
    public function __construct(
        private readonly ContentWordFilter $wordFilter,
        private readonly UserPostingGate $postingGate,
        private readonly ChatAutomoderationService $automod,
    ) {}

    public function update(User $user, ChatMessage $message, UpdateChatMessageRequest $request): JsonResponse
    {
        $this->postingGate->ensureCanPost($user);

        $validated = $request->validated();
        $rawMsg = trim((string) ($validated['message'] ?? ''));
        if ($message->type === 'public') {
            $mod = $this->automod->applyToPublicMessage($rawMsg, $user);
            if (! $mod['ok']) {
                return response()->json(['message' => $mod['message']], 422);
            }
            $filtered = $mod['text'];
            $message->moderation_flag_at = $mod['flag'] ? time() : null;
        } else {
            $filtered = $this->wordFilter->filter($rawMsg);
        }

        if ($request->has('style')) {
            $sp = $validated['style'] ?? null;
            $message->post_style = ChatMessageBodyStyle::fromValidated(is_array($sp) ? $sp : null);
        }

        $message->post_message = $filtered;
        $message->post_edited_at = time();
        $message->save();

        broadcast(new MessageUpdated($message))->toOthers();

        return ChatMessageResource::make($message->fresh())->response();
    }

    public function softDelete(User $user, ChatMessage $message): JsonResponse
    {
        $this->postingGate->ensureCanPost($user);

        $now = time();
        if ($message->post_deleted_at === null) {
            $message->post_deleted_at = $now;
            $message->post_message = '';
            $message->file = 0;
            $message->post_style = null;
            $message->save();
            broadcast(new MessageDeleted($message->fresh()))->toOthers();
        }

        return ChatMessageResource::make($message->fresh())->response();
    }
}
