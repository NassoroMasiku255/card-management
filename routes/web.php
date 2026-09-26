<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\WebhookLogController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return redirect('/login');
});

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// WhatsApp Cloud API webhook: one public endpoint handling both
// the GET verification handshake and POST event delivery.
Route::match(['get', 'post'], '/webhook/whatsapp', WhatsAppWebhookController::class)
    ->middleware('whatsapp.signature')
    ->name('webhook.whatsapp');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Events
    Route::resource('events', EventController::class);

    // Guests (nested under events)
    Route::prefix('events/{event}')->name('events.')->group(function () {
        Route::get('/guests', [GuestController::class, 'index'])->name('guests.index');
        Route::get('/guests/create', [GuestController::class, 'create'])->name('guests.create');
        Route::post('/guests', [GuestController::class, 'store'])->name('guests.store');
        Route::get('/guests/import', [GuestController::class, 'showImport'])->name('guests.import');
        Route::post('/guests/import', [GuestController::class, 'import'])->name('guests.import.store');
        Route::get('/guests/export', [GuestController::class, 'export'])->name('guests.export');
        Route::get('/guests/{guest}/edit', [GuestController::class, 'edit'])->name('guests.edit');
        Route::put('/guests/{guest}', [GuestController::class, 'update'])->name('guests.update');
        Route::delete('/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');

        // Invitations
        Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
        Route::get('/invitations/{guest}/card', [InvitationController::class, 'card'])->name('invitations.card');
        Route::post('/invitations/{invitation}/send', [InvitationController::class, 'send'])->name('invitations.send');
        Route::post('/invitations/send-all', [InvitationController::class, 'sendAll'])->name('invitations.sendAll');
        Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');

        // Scanner
        Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner.index');
        Route::post('/scanner/verify', [ScannerController::class, 'verify'])->name('scanner.verify');
        Route::post('/scanner/check-in', [ScannerController::class, 'checkIn'])->name('scanner.checkIn');
    });

    // Download template
    Route::get('/guests/template/download', [GuestController::class, 'downloadTemplate'])->name('guests.template');

    // Webhook logs
    Route::get('/webhook-logs', [WebhookLogController::class, 'index'])->name('webhook-logs.index');
    Route::get('/webhook-logs/{webhookLog}', [WebhookLogController::class, 'show'])->name('webhook-logs.show');
});
