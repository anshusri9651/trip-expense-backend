<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\SummaryController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\TripMemberController;
use Illuminate\Support\Facades\Route;

Route::get('health', fn () => response()->json([
    'success' => true,
    'service' => 'trip-expense-api',
    'status' => 'ok',
]));

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', [SummaryController::class, 'dashboard']);
    Route::apiResource('trips', TripController::class)->names('api.trips');
    Route::apiResource('trips.members', TripMemberController::class)->except(['show', 'create', 'edit'])->names('api.trips.members');
    Route::apiResource('trips.expenses', ExpenseController::class)->except(['create', 'edit'])->names('api.trips.expenses');
    Route::get('trips/{trip}/summary', [SummaryController::class, 'summary']);
    Route::get('trips/{trip}/balances', [SummaryController::class, 'balances']);
    Route::get('trips/{trip}/statistics', [SummaryController::class, 'statistics']);
    Route::get('trips/{trip}/settlements', [SummaryController::class, 'settlements']);
});
