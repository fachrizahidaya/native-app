<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TokenService;
use App\Services\OtpService;
use App\Notifications\OtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function __construct(protected TokenService $tokenService)
    {
    }

    /**
     * Login with email and password
     */
    public function login(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    // If not verified → resend OTP
    if (!$user->is_verified) {

        if ($user->otp_last_sent_at && now()->diffInSeconds($user->otp_last_sent_at) < 60) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting another OTP.',
                'requires_verification' => true,
                'email' => $user->email
            ], 429);
        }

        // Generate new OTP
       $otp = app(OtpService::class)->forceResendOtp($user);

        // Send OTP
        $user->notify(new OtpNotification($otp));

        // Save last sent time (you need this column)
        $user->update([
            'otp_last_sent_at' => now()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Your account is not verified. A new OTP has been sent to your email.',
            'requires_verification' => true,
            'email' => $user->email
        ], 403);
    }

    $tokenData = $this->tokenService->createToken($user);

    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_verified' => $user->is_verified,
            ],
            ...$tokenData,
        ]
    ]);
}

    /**
     * Logout current user
     */
    public function logout(Request $request): JsonResponse
    {
        $this->tokenService->revokeToken($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Manually refresh token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $tokenData = $this->tokenService->createToken($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => $tokenData,
        ]);
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ]
        ]);
    }
}
