<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminWhatsAppTemplateController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventReportController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\GuestPaymentController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PublicInvitationController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\WebhookLogController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// Public landing page
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('landing');
})->name('home');

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Public guest invitation page (shared via WhatsApp or SMS)
Route::get('/invitation/{qrCode}', [PublicInvitationController::class, 'show'])->name('invitation.public');
Route::get('/invitation/{qrCode}/card', [PublicInvitationController::class, 'card'])->name('invitation.public.card');
Route::post('/invitation/{qrCode}/rsvp', [PublicInvitationController::class, 'rsvp'])->name('invitation.public.rsvp');

// WhatsApp Cloud API webhook: one public endpoint handling both
// the GET verification handshake and POST event delivery.
Route::match(['get', 'post'], '/webhook/whatsapp', WhatsAppWebhookController::class)
    ->middleware('whatsapp.signature')
    ->name('webhook.whatsapp');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin user and event access management
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/create', [AdminUserController::class, 'create'])->name('create');
        Route::post('/', [AdminUserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
    });

    // Admin WhatsApp Templates management & sync
    Route::prefix('admin/whatsapp-templates')->name('admin.whatsapp-templates.')->group(function () {
        Route::get('/', [AdminWhatsAppTemplateController::class, 'index'])->name('index');
        Route::post('/sync', [AdminWhatsAppTemplateController::class, 'sync'])->name('sync');
    });

    // Events
    Route::resource('events', EventController::class);

    // Guests (nested under events)
    Route::prefix('events/{event}')->name('events.')->group(function () {
        Route::get('/report', [EventReportController::class, 'show'])->name('report');
        Route::get('/countdown-card', [EventController::class, 'countdownCard'])->name('countdown-card');

        Route::get('/guests', [GuestController::class, 'index'])->name('guests.index');
        Route::get('/guests/create', [GuestController::class, 'create'])->name('guests.create');
        Route::post('/guests', [GuestController::class, 'store'])->name('guests.store');
        Route::get('/guests/import', [GuestController::class, 'showImport'])->name('guests.import');
        Route::post('/guests/import', [GuestController::class, 'import'])->name('guests.import.store');
        Route::get('/guests/export', [GuestController::class, 'export'])->name('guests.export');
        Route::get('/guests/{guest}/edit', [GuestController::class, 'edit'])->name('guests.edit');
        Route::put('/guests/{guest}', [GuestController::class, 'update'])->name('guests.update');
        Route::delete('/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');
        Route::post('/guests/{guest}/payments', [GuestPaymentController::class, 'store'])->name('guests.payments.store');
        Route::delete('/guests/{guest}/payments/{payment}', [GuestPaymentController::class, 'destroy'])->name('guests.payments.destroy');

        // Invitations
        Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
        Route::get('/invitations/pending', [InvitationController::class, 'pending'])->name('invitations.pending');
        Route::get('/invitations/export', [InvitationController::class, 'export'])->name('invitations.export');
        Route::get('/invitations/{guest}/card', [InvitationController::class, 'card'])->name('invitations.card');
        Route::post('/invitations/{invitation}/upload-card', [InvitationController::class, 'uploadCard'])->name('invitations.upload-card');
        Route::post('/invitations/{invitation}/send', [InvitationController::class, 'send'])->name('invitations.send');
        Route::post('/invitations/send-all', [InvitationController::class, 'sendAll'])->name('invitations.sendAll');
        Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
        Route::post('/guests/{guest}/send-message', [InvitationController::class, 'sendMessage'])->name('guests.send-message');

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
