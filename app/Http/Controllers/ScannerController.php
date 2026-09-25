<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScannerController extends Controller
{
    public function index(Event $event)
    {
        $this->authorizeEvent($event);

        $stats = [
            'total_guests' => $event->guests()->count(),
            'checked_in' => $event->invitations()->where('attendance_status', 'attended')->count(),
            'remaining' => $event->invitations()->where('attendance_status', 'pending')->count(),
        ];

        return view('scanner.index', compact('event', 'stats'));
    }

    public function verify(Request $request, Event $event)
    {
        $this->authorizeEvent($event);

        $request->validate([
            'qr_code' => 'required|string',
        ]);

        $qrData = $request->input('qr_code');

        $invitation = Invitation::where('event_id', $event->id)
            ->where('qr_code_data', $qrData)
            ->with('guest')
            ->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR Code. Guest not found for this event.',
            ], 404);
        }

        $guest = $invitation->guest;

        if ($invitation->attendance_status === 'attended') {
            return response()->json([
                'success' => false,
                'message' => "Guest '{$guest->full_name}' has already checked in at {$invitation->checked_in_at->format('H:i')}.",
                'guest' => [
                    'name' => $guest->full_name,
                    'unique_id' => $guest->unique_id,
                    'card_type' => $guest->card_type,
                    'already_checked_in' => true,
                    'checked_in_at' => $invitation->checked_in_at->format('d M Y H:i'),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Guest verified successfully!',
            'guest' => [
                'id' => $guest->id,
                'name' => $guest->full_name,
                'unique_id' => $guest->unique_id,
                'phone' => $guest->phone_number,
                'card_type' => $guest->card_type,
                'category' => $guest->category,
                'table_number' => $guest->table_number,
                'rsvp_status' => $invitation->rsvp_status,
                'invitation_id' => $invitation->id,
            ],
        ]);
    }

    public function checkIn(Request $request, Event $event)
    {
        $this->authorizeEvent($event);

        $request->validate([
            'invitation_id' => 'required|integer',
        ]);

        $invitation = Invitation::where('event_id', $event->id)
            ->where('id', $request->input('invitation_id'))
            ->with('guest')
            ->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation not found.',
            ], 404);
        }

        if ($invitation->attendance_status === 'attended') {
            return response()->json([
                'success' => false,
                'message' => 'Guest already checked in.',
            ]);
        }

        $invitation->markAttended();

        ActivityLog::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'guest_checked_in',
            'description' => "Guest '{$invitation->guest->full_name}' checked in.",
        ]);

        $stats = [
            'total_guests' => $event->guests()->count(),
            'checked_in' => $event->invitations()->where('attendance_status', 'attended')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => "Guest '{$invitation->guest->full_name}' checked in successfully!",
            'stats' => $stats,
        ]);
    }
}
