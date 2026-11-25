<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        $users = User::with('events')
            ->withCount('events')
            ->orderByDesc('is_admin')
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $this->authorizeAdmin();

        $events = Event::with(['user', 'users'])->orderBy('name')->get();

        return view('admin.users.create', compact('events'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'events' => 'nullable|array',
            'events.*' => 'integer|exists:events,id',
            'whatsapp_events' => 'nullable|array',
            'whatsapp_events.*' => 'integer',
            'sms_events' => 'nullable|array',
            'sms_events.*' => 'integer',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $this->syncEventAssignments($user, $validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} created and event access assigned.");
    }

    public function edit(User $user)
    {
        $this->authorizeAdmin();

        $user->load('events');
        $events = Event::with(['user', 'users'])->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'events'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'events' => 'nullable|array',
            'events.*' => 'integer|exists:events,id',
            'whatsapp_events' => 'nullable|array',
            'whatsapp_events.*' => 'integer',
            'sms_events' => 'nullable|array',
            'sms_events.*' => 'integer',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            ...(! empty($validated['password']) ? ['password' => $validated['password']] : []),
        ];

        $user->update($updateData);

        $this->syncEventAssignments($user, $validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} updated.");
    }

    /**
     * Sync the events a user can manage, together with the message types
     * (WhatsApp, SMS) they are allowed to send on each of those events.
     */
    private function syncEventAssignments(User $user, array $validated): void
    {
        $selectedEventIds = Event::whereIn('id', $validated['events'] ?? [])->pluck('id')->all();

        $ownedEventIds = Event::where('user_id', $user->id)->pluck('id')->all();

        $whatsappEventIds = array_map('intval', $validated['whatsapp_events'] ?? []);
        $smsEventIds = array_map('intval', $validated['sms_events'] ?? []);

        $assignments = [];

        foreach (array_unique(array_merge($selectedEventIds, $ownedEventIds)) as $eventId) {
            $assignments[$eventId] = [
                'can_send_whatsapp' => in_array($eventId, $whatsappEventIds, true),
                'can_send_sms' => in_array($eventId, $smsEventIds, true),
            ];
        }

        $user->events()->sync($assignments);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Administrator access required.');
    }
}
