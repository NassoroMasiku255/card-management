<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventPaymentMethod;
use Illuminate\Http\Request;

class EventPaymentMethodController extends Controller
{
    public function store(Request $request, Event $event)
    {
        $this->authorizeEvent($event);

        $validated = $request->validate([
            'payment_name' => 'required|string|max:255',
            'payment_number' => 'required|string|max:100',
        ]);

        $event->paymentMethods()->create($validated);

        return redirect()->route('events.show', $event)->with('success', 'Payment account added.');
    }

    public function destroy(Event $event, EventPaymentMethod $paymentMethod)
    {
        $this->authorizeEvent($event);
        abort_unless($paymentMethod->event_id === $event->id, 404);

        $paymentMethod->delete();

        return redirect()->route('events.show', $event)->with('success', 'Payment account removed.');
    }
}