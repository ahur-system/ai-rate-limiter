<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\PostController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make sure to add the
| ai-rate-limit middleware to routes that need rate limiting.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Apply AI Rate Limiting to all API routes
Route::middleware(['ai-rate-limit'])->group(function () {
    
    // User management routes
    Route::prefix('v1/users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });
    
    // Post management routes
    Route::prefix('v1/posts')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::post('/', [PostController::class, 'store']);
        Route::get('/{post}', [PostController::class, 'show']);
        Route::put('/{post}', [PostController::class, 'update']);
        Route::delete('/{post}', [PostController::class, 'destroy']);
    });
    
    // Admin routes with different limits
    Route::prefix('v1/admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/analytics', function () {
            return response()->json(['message' => 'Admin analytics']);
        });
        Route::get('/users', function () {
            return response()->json(['message' => 'All users']);
        });
    });
});

// Public routes (no rate limiting)
Route::get('/health', function () {
    return response()->json(['status' => 'healthy']);
}); 