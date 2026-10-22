<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GuestPaymentController extends Controller
{
    public function store(Request $request, Event $event, Guest $guest)
    {
        $this->authorizeEvent($event);
        $this->ensureGuestBelongsToEvent($event, $guest);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|in:cash,mobile_money,bank,cheque,other',
            'reference' => 'nullable|string|max:255',
            'paid_at' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payment = DB::transaction(function () use ($validated, $event, $guest) {
            $payment = $guest->payments()->create([
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'amount' => $validated['amount'],
                'method' => $validated['method'],
                'reference' => $validated['reference'] ?? null,
                'paid_at' => $validated['paid_at'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->refreshGuestPaymentTotals($guest);

            return $payment;
        });

        ActivityLog::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'payment_recorded',
            'description' => "Payment of ".number_format((float) $payment->amount)." TZS recorded for '{$guest->full_name}'.",
            'metadata' => [
                'guest_id' => $guest->id,
                'payment_id' => $payment->id,
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
            ],
        ]);

        return back()->with('success', 'Payment recorded successfully!');
    }

    public function destroy(Event $event, Guest $guest, GuestPayment $payment)
    {
        $this->authorizeEvent($event);
        $this->ensureGuestBelongsToEvent($event, $guest);
        abort_unless($payment->guest_id === $guest->id && $payment->event_id === $event->id, 404);

        $amount = (float) $payment->amount;

        DB::transaction(function () use ($guest, $payment) {
            $payment->delete();
            $this->refreshGuestPaymentTotals($guest);
        });

        ActivityLog::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'payment_deleted',
            'description' => "Payment of ".number_format($amount)." TZS removed for '{$guest->full_name}'.",
            'metadata' => [
                'guest_id' => $guest->id,
                'amount' => $amount,
            ],
        ]);

        return back()->with('success', 'Payment removed successfully!');
    }

    private function ensureGuestBelongsToEvent(Event $event, Guest $guest): void
    {
        abort_unless($guest->event_id === $event->id, 404);
    }

    private function refreshGuestPaymentTotals(Guest $guest): void
    {
        $guest->update([
            'paid_amount' => $guest->payments()->sum('amount'),
            'last_payment_at' => $guest->payments()->max('paid_at'),
        ]);
    }
}
