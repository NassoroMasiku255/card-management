<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $events = ($user->isAdmin() ? Event::query() : $user->events())
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
            ->latest('events.created_at')
            ->get();

        $totalEvents = $events->count();
        $totalGuests = $events->sum('guests_count');
        $activeEvents = $events->where('status', 'active')->count();
        $totalInvitations = $events->sum('invitations_count');
        $totalInvitationsSent = $events->sum('invitations_sent_count');
        $rsvpAttending = $events->sum('rsvp_attending_count');
        $rsvpNotAttending = $events->sum('rsvp_not_attending_count');
        $rsvpPending = $events->sum('rsvp_pending_count');
        $checkedIn = $events->sum('checked_in_count');
        $totalPledged = (float) $events->sum('total_pledged');
        $totalPaid = (float) $events->sum('total_paid');
        $outstanding = max(0, $totalPledged - $totalPaid);
        $collectionRate = $totalPledged > 0 ? (int) min(100, round(($totalPaid / $totalPledged) * 100)) : ($totalPaid > 0 ? 100 : 0);
        $estimatedAttendance = $events->sum(fn ($event) => $event->single_guests_count + ($event->double_guests_count * 2));

        $upcoming = $events->where('event_date', '>=', now()->startOfDay())->sortBy('event_date')->first();
        $topContributors = Guest::query()
            ->whereIn('event_id', $events->pluck('id'))
            ->where('paid_amount', '>', 0)
            ->with('event')
            ->orderByDesc('paid_amount')
            ->take(5)
            ->get();

        $teamStats = $user->isAdmin()
            ? [
                'total' => User::count(),
                'admins' => User::where('is_admin', true)->count(),
                'whatsapp' => User::where('can_send_whatsapp', true)->count(),
                'owners' => $events->pluck('user_id')->filter()->unique()->count(),
            ]
            : null;

        $activityLogs = ActivityLog::whereIn('event_id', $events->pluck('id'))
            ->with(['event', 'user'])
            ->latest()
            ->take(6)
            ->get();

        return view('dashboard', compact(
            'events',
            'totalEvents',
            'totalGuests',
            'activeEvents',
            'totalInvitations',
            'totalInvitationsSent',
            'rsvpAttending',
            'rsvpNotAttending',
            'rsvpPending',
            'checkedIn',
            'totalPledged',
            'totalPaid',
            'outstanding',
            'collectionRate',
            'estimatedAttendance',
            'upcoming',
            'topContributors',
            'teamStats',
            'activityLogs'
        ));
    }
}
