<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventMessageBalanceController extends Controller
{
    public const MOBILE_NETWORKS = [
        'mpesa' => 'M-Pesa (Vodacom)',
        'mixx' => 'Mixx by Yas (Tigo Pesa)',
        'airtel' => 'Airtel Money',
        'halopesa' => 'HaloPesa',
    ];

    public const PACKAGES = [100, 250, 500, 1000];

    /**
     * Administrator-only manual top-up or correction of an event's balance.
     */
    public function store(Request $request, Event $event)
    {
        $this->authorizeEvent($event);
        abort_unless(Auth::user()?->isAdmin(), 403, 'Administrator access required.');

        $validated = $request->validate([
            'channel' => ['required', Rule::in(array_keys(Event::MESSAGE_CHANNELS))],
            'action' => ['required', Rule::in(['add', 'remove'])],
            'quantity' => 'required|integer|min:1|max:1000000',
            'note' => 'nullable|string|max:255',
        ]);

        $channel = $validated['channel'];
        $label = Event::MESSAGE_CHANNELS[$channel];
        $amount = (int) $validated['quantity'] * ($validated['action'] === 'remove' ? -1 : 1);

        $entry = $event->adjustMessageBalance($channel, $amount, Auth::id(), $validated['note'] ?? null);

        ActivityLog::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'message_balance_adjusted',
            'description' => ($entry->amount >= 0 ? 'Added ' : 'Removed ').number_format(abs($entry->amount))
                ." {$label} messages. New balance: ".number_format($entry->balance_after).'.',
            'metadata' => [
                'channel' => $channel,
                'amount' => $entry->amount,
                'balance_after' => $entry->balance_after,
            ],
        ]);

        return redirect()->route('events.messages.report', $event)->with(
            'success',
            "{$label} balance updated. New balance: ".number_format($entry->balance_after).' messages.'
        );
    }

    /**
     * Purchase screen for buying messages through mobile money.
     */
    public function buy(Event $event)
    {
        $this->authorizeEvent($event);

        return view('events.messages-buy', [
            'event' => $event,
            'channels' => Event::MESSAGE_CHANNELS,
            'networks' => self::MOBILE_NETWORKS,
            'packages' => self::PACKAGES,
        ]);
    }

    /**
     * Online payment is not live yet: validate the order, then tell the user
     * the service is coming soon. Nothing is charged and no balance is added.
     */
    public function submitPurchase(Request $request, Event $event)
    {
        $this->authorizeEvent($event);

        $request->validate([
            'channel' => ['required', Rule::in(array_keys(Event::MESSAGE_CHANNELS))],
            'quantity' => 'required|integer|min:1|max:1000000',
            'network' => ['required', Rule::in(array_keys(self::MOBILE_NETWORKS))],
            'phone_number' => 'required|string|max:20',
        ]);

        return redirect()->route('events.messages.buy', $event)->withInput()->with('purchase_coming_soon', true);
    }
}
