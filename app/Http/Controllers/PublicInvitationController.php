<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\Request;

class PublicInvitationController extends Controller
{
    public function show(string $qrCode)
    {
        $invitation = $this->findInvitation($qrCode);
        $invitation->loadMissing(['guest', 'event']);

        $event = $invitation->event;
        $guest = $invitation->guest;
        $cardType = $event->cardTypeForAmount((float) $guest->paid_amount);
        $daysRemaining = max(0, (int) now()->startOfDay()->diffInDays($event->event_date->startOfDay(), false));
        $publicUrl = route('invitation.public', $qrCode);

        return view('invitations.public', compact(
            'invitation',
            'event',
            'guest',
            'cardType',
            'daysRemaining',
            'publicUrl'
        ));
    }

    public function card(string $qrCode)
    {
        $invitation = $this->findInvitation($qrCode);
        $invitation->loadMissing(['guest', 'event']);

        return view('invitations.card', [
            'event' => $invitation->event,
            'guest' => $invitation->guest,
            'backUrl' => route('invitation.public', $qrCode),
        ]);
    }

    public function rsvp(Request $request, string $qrCode)
    {
        $invitation = $this->findInvitation($qrCode);

        $validated = $request->validate([
            'rsvp_status' => 'required|in:attending,not_attending',
        ]);

        $invitation->markRsvp($validated['rsvp_status']);

        $message = $validated['rsvp_status'] === 'attending'
            ? 'Asante! Your attendance has been confirmed.'
            : 'Your response has been recorded. Thank you for letting us know.';

        return redirect()
            ->route('invitation.public', $qrCode)
            ->with('success', $message);
    }

    private function findInvitation(string $qrCode): Invitation
    {
        return Invitation::where('qr_code_data', $qrCode)->firstOrFail();
    }
}
