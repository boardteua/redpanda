<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Me\UpdateMeAccountRequest;
use App\Http\Resources\UserResource;

class MeAccountController extends Controller
{
    public function update(UpdateMeAccountRequest $request): UserResource
    {
        $user = $request->user();
        abort_if($user === null || $user->guest, 403, 'Гості не можуть змінювати акаунт.');

        $validated = $request->validated();

        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
            $user->email_verified_at = null;
        }

        if (array_key_exists('password', $validated)) {
            $user->password = $validated['password'];
        }

        $user->save();

        return UserResource::make($user->fresh());
    }
}
