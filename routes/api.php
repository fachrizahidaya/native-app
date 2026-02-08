<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    
    // Registration routes
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/check-username', [RegisterController::class, 'checkUsername']);
    Route::post('/verify-otp', [RegisterController::class, 'verifyOtp']);
    Route::post('/resend-otp', [RegisterController::class, 'resendOtp']);
});

// Protected routes with auto token refresh
Route::middleware(['auth:sanctum', 'token.refresh'])->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('/user', [LoginController::class, 'me']);
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::post('/refresh-token', [LoginController::class, 'refreshToken']);
        Route::get('/me', [LoginController::class, 'me']);
    });

    // Admin only routes - can access everything
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users', function () {
            return response()->json([
                'success' => true,
                'data' => \App\Models\User::all(['id', 'name', 'email', 'role', 'created_at'])
            ]);
        });

        Route::get('/dashboard', function () {
            return response()->json([
                'success' => true,
                'message' => 'Welcome to admin dashboard',
                'data' => [
                    'total_users' => \App\Models\User::count(),
                    'total_admins' => \App\Models\User::where('role', 'admin')->count(),
                    'total_members' => \App\Models\User::where('role', 'member')->count(),
                ]
            ]);
        });
    });
});
