<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TransportLogController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

/*
 * Guest / Authentication
 */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.store')->middleware('throttle:5,1');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware(['auth', 'no.cache'])
    ->name('logout');

/*
 * Authenticated application
 */
Route::middleware(['auth', 'active', 'no.cache'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
     * Transport logs (Admin & Super Admin)
     */
    Route::resource('transport-logs', TransportLogController::class)
        ->parameters(['transport-logs' => 'transport_log']);

    Route::get('/transport-logs/export/monthly', [TransportLogController::class, 'exportMonthly'])
        ->name('transport-logs.export.monthly');
    Route::get('/transport-logs/export/yearly', [TransportLogController::class, 'exportYearly'])
        ->name('transport-logs.export.yearly');
    Route::get('/transport-logs/export/range', [TransportLogController::class, 'exportRange'])
        ->name('transport-logs.export.range');
    Route::get('/transport-logs/{transport_log}/export/single', [TransportLogController::class, 'exportSingle'])
        ->name('transport-logs.export.single');

    /*
     * Settings / Profile (every active user)
     */
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingController::class, 'update'])->name('settings.update');

    /*
     * Super Admin only
     */
    Route::middleware(['role:super_admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');

        Route::resource('activity-logs', ActivityLogController::class)
            ->parameters(['activity-logs' => 'activity_log'])
            ->only(['index', 'show']);
    });
});

/*
 * Static error pages
 */
Route::get('/403', fn () => view('errors.403'))->name('errors.forbidden');
Route::get('/500', fn () => view('errors.500'))->name('errors.server');

Route::fallback(fn () => view('errors.404'));
