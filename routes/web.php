<?php

use App\Http\Controllers\Auth\ClientLoginController;
use App\Http\Controllers\Client\InvitationController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
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

    // Projects
    Route::resource('projects', ProjectController::class);

    // Files
    Route::post('/projects/{project}/files', [FileController::class, 'store'])->name('projects.files.store');
    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('/invoices/{invoice}/paid', [InvoiceController::class, 'markPaid'])->name('invoices.markPaid');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.downloadPdf');

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

        // Projects
        Route::get('/projects', [\App\Http\Controllers\Client\ProjectController::class, 'index'])
            ->name('projects.index');
        Route::get('/projects/{project}', [\App\Http\Controllers\Client\ProjectController::class, 'show'])
            ->name('projects.show');
        Route::get('/projects/{project}/files/{file}/download',
            [\App\Http\Controllers\Client\ProjectController::class, 'downloadFile'])
            ->name('projects.files.download');

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
