<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $events = ($user->isAdmin() ? Event::query()->with('user') : $user->events())
            ->with('user')
            ->withCount([
                'guests',
                'guests as single_guests_count' => fn ($query) => $query->where('card_type', 'single'),
                'guests as double_guests_count' => fn ($query) => $query->where('card_type', 'double'),
                'invitations',
                'invitations as invitations_sent_count' => fn ($query) => $query->where('send_status', 'sent'),
                'invitations as rsvp_attending_count' => fn ($query) => $query->where('rsvp_status', 'attending'),
                'invitations as rsvp_not_attending_count' => fn ($query) => $query->where('rsvp_status', 'not_attending'),
                'invitations as rsvp_pending_count' => fn ($query) => $query->where('rsvp_status', 'pending'),
                'invitations as checked_in_count' => fn ($query) => $query->where('attendance_status', 'attended'),
            ])
            ->withSum('guests as total_pledged', 'amount_contributed')
            ->withSum('guests as total_paid', 'paid_amount')
            ->latest()
            ->get();

        return view('events.index', compact('events'));
    }

    public function create()
    {
        $templates = $this->getCardTemplates();

        return view('events.create', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'event_date' => 'required|date|after:today',
            'event_time' => 'nullable|date_format:H:i,H:i:s',
            'location' => 'required|string|max:255',
            'hall' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'card_template' => 'required|string',
            'card_background_color' => 'nullable|string|max:7',
            'card_text_color' => 'nullable|string|max:7',
            'card_accent_color' => 'nullable|string|max:7',
            'single_card_min_amount' => 'nullable|numeric|min:1',
            'double_card_min_amount' => 'nullable|numeric|min:1',
            'payment_thank_you_enabled' => 'nullable|boolean',
            'cover_image' => 'nullable|image|max:5120',
        ]);

        $validated['event_time'] = $this->normalizeEventTime($validated['event_time'] ?? null);
        $validated['user_id'] = Auth::id();
        $validated['status'] = 'active';
        $validated['payment_thank_you_enabled'] = $request->boolean('payment_thank_you_enabled');

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('events/covers', 'public');
        }

        $event = Event::create($validated);

        ActivityLog::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'event_created',
            'description' => "Event '{$event->name}' was created.",
        ]);

        return redirect()->route('events.show', $event)->with('success', 'Event created successfully!');
    }

    public function show(Event $event)
    {
        $this->authorizeEvent($event);

        $event->load(['guests.invitation', 'invitations.guest']);

        $totalPledged = $event->guests->sum('amount_contributed');
        $totalPaid = $event->guests->sum('paid_amount');
        $singles = $event->guests->where('card_type', 'single')->count();
        $doubles = $event->guests->where('card_type', 'double')->count();

        $stats = [
            'total_guests' => $event->guests->count(),
            'invitations_sent' => $event->invitations->where('send_status', 'sent')->count(),
            'invitations_pending' => $event->invitations->where('send_status', 'pending')->count(),
            'rsvp_attending' => $event->invitations->where('rsvp_status', 'attending')->count(),
            'rsvp_not_attending' => $event->invitations->where('rsvp_status', 'not_attending')->count(),
            'rsvp_pending' => $event->invitations->where('rsvp_status', 'pending')->count(),
            'checked_in' => $event->invitations->where('attendance_status', 'attended')->count(),
            'not_checked_in' => $event->invitations->where('attendance_status', 'pending')->count(),
            'total_contribution' => $totalPledged,
            'total_pledged' => $totalPledged,
            'total_paid' => $totalPaid,
            'outstanding' => max(0, $totalPledged - $totalPaid),
            'estimated_attendance' => $event->guests->sum(fn ($guest) => $guest->estimated_attendees),
            'singles' => $singles,
            'doubles' => $doubles,
            'collection_rate' => $totalPledged > 0
                ? (int) min(100, round(($totalPaid / $totalPledged) * 100))
                : ($totalPaid > 0 ? 100 : 0),
        ];

        $topGuests = $event->guests
            ->sortByDesc(fn ($guest) => (float) $guest->paid_amount)
            ->take(5)
            ->values();

        $activityLogs = $event->activityLogs()
            ->with('user')
            ->latest()
            ->take(8)
            ->get();

        return view('events.show', compact('event', 'stats', 'topGuests', 'activityLogs'));
    }

    public function countdownCard(Event $event)
    {
        $this->authorizeEvent($event);

        $daysRemaining = max(0, (int) now()->startOfDay()->diffInDays($event->event_date->startOfDay(), false));
        $countdownText = $daysRemaining === 0
            ? 'Today is the day!'
            : $daysRemaining.' '.($daysRemaining === 1 ? 'day' : 'days').' to go';

        $caption = "{$event->name} — {$countdownText}. "
            .$event->event_date->format('l, d M Y')
            ." at {$event->location}. See you there!";

        return view('events.countdown-card', compact('event', 'daysRemaining', 'countdownText', 'caption'));
    }

    public function edit(Event $event)
    {
        $this->authorizeEvent($event);
        $templates = $this->getCardTemplates();

        return view('events.edit', compact('event', 'templates'));
    }

    public function update(Request $request, Event $event)
    {
        $this->authorizeEvent($event);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'event_date' => 'required|date',
            'event_time' => 'nullable|date_format:H:i,H:i:s',
            'location' => 'required|string|max:255',
            'hall' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'card_template' => 'required|string',
            'card_background_color' => 'nullable|string|max:7',
            'card_text_color' => 'nullable|string|max:7',
            'card_accent_color' => 'nullable|string|max:7',
            'single_card_min_amount' => 'nullable|numeric|min:1',
            'double_card_min_amount' => 'nullable|numeric|min:1',
            'payment_thank_you_enabled' => 'nullable|boolean',
            'cover_image' => 'nullable|image|max:5120',
            'status' => 'nullable|in:draft,active,completed,cancelled',
        ]);

        $validated['event_time'] = $this->normalizeEventTime($validated['event_time'] ?? null);
        $validated['payment_thank_you_enabled'] = $request->boolean('payment_thank_you_enabled');

        if ($request->hasFile('cover_image')) {
            if ($event->cover_image) {
                Storage::disk('public')->delete($event->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('events/covers', 'public');
        }

        $event->update($validated);

        return redirect()->route('events.show', $event)->with('success', 'Event updated successfully!');
    }

    public function destroy(Event $event)
    {
        $this->authorizeEvent($event);
        $event->delete();

        return redirect()->route('events.index')->with('success', 'Event deleted successfully!');
    }

    private function normalizeEventTime(?string $time): ?string
    {
        if (empty($time)) {
            return null;
        }

        return substr($time, 0, 5);
    }

    private function getCardTemplates(): array
    {
        return [
            'elegant' => ['name' => 'Elegant Gold', 'preview' => 'Formal gold-themed design'],
            'modern' => ['name' => 'Modern Minimal', 'preview' => 'Clean and contemporary'],
            'floral' => ['name' => 'Floral Romance', 'preview' => 'Beautiful floral borders'],
            'royal' => ['name' => 'Royal Classic', 'preview' => 'Traditional royal design'],
            'rustic' => ['name' => 'Rustic Charm', 'preview' => 'Natural wood & green theme'],
        ];
    }
}
