<?php

namespace App\Http\Middleware;

use App\Services\Auth0\Auth0AccessTokenVerifier;
use App\Services\Auth0\Auth0UserProvisioner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Піднімає користувача з Bearer JWT Auth0 на guard `web` (без сесії),
 * щоб далі спрацював `auth:sanctum` і broadcasting auth.
 */
class ResolveAuth0BearerUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('auth0.enabled')) {
            return $next($request);
        }

        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        $token = $request->bearerToken();
        if ($token === null || substr_count($token, '.') !== 2) {
            return $next($request);
        }

        try {
            $claims = app(Auth0AccessTokenVerifier::class)->verify($token);
            $user = app(Auth0UserProvisioner::class)->provision($claims);
            Auth::guard('web')->setUser($user);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::debug('Auth0 bearer resolution failed', [
                'message' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
