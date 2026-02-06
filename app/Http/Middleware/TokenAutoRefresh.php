<?php

namespace App\Http\Middleware;

use App\Services\TokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenAutoRefresh
{
    public function __construct(protected TokenService $tokenService)
    {
    }

    /**
     * Handle an incoming request and auto-refresh token if near expiration.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() && $request->user()->currentAccessToken()) {
            $newToken = $this->tokenService->checkAndRefreshToken(
                $request->user()->currentAccessToken()
            );

            if ($newToken) {
                // Add new token to response headers
                $response->headers->set('X-New-Token', $newToken['access_token']);
                $response->headers->set('X-Token-Expires-At', $newToken['expires_at']);
            }
        }

        return $response;
    }
}
