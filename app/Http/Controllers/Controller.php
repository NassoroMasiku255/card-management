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

        if (! $user || ! $user->canManageEvent($event)) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Only administrators, or users granted this message type on this
     * event, may trigger outbound WhatsApp or SMS messages.
     */
    protected function authorizeMessageSending(Event $event, string $channel): void
    {
        $label = Event::MESSAGE_CHANNELS[$channel];

        abort_unless(
            Auth::user()?->canSendOnEvent($event, $channel),
            403,
            "You do not have permission to send {$label} messages for this event."
        );
    }
}
