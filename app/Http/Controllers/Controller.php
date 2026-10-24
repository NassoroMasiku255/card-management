<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * Guard against operating on an event that belongs to another user.
     */
    protected function authorizeEvent(Event $event): void
    {
        $user = Auth::user();

        if (! $user || (! $user->isAdmin() && $event->user_id !== $user->id)) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Only administrators or users explicitly granted the permission may
     * trigger outbound WhatsApp messages.
     */
    protected function authorizeWhatsAppSending(): void
    {
        abort_unless(
            Auth::user()?->canSendWhatsApp(),
            403,
            'You do not have permission to send WhatsApp messages.'
        );
    }
}
