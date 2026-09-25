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
        if ($event->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }
}
