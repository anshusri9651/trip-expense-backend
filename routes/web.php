<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\LegalController;
use App\Http\Controllers\Web\TripController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\AdminController;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
    Route::get('/trips/create', [TripController::class, 'create'])->name('trips.create');
    Route::get('/trips/{trip}', [TripController::class, 'show'])->name('trips.show');
    Route::post('/trips', [TripController::class, 'store'])->name('trips.store');
    Route::put('/trips/{trip}', [TripController::class, 'update'])->name('trips.update');
    Route::delete('/trips/{trip}', [TripController::class, 'destroy'])->name('trips.destroy');
    Route::post('/trips/{trip}/members', [TripController::class, 'addMember'])->name('trips.members.add');
    Route::put('/trips/{trip}/members/{member}', [TripController::class, 'updateMember'])->name('trips.members.update');
    Route::delete('/trips/{trip}/members/{member}', [TripController::class, 'deleteMember'])->name('trips.members.delete');
    Route::post('/trips/{trip}/expenses', [TripController::class, 'addExpense'])->name('trips.expenses.add');
    Route::put('/trips/{trip}/expenses/{expense}', [TripController::class, 'updateExpense'])->name('trips.expenses.update');
    Route::delete('/trips/{trip}/expenses/{expense}', [TripController::class, 'deleteExpense'])->name('trips.expenses.delete');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/privacy-policy', fn () => app(LegalController::class)->show('privacy-policy'))->name('legal.privacy');
Route::get('/terms', fn () => app(LegalController::class)->show('terms'))->name('legal.terms');
Route::middleware('auth')->prefix('admin/legal')->name('admin.legal.')->group(function () {
    Route::get('/{slug}/edit', [LegalController::class, 'edit'])->name('edit');
    Route::put('/{slug}', [LegalController::class, 'update'])->name('update');
});
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{user}/toggle', [AdminController::class, 'toggleUser'])->name('users.toggle');
    Route::post('/users/{user}/admin', [AdminController::class, 'setAdmin'])->name('users.admin');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::delete('/trips/{trip}', [AdminController::class, 'deleteTrip'])->name('trips.delete');
    Route::delete('/expenses/{expense}', [AdminController::class, 'deleteExpense'])->name('expenses.delete');
    Route::delete('/members/{member}', [AdminController::class, 'deleteMember'])->name('members.delete');
});
