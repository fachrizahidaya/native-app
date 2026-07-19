<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\GroceryBudgetController;
use App\Http\Controllers\GroceryExpenseController;
use App\Http\Controllers\HomeworkController;
use App\Http\Controllers\NoteController;
use App\Services\OtpService;
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
    Route::post('/force-resend-otp', [LoginController::class, 'forceResendOtp']);
    
    // Registration routes
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/check-username', [RegisterController::class, 'checkUsername']);
    Route::post('/verify-otp', [RegisterController::class, 'verify']);
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

    Route::get('/groceries/budgets', [GroceryBudgetController::class, 'index']);
    Route::post('/groceries/budgets', [GroceryBudgetController::class, 'store']);
    Route::get('/groceries/budgets/{grocery}', [GroceryBudgetController::class, 'show']);
    Route::put('/groceries/budgets/{grocery}', [GroceryBudgetController::class, 'update']);
    Route::patch('/groceries/budgets/{grocery}', [GroceryBudgetController::class, 'update']);
    Route::delete('/groceries/budgets/{grocery}', [GroceryBudgetController::class, 'destroy']);

    Route::apiResource('groceries', GroceryBudgetController::class);
    Route::apiResource('homework', HomeworkController::class);
    Route::apiResource('notes', NoteController::class);
    
    // Grocery Expenses
    Route::get('/groceries/budgets/{grocery}/expenses', [GroceryExpenseController::class, 'index']);
    Route::post('/groceries/budgets/{grocery}/expenses', [GroceryExpenseController::class, 'store']);
    Route::get('/groceries/budgets/{grocery}/expenses/{expense}', [GroceryExpenseController::class, 'show']);
    Route::put('/groceries/budgets/{grocery}/expenses/{expense}', [GroceryExpenseController::class, 'update']);
    Route::patch('/groceries/budgets/{grocery}/expenses/{expense}', [GroceryExpenseController::class, 'update']);
    Route::delete('/groceries/budgets/{grocery}/expenses/{expense}', [GroceryExpenseController::class, 'destroy']);

    // Note
    Route::get('/notes', [NoteController::class, 'index']);
    Route::post('/notes', [NoteController::class, 'store']);
    Route::get('/notes/{note}', [NoteController::class, 'show']);
    Route::put('/notes/{note}', [NoteController::class, 'update']);
    Route::delete('/notes/{note}', [NoteController::class, 'destroy']);

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
