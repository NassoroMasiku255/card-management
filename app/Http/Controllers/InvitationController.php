<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\ActivityLog;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvitationController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsapp)
    {
    }

    public function index(Event $event)
    {
        $this->authorizeEvent($event);
        $invitations = $event->invitations()->with('guest')->paginate(25);

        return view('invitations.index', [
            'event' => $event,
            'invitations' => $invitations,
            'stats' => $this->sendStats($event),
        ]);
    }

    public function card(Event $event, Guest $guest)
    {
        $this->authorizeEvent($event);
        return view('invitations.card', compact('event', 'guest'));
    }

    public function send(Request $request, Event $event, Invitation $invitation)
    {
        $this->authorizeEvent($event);

        $success = $this->whatsapp->sendInvitation($invitation);

        $message = $success
            ? "Invitation sent to {$invitation->guest->full_name}!"
            : "Failed to send invitation to {$invitation->guest->full_name}.";

        if ($success) {
            ActivityLog::create([
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'action' => 'invitation_sent',
                'description' => "Invitation sent to {$invitation->guest->full_name}.",
            ]);
        }

        if ($request->wantsJson()) {
            return $this->invitationJsonResponse($event, $invitation, $success, $message);
        }

        return $success
            ? back()->with('success', $message)
            : back()->with('error', $message);
    }

    public function sendAll(Event $event)
    {
        $this->authorizeEvent($event);

        $pendingInvitations = $event->invitations()
            ->where('send_status', 'pending')
            ->with('guest')
            ->get();

        if ($pendingInvitations->isEmpty()) {
            return back()->with('info', 'No pending invitations to send.');
        }

        $sent = 0;
        $failed = 0;

        foreach ($pendingInvitations as $invitation) {
            if ($this->whatsapp->sendInvitation($invitation)) {
                $sent++;
            } else {
                $failed++;
            }
            usleep(500000); // 0.5 second delay between messages
        }

        ActivityLog::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'bulk_invitations_sent',
            'description' => "Bulk send: {$sent} sent, {$failed} failed.",
        ]);

        return back()->with('success', "Sent {$sent} invitations. {$failed} failed.");
    }

    public function resend(Request $request, Event $event, Invitation $invitation)
    {
        $this->authorizeEvent($event);

        $invitation->update(['send_status' => 'pending']);

        $success = $this->whatsapp->sendInvitation($invitation);

        $message = $success
            ? "Invitation resent to {$invitation->guest->full_name}!"
            : 'Failed to resend invitation.';

        if ($request->wantsJson()) {
            return $this->invitationJsonResponse($event, $invitation, $success, $message);
        }

        return $success
            ? back()->with('success', $message)
            : back()->with('error', $message);
    }

    private function invitationJsonResponse(Event $event, Invitation $invitation, bool $success, string $message)
    {
        $invitation->refresh();

        return response()->json([
            'success' => $success,
            'message' => $message,
            'invitation' => [
                'id' => $invitation->id,
                'send_status' => $invitation->send_status,
                'sent_at' => optional($invitation->sent_at)->format('d/m H:i'),
            ],
            'stats' => $this->sendStats($event),
        ]);
    }

    /**
     * Send-status breakdown for the event, resolved in a single grouped query
     * instead of one COUNT per status.
     */
    private function sendStats(Event $event): array
    {
        $counts = $event->invitations()
            ->select('send_status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('send_status')
            ->pluck('aggregate', 'send_status');

        return [
            'total' => (int) $counts->sum(),
            'sent' => (int) $counts->get('sent', 0),
            'pending' => (int) $counts->get('pending', 0),
            'failed' => (int) $counts->get('failed', 0),
        ];
    }
}
