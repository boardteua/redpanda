<?php

namespace App\Services\Auth0;

use App\Models\User;
use Illuminate\Support\Str;
use stdClass;

/**
 * Провізіонінг локального User після валідного Auth0 access token (T76).
 * Політика: за `auth0_subject`; за email — лише якщо рядок імпортованого юзера ще без auth0_subject.
 */
class Auth0UserProvisioner
{
    public function provision(stdClass $claims): User
    {
        $sub = (string) $claims->sub;
        $email = isset($claims->email) && is_string($claims->email) ? trim($claims->email) : null;
        if ($email === '') {
            $email = null;
        }

        $existing = User::query()->where('auth0_subject', $sub)->first();
        if ($existing !== null) {
            return $existing;
        }

        if ($email !== null) {
            $byEmail = User::query()
                ->where('guest', false)
                ->where('email', $email)
                ->whereNull('auth0_subject')
                ->first();
            if ($byEmail !== null) {
                $byEmail->forceFill(['auth0_subject' => $sub])->save();

                return $byEmail->fresh();
            }

            $emailTaken = User::query()
                ->where('guest', false)
                ->where('email', $email)
                ->whereNotNull('auth0_subject')
                ->where('auth0_subject', '!=', $sub)
                ->exists();
            if ($emailTaken) {
                abort(409, 'Обліковий запис з цією електронною адресою вже пов’язаний з іншим соціальним входом.');
            }
        }

        $userName = $this->allocateUserName($claims, $sub, $email);

        $user = new User;
        $user->forceFill([
            'user_name' => $userName,
            'email' => $email,
            'password' => null,
            'guest' => false,
            'auth0_subject' => $sub,
            'email_verified_at' => $this->shouldMarkEmailVerified($claims) ? now() : null,
        ])->save();

        return $user->fresh();
    }

    private function shouldMarkEmailVerified(stdClass $claims): bool
    {
        if (! isset($claims->email_verified)) {
            return false;
        }

        return $claims->email_verified === true || $claims->email_verified === 'true' || $claims->email_verified === 1;
    }

    private function allocateUserName(stdClass $claims, string $sub, ?string $email): string
    {
        foreach (['nickname', 'name'] as $key) {
            if (isset($claims->{$key}) && is_string($claims->{$key})) {
                $candidate = $this->sanitizeUserName($claims->{$key});
                if ($candidate !== '' && ! User::query()->where('user_name', $candidate)->exists()) {
                    return $candidate;
                }
            }
        }

        if ($email !== null && str_contains($email, '@')) {
            $local = $this->sanitizeUserName(explode('@', $email, 2)[0] ?? '');
            if ($local !== '' && ! User::query()->where('user_name', $local)->exists()) {
                return $local;
            }
        }

        $base = $this->sanitizeUserName(str_replace('|', '_', $sub));
        if ($base === '') {
            $base = 'oauth';
        }
        $base = Str::limit($base, 24, '');

        for ($i = 0; $i < 30; $i++) {
            $suffix = $i === 0 ? '' : '_'.Str::lower(Str::random(4));
            $candidate = $base.$suffix;
            if (strlen($candidate) > 32) {
                $candidate = Str::limit($base, 32 - strlen($suffix), '').$suffix;
            }
            if (! User::query()->where('user_name', $candidate)->exists()) {
                return $candidate;
            }
        }

        return 'oauth_'.Str::lower(Str::random(12));
    }

    private function sanitizeUserName(string $raw): string
    {
        $s = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', trim($raw)) ?? '';
        $s = trim($s, '_-');
        if (strlen($s) > 32) {
            $s = substr($s, 0, 32);
        }

        return $s;
    }
}
