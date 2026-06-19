<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/me', [UserController::class, 'me']);
    Route::put('/change-password', [PasswordController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
