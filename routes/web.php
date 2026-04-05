<?php

use App\Http\Controllers\Auth\ClientLoginController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\Client\InvitationController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

// ─── Public landing ──────────────────────────────────────────────────────────
Route::get('/', function () {
    return view('welcome');
});

// ─── Staff / Owner ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'auth.staff'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Clients management
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients/invite', [InvitationController::class, 'send'])->name('clients.invite');
    Route::post('/clients/{client}/resend-invitation', [InvitationController::class, 'resend'])->name('clients.resend-invitation');

    // Staff / team management (owner only — enforced in controller)
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff/invite', [StaffController::class, 'invite'])->name('staff.invite');
    Route::delete('/staff/{member}', [StaffController::class, 'destroy'])->name('staff.destroy');

    // Projects + nested messages
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/messages', [MessageController::class, 'store'])->name('projects.messages.store');

    // Files
    Route::post('/projects/{project}/files', [FileController::class, 'store'])->name('projects.files.store');
    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('/invoices/{invoice}/paid', [InvoiceController::class, 'markPaid'])->name('invoices.markPaid');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.downloadPdf');

    // Business settings (owner only enforced in view with @can, route open to all staff)
    Route::get('/settings', [BusinessSettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [BusinessSettingsController::class, 'update'])->name('settings.update');
    Route::get('/settings/logo', [BusinessSettingsController::class, 'logo'])->name('settings.logo');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── Client invitation (signed URLs — no auth required) ──────────────────────
Route::get('/invitation/{token}', [InvitationController::class, 'accept'])
    ->name('client.invitation.accept');

Route::post('/invitation/{token}', [InvitationController::class, 'activate'])
    ->name('client.invitation.activate');

// ─── Staff invitation (signed URLs — no auth required) ───────────────────────
Route::get('/staff-invitation/{token}', [StaffController::class, 'accept'])
    ->name('staff.invitation.accept');

Route::post('/staff-invitation/{token}', [StaffController::class, 'activate'])
    ->name('staff.invitation.activate');

// ─── Client portal ────────────────────────────────────────────────────────────
Route::prefix('client')->name('client.')->group(function () {
    // Guest
    Route::middleware('guest:client')->group(function () {
        Route::get('/login', [ClientLoginController::class, 'create'])->name('login');
        Route::post('/login', [ClientLoginController::class, 'store']);
    });

    Route::post('/logout', [ClientLoginController::class, 'destroy'])->name('logout');

    // Authenticated client
    Route::middleware('auth.client')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Client\ProjectController::class, 'index'])
            ->name('dashboard');

        // Projects + nested messages
        Route::get('/projects', [\App\Http\Controllers\Client\ProjectController::class, 'index'])
            ->name('projects.index');
        Route::get('/projects/{project}', [\App\Http\Controllers\Client\ProjectController::class, 'show'])
            ->name('projects.show');
        Route::get('/projects/{project}/files/{file}/download',
            [\App\Http\Controllers\Client\ProjectController::class, 'downloadFile'])
            ->name('projects.files.download');
        Route::post('/projects/{project}/messages',
            [\App\Http\Controllers\Client\MessageController::class, 'store'])
            ->name('projects.messages.store');

        // Business logo (for client portal nav)
        Route::get('/logo', function () {
            $business = auth()->guard('client')->user()->business;
            abort_unless($business->logo_path && \Illuminate\Support\Facades\Storage::exists($business->logo_path), 404);
            return \Illuminate\Support\Facades\Storage::response($business->logo_path);
        })->name('logo');

        // Invoices
        Route::get('/invoices', [\App\Http\Controllers\Client\InvoiceController::class, 'index'])
            ->name('invoices.index');
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\Client\InvoiceController::class, 'show'])
            ->name('invoices.show');
        Route::get('/invoices/{invoice}/pdf', [\App\Http\Controllers\Client\InvoiceController::class, 'downloadPdf'])
            ->name('invoices.pdf');
    });
});

// ─── Breeze auth routes (staff) ───────────────────────────────────────────────
require __DIR__.'/auth.php';
