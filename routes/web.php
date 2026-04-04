<?php

use App\Http\Controllers\Auth\ClientLoginController;
use App\Http\Controllers\Client\InvitationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ─── Public landing ──────────────────────────────────────────────────────────
Route::get('/', function () {
    return view('welcome');
});

// ─── Staff / Owner dashboard ─────────────────────────────────────────────────
Route::middleware(['auth', 'auth.staff'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Client invitations (sent by staff)
    Route::post('/clients/invite', [InvitationController::class, 'send'])->name('clients.invite');
});

// ─── Client invitation (signed URLs — no auth required) ──────────────────────
Route::get('/invitation/{token}', [InvitationController::class, 'accept'])
    ->name('client.invitation.accept');

Route::post('/invitation/{token}', [InvitationController::class, 'activate'])
    ->name('client.invitation.activate');

// ─── Client auth ─────────────────────────────────────────────────────────────
Route::prefix('client')->name('client.')->group(function () {
    // Guest client routes
    Route::middleware('guest:client')->group(function () {
        Route::get('/login', [ClientLoginController::class, 'create'])->name('login');
        Route::post('/login', [ClientLoginController::class, 'store']);
    });

    Route::post('/logout', [ClientLoginController::class, 'destroy'])->name('logout');

    // Authenticated client routes
    Route::middleware('auth.client')->group(function () {
        Route::get('/dashboard', function () {
            return view('client.dashboard');
        })->name('dashboard');
    });
});

// ─── Breeze auth routes (register, login, password reset for staff) ───────────
require __DIR__.'/auth.php';
