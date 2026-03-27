<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\OtpNotification;
use App\Services\OtpService;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Check if username is available
     */
    public function checkUsername(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:3|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $exists = User::where('username', $request->username)->exists();

        return response()->json([
            'success' => true,
            'available' => !$exists,
            'message' => $exists ? 'Username already taken' : 'Username is available'
        ]);
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ], [
            'password.confirmed' => 'The password and password confirmation do not match.',
            'password_confirmation.required' => 'Password confirmation is required.',
            'password_confirmation.min' => 'Password confirmation must be at least 8 characters.',
            'email.unique' => 'This email is already registered.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'member', // Default role
            ]);

            // Generate and send OTP
            $otp = $this->otpService->createOtp($user);
            $user->notify(new OtpNotification($otp));

            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Please check your email for the verification code.',
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'User is already verified'
            ], 400);
        }

        $verified = $this->otpService->verifyOtp($user, $request->otp);

        if (!$verified) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        // Create token for the verified user
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token' => $token,
            ]
        ]);
    }

    public function verify(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|string|size:6',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found',
        ], 404);
    }

    if ($user->is_verified) {
        return response()->json([
            'success' => false,
            'message' => 'User already verified',
        ], 400);
    }

    $isValid = $this->otpService->verifyOtp($user, $request->otp);

    if (!$isValid) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired OTP',
        ], 400);
    }

    $tokenData = app(TokenService::class)->createToken($user);

    return response()->json([
        'success' => true,
        'message' => 'Verification successful',
        'data' => [
            ...$tokenData,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
            ]
        ]
    ]);
}



    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'User is already verified'
            ], 400);
        }

        $otp = $this->otpService->resendOtp($user);

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting a new OTP. Current OTP is still valid.'
            ], 429);
        }

        // Send OTP via email
        $user->notify(new OtpNotification($otp));

        return response()->json([
            'success' => true,
            'message' => 'OTP has been resent to your email'
        ]);
    }
}
