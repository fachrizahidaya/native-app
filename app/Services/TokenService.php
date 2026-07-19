<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class TokenService
{
    protected int $tokenExpirationMinutes = 60 * 24 * 7;
    protected int $refreshThresholdMinutes = 10;

    /**
     * Create a new token for user
     */
    public function createToken(User $user, string $tokenName = 'auth_token'): array
    {
        // Revoke existing tokens
        $user->currentAccessToken()?->delete();

        // Create new token with expiration
        $token = $user->createToken(
            $tokenName,
            ['*'],
            Carbon::now()->addMinutes($this->tokenExpirationMinutes)
        );

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at->toISOString(),
            'expires_in' => $this->tokenExpirationMinutes * 60,
        ];
    }

    /**
     * Check if token needs refresh and regenerate if necessary
     */
    public function checkAndRefreshToken(PersonalAccessToken $token): ?array
    {
        if (!$token->expires_at) {
            return null;
        }

        $expiresAt = Carbon::parse($token->expires_at);
        $now = Carbon::now();

        // Token expired
        if ($now->greaterThanOrEqualTo($expiresAt)) {
            return null;
        }

        // Check if within refresh threshold
        $minutesUntilExpiry = $now->diffInMinutes($expiresAt, false);

        if ($minutesUntilExpiry <= $this->refreshThresholdMinutes) {
            $user = $token->tokenable;
            $token->delete();
            return $this->createToken($user);
        }

        return null;
    }

    /**
     * Revoke current token
     */
    public function revokeToken(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Revoke all tokens for user
     */
    public function revokeAllTokens(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
