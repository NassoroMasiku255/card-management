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

        $events = Event::with('user')->orderBy('name')->get();

        return view('admin.users.create', compact('events'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'can_send_whatsapp' => 'nullable|boolean',
            'events' => 'nullable|array',
            'events.*' => 'integer|exists:events,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'can_send_whatsapp' => $request->boolean('can_send_whatsapp'),
        ]);

        $this->syncEventAssignments($user, $validated['events'] ?? []);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} created and event access assigned.");
    }

    public function edit(User $user)
    {
        $this->authorizeAdmin();

        $user->load('events');
        $events = Event::with('user')->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'events'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'can_send_whatsapp' => 'nullable|boolean',
            'events' => 'nullable|array',
            'events.*' => 'integer|exists:events,id',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            ...(! empty($validated['password']) ? ['password' => $validated['password']] : []),
        ];

        if ($request->has('can_send_whatsapp')) {
            $updateData['can_send_whatsapp'] = $request->boolean('can_send_whatsapp');
        }

        $user->update($updateData);

        $this->syncEventAssignments($user, $validated['events'] ?? []);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} updated.");
    }

    private function syncEventAssignments(User $user, array $eventIds): void
    {
        $selectedEventIds = Event::whereIn('id', $eventIds)->pluck('id')->all();

        Event::whereIn('id', $selectedEventIds)->update(['user_id' => $user->id]);

        if (Auth::id() !== $user->id) {
            Event::where('user_id', $user->id)
                ->whereNotIn('id', $selectedEventIds)
                ->update(['user_id' => Auth::id()]);
        }
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Administrator access required.');
    }
}
