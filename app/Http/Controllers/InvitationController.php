<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvitationController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsapp) {}

    public function index(Event $event)
    {
        $this->authorizeEvent($event);
        $invitations = $event->invitations()->with(['guest', 'latestInvitationMessage'])->orderBy('id')->paginate(25);

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

    public function pending(Event $event)
    {
        $this->authorizeEvent($event);

        $invitations = $event->invitations()
            ->where('send_status', 'pending')
            ->with('guest')
            ->get()
            ->sortBy(fn ($invitation) => $invitation->guest->full_name)
            ->values();

        return response()->json([
            'invitations' => $invitations->map(fn ($invitation) => [
                'id' => $invitation->id,
                'guest_id' => $invitation->guest_id,
                'guest_name' => $invitation->guest->full_name,
                'url' => route('events.invitations.send', [$event, $invitation]),
                'card_image_url' => $invitation->card_image_url,
            ]),
        ]);
    }

    public function send(Request $request, Event $event, Invitation $invitation)
    {
        $this->authorizeEvent($event);
        $this->validateCardImage($request);
        $this->storeCardImage($request, $event, $invitation);

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
        $this->validateCardImage($request);
        $this->storeCardImage($request, $event, $invitation);

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

    public function sendMessage(Request $request, Event $event, Guest $guest)
    {
        $this->authorizeEvent($event);

        $request->validate([
            'template_name' => 'required|string',
            'message_type' => 'required|string|in:reminder,invitation,other',
        ]);

        $invitation = $guest->invitation;

        if (! $invitation) {
            $invitation = Invitation::create([
                'event_id' => $event->id,
                'guest_id' => $guest->id,
                'qr_code_data' => $guest->unique_id,
            ]);
        }

        $success = $request->message_type === 'reminder'
            ? $this->whatsapp->sendReminder($guest, $invitation, $request->template_name)
            : $this->whatsapp->sendInvitation($invitation, $request->template_name);

        $latestMessage = WhatsAppMessage::where('guest_id', $guest->id)->latest('id')->first();

        $message = $success
            ? ucfirst($request->message_type)." message sent to {$guest->full_name}! Status: "
                .($latestMessage?->status_label ?? 'Accepted').'.'
            : 'Failed to send '.$request->message_type." message to {$guest->full_name}."
                .($latestMessage?->error_message ? " ({$latestMessage->error_message})" : '');

        if ($success) {
            ActivityLog::create([
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'action' => 'whatsapp_message_sent',
                'description' => ucfirst($request->message_type)." message (template: {$request->template_name}) sent to {$guest->full_name}.",
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'guest_id' => $guest->id,
                'status' => $latestMessage ? [
                    'state' => $latestMessage->status,
                    'label' => $latestMessage->status_label,
                    'badge_class' => $latestMessage->status_badge_class,
                    'message_type' => ucfirst($latestMessage->message_type),
                    'updated_at' => optional($latestMessage->status_updated_at)->diffForHumans(),
                    'error_message' => $latestMessage->error_message,
                ] : null,
            ]);
        }

        return $success
            ? back()->with('success', $message)
            : back()->with('error', $message);
    }

    private function invitationJsonResponse(Event $event, Invitation $invitation, bool $success, string $message)
    {
        $invitation->refresh()->load('latestInvitationMessage');

        return response()->json([
            'success' => $success,
            'message' => $message,
            'invitation' => [
                'id' => $invitation->id,
                'send_status' => $invitation->send_status,
                'sent_at' => optional($invitation->sent_at)->format('d/m H:i'),
                'card_image_url' => $invitation->card_image_url,
                'whatsapp_status' => $invitation->latestInvitationMessage ? [
                    'label' => $invitation->latestInvitationMessage->status_label,
                    'badge_class' => $invitation->latestInvitationMessage->status_badge_class,
                ] : null,
            ],
            'stats' => $this->sendStats($event),
        ]);
    }

    private function validateCardImage(Request $request): void
    {
        $request->validate([
            'card_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ]);
    }

    private function storeCardImage(Request $request, Event $event, Invitation $invitation): void
    {
        if (! $request->hasFile('card_image')) {
            return;
        }

        $path = $request->file('card_image')->store("invitation-cards/{$event->id}", 'public');

        if ($invitation->card_image_path) {
            Storage::disk('public')->delete($invitation->card_image_path);
        }

        $invitation->update(['card_image_path' => $path]);
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
