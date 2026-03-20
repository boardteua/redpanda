<?php

namespace App\Services\Moderation;

use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

final class UserPostingGate
{
    public function ensureCanPost(User $user): void
    {
        $now = time();
        if ($user->isKickedAt($now)) {
            abort(Response::HTTP_FORBIDDEN, 'Вас тимчасово відключено від чату.');
        }
        if ($user->isMutedAt($now)) {
            abort(Response::HTTP_FORBIDDEN, 'Ви в муті й не можете надсилати повідомлення.');
        }
    }
}
