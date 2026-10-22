<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $this->authorizeEvent($event);

        $query = $event->guests()->with(['invitation', 'latestWhatsAppMessage']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('unique_id', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($cardType = $request->input('card_type')) {
            $query->where('card_type', $cardType);
        }

        if ($rsvp = $request->input('rsvp')) {
            $query->whereHas('invitation', function ($q) use ($rsvp) {
                $q->where('rsvp_status', $rsvp);
            });
        }

        if ($messageStatus = $request->input('message_status')) {
            $query->whereHas('latestWhatsAppMessage', function ($q) use ($messageStatus) {
                $q->where('status', $messageStatus);
            });
        }

        if ($paymentStatus = $request->input('payment_status')) {
            match ($paymentStatus) {
                'paid' => $query->where('paid_amount', '>', 0)
                    ->where(function ($q) {
                        $q->where('amount_contributed', '<=', 0)
                            ->orWhereColumn('paid_amount', '>=', 'amount_contributed');
                    }),
                'partial' => $query->where('paid_amount', '>', 0)
                    ->where('amount_contributed', '>', 0)
                    ->whereColumn('paid_amount', '<', 'amount_contributed'),
                'overpaid' => $query->where('amount_contributed', '>', 0)
                    ->whereColumn('paid_amount', '>', 'amount_contributed'),
                'unpaid' => $query->where('paid_amount', '<=', 0),
                default => null,
            };
        }

        $guests = $query->latest()->paginate(25)->appends($request->query());

        return view('guests.index', compact('event', 'guests'));
    }

    public function export(Event $event)
    {
        $this->authorizeEvent($event);

        $guests = $event->guests()->with(['invitation', 'latestWhatsAppMessage'])->get();

        $headers = ['Unique ID', 'Full Name', 'Phone Number', 'Card Type', 'Pledged Amount', 'Amount Paid', 'Balance', 'Payment Status', 'Last Payment At', 'Category', 'Table', 'RSVP Status', 'Send Status', 'Last Message Type', 'Last Message Status', 'Last Message At', 'Attendance'];

        $rows = $guests->map(function ($guest) {
            return [
                $guest->unique_id,
                $guest->full_name,
                $guest->phone_number,
                $guest->card_type,
                $guest->amount_contributed,
                $guest->paid_amount,
                $guest->balance,
                $guest->payment_status,
                optional($guest->last_payment_at)->format('Y-m-d H:i') ?? '',
                $guest->category ?? '',
                $guest->table_number ?? '',
                $guest->invitation?->rsvp_status ?? 'N/A',
                $guest->invitation?->send_status ?? 'N/A',
                $guest->latestWhatsAppMessage?->message_type ?? 'N/A',
                $guest->latestWhatsAppMessage?->status ?? 'N/A',
                optional($guest->latestWhatsAppMessage?->status_updated_at)->format('Y-m-d H:i') ?? '',
                $guest->invitation?->attendance_status ?? 'N/A',
            ];
        });

        $content = implode(',', $headers)."\n";
        foreach ($rows as $row) {
            $content .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', $v).'"', $row))."\n";
        }

        $filename = 'guests_'.$event->slug.'_'.date('Y-m-d').'.csv';

        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function create(Event $event)
    {
        $this->authorizeEvent($event);

        return view('guests.create', compact('event'));
    }

    public function store(Request $request, Event $event)
    {
        $this->authorizeEvent($event);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'amount_contributed' => 'nullable|numeric|min:0',
            'card_type' => 'required|in:single,double',
            'email' => 'nullable|email',
            'table_number' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $validated['event_id'] = $event->id;
        $guest = Guest::create($validated);

        Invitation::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'qr_code_data' => $guest->unique_id,
        ]);

        ActivityLog::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'guest_added',
            'description' => "Guest '{$guest->full_name}' was added.",
        ]);

        return redirect()->route('events.guests.index', $event)->with('success', 'Guest added successfully!');
    }

    public function edit(Event $event, Guest $guest)
    {
        $this->authorizeEvent($event);
        $this->ensureGuestBelongsToEvent($event, $guest);

        $guest->load('payments');

        return view('guests.edit', compact('event', 'guest'));
    }

    public function update(Request $request, Event $event, Guest $guest)
    {
        $this->authorizeEvent($event);
        $this->ensureGuestBelongsToEvent($event, $guest);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'amount_contributed' => 'nullable|numeric|min:0',
            'card_type' => 'required|in:single,double',
            'email' => 'nullable|email',
            'table_number' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $guest->update($validated);

        return redirect()->route('events.guests.index', $event)->with('success', 'Guest updated successfully!');
    }

    public function destroy(Event $event, Guest $guest)
    {
        $this->authorizeEvent($event);
        $this->ensureGuestBelongsToEvent($event, $guest);
        $guest->delete();

        return redirect()->route('events.guests.index', $event)->with('success', 'Guest removed successfully!');
    }

    public function import(Request $request, Event $event)
    {
        $this->authorizeEvent($event);

        $request->validate([
            'file' => 'required|file|extensions:csv,xlsx,xls|max:10240',
        ]);

        $service = new ExcelImportService;
        $results = $service->import($request->file('file'), $event);

        ActivityLog::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'guests_imported',
            'description' => "Imported {$results['success']} guests, {$results['failed']} failed.",
            'metadata' => $results,
        ]);

        if ($results['success'] > 0) {
            $message = "Successfully imported {$results['success']} guests.";
            if ($results['failed'] > 0) {
                $message .= " {$results['failed']} rows failed.";
            }

            return back()->with('success', $message)->with('import_errors', $results['errors']);
        }

        return back()->with('error', 'Import failed.')->with('import_errors', $results['errors']);
    }

    public function showImport(Event $event)
    {
        $this->authorizeEvent($event);

        return view('guests.import', compact('event'));
    }

    public function downloadTemplate()
    {
        $headers = ['Full Name', 'Phone Number', 'Pledged Amount', 'Amount Paid', 'Card Type', 'Email', 'Table Number', 'Category', 'Notes'];
        $sample = ['John Doe', '0712345678', '50000', '25000', 'single', 'john@email.com', 'Table 1', 'Family', 'VIP guest'];

        $content = implode(',', $headers)."\n".implode(',', $sample)."\n";

        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="guest_import_template.csv"');
    }

    private function ensureGuestBelongsToEvent(Event $event, Guest $guest): void
    {
        abort_unless($guest->event_id === $event->id, 404);
    }
}
