<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class OtpService
{
    /**
     * Generate a 6-digit OTP
     */
    public function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create and store OTP for user
     */
   public function createOtp(User $user): string
{
    $otp = $this->generateOtp();
    
    $user->update([
        'otp' => $otp,
        'otp_expires_at' => Carbon::now()->addMinutes(10),
        'otp_last_sent_at' => Carbon::now(),
    ]);

    return $otp;
}

    /**
     * Verify OTP for user
     */
    public function verifyOtp(User $user, string $otp): bool
    {
        // Check if OTP matches and hasn't expired
        if ($user->otp !== $otp) {
            return false;
        }

        if (!$user->otp_expires_at || Carbon::now()->isAfter($user->otp_expires_at)) {
            return false;
        }

        // Clear OTP after successful verification
        $user->update([
            'otp' => null,
            'otp_expires_at' => null,
            'is_verified' => true,
            'email_verified_at' => Carbon::now(),
        ]);

        return true;
    }

    

    /**
     * Check if user can request new OTP (rate limiting)
     */
   public function canRequestOtp(User $user): bool
{
    if (!$user->otp_last_sent_at) {
        return true;
    }

    return Carbon::now()->diffInSeconds($user->otp_last_sent_at) > 60;
}

    /**
     * Resend OTP to user
     */
    public function resendOtp(User $user): ?string
    {
        if (!$this->canRequestOtp($user)) {
            return null;
        }

        return $this->createOtp($user);
    }

    public function forceResendOtp(User $user): string
{
    return $this->createOtp($user);
}
}
