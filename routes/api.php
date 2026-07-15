<?php

use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\FacilityController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('api.auth.')->group(function (): void {
    Route::post('register', RegisterController::class)->name('register');
    Route::post('login', LoginController::class)->name('login');
    Route::post('forgot-password', [PasswordResetController::class, 'sendLink'])
        ->name('forgot-password');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])
        ->name('reset-password');
    Route::get('verify-email/{id}/{hash}', EmailVerificationController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verify-email');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', LogoutController::class)->name('logout');
        Route::get('me', MeController::class)->name('me');
        Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });
});

// Facilities — list/show public (active only for guests/users);
// write actions require auth:sanctum + manager role (enforced in FormRequest / controller).
Route::get('facilities', [FacilityController::class, 'index'])->name('api.facilities.index');
Route::get('facilities/{facility}', [FacilityController::class, 'show'])->name('api.facilities.show');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('facilities', [FacilityController::class, 'store'])->name('api.facilities.store');
    Route::put('facilities/{facility}', [FacilityController::class, 'update'])->name('api.facilities.update');
    Route::patch('facilities/{facility}', [FacilityController::class, 'update'])->name('api.facilities.patch');
    Route::delete('facilities/{facility}', [FacilityController::class, 'destroy'])->name('api.facilities.destroy');
});
