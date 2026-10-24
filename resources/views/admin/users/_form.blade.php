@php
    $selectedEventIds = collect(old('events', $selectedEventIds ?? []))->map(fn ($id) => (int) $id)->all();
@endphp

<div class="card-surface p-6 md:p-8 space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="label-field">Full Name *</label>
            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="input-field" placeholder="User full name">
        </div>
        <div>
            <label class="label-field">Email Address *</label>
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required class="input-field" placeholder="user@example.com">
        </div>
        <div>
            <label class="label-field">Password {{ isset($user) ? '(leave blank to keep current)' : '*' }}</label>
            <input type="password" name="password" {{ isset($user) ? '' : 'required' }} class="input-field" autocomplete="new-password">
        </div>
        <div>
            <label class="label-field">Confirm Password</label>
            <input type="password" name="password_confirmation" {{ isset($user) ? '' : 'required' }} class="input-field" autocomplete="new-password">
        </div>
    </div>

    <div class="border-t border-surface-100 pt-6">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
            <div>
                <h3 class="font-display text-base font-bold text-surface-900">Assigned Events</h3>
                <p class="text-xs text-surface-500 mt-0.5">
                    Selecting an event moves it to this user. They will only see these events and their activities.
                </p>
            </div>
            <span class="badge bg-surface-100 text-surface-600">{{ count($selectedEventIds) }} selected</span>
        </div>

        @if(isset($user) && $user->is_admin)
        <p class="mb-3 rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 text-xs text-purple-800">
            This user is an administrator and can already access every event. Assigning an event here only transfers its ownership.
        </p>
        @elseif(isset($user) && Auth::id() !== $user->id)
        <p class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
            Unchecking an event currently owned by this user returns it to your account.
        </p>
        @endif

        @if($events->isEmpty())
        <div class="rounded-xl border border-dashed border-surface-300 px-4 py-8 text-center text-sm text-surface-400">
            No events exist yet. Create an event first, then assign it here.
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-80 overflow-y-auto rounded-xl border border-surface-200 p-2">
            @foreach($events as $event)
            <label class="flex items-start gap-3 rounded-lg border border-surface-100 px-3 py-2.5 cursor-pointer hover:bg-surface-50 transition">
                <input type="checkbox" name="events[]" value="{{ $event->id }}"
                    {{ in_array($event->id, $selectedEventIds, true) ? 'checked' : '' }}
                    class="mt-0.5 h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-surface-800 truncate">{{ $event->name }}</span>
                    <span class="block text-[11px] text-surface-400">
                        {{ $event->event_date->format('d M Y') }} &middot; {{ $event->location }}
                    </span>
                    <span class="block text-[10px] text-surface-400 mt-0.5">
                        Owner: {{ $event->user?->name ?? 'Unassigned' }}
                    </span>
                </span>
            </label>
            @endforeach
        </div>
        @endif
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-surface-100 pt-5">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> {{ isset($user) ? 'Save User' : 'Create User' }}
        </button>
    </div>
</div>
