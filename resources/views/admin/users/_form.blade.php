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

    <div class="rounded-2xl border border-surface-200 bg-surface-50/70 p-4">
        <label class="flex cursor-pointer items-start gap-3">
            <input type="hidden" name="can_send_whatsapp" value="0">
            <input type="checkbox" name="can_send_whatsapp" value="1"
                {{ old('can_send_whatsapp', $user->can_send_whatsapp ?? false) ? 'checked' : '' }}
                class="mt-0.5 h-4 w-4 rounded border-surface-300 text-green-600 focus:ring-green-500">
            <span>
                <span class="flex flex-wrap items-center gap-2 text-sm font-semibold text-surface-800">
                    <i class="fab fa-whatsapp text-green-600"></i>
                    Allow WhatsApp sending
                    @if(isset($user) && $user->is_admin)
                    <span class="badge bg-purple-100 text-purple-700">Administrators always allowed</span>
                    @endif
                </span>
                <span class="mt-1 block text-xs leading-relaxed text-surface-500">
                    Can send invitations and reminders from the guest and invitation pages.
                    Users without this permission still see delivery statuses and can manage guests and payments.
                </span>
            </span>
        </label>
    </div>

    <div class="border-t border-surface-100 pt-6">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
            <div>
                <h3 class="font-display text-base font-bold text-surface-900">Event Access</h3>
                <p class="text-xs text-surface-500 mt-0.5">
                    Select the events this user can manage. Sharing an event does not change its owner or remove other managers.
                </p>
            </div>
            <span class="badge bg-surface-100 text-surface-600">{{ count($selectedEventIds) }} selected</span>
        </div>

        @if(isset($user) && $user->is_admin)
        <p class="mb-3 rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 text-xs text-purple-800">
            This user is an administrator and can already access every event. These selections are for reference only.
        </p>
        @else
        <p class="mb-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-800">
            Multiple people can manage the same event. Unchecking an event only removes this user&rsquo;s access; other managers are not affected. Events owned by this user always stay accessible.
        </p>
        @endif

        @if($events->isEmpty())
        <div class="rounded-xl border border-dashed border-surface-300 px-4 py-8 text-center text-sm text-surface-400">
            No events exist yet. Create an event first, then assign it here.
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-80 overflow-y-auto rounded-xl border border-surface-200 p-2">
            @foreach($events as $event)
            @php
                $isOwner = isset($user) && $event->user_id === $user->id;
                $otherManagers = $event->users->where('id', '!=', $event->user_id);
            @endphp
            <label class="flex items-start gap-3 rounded-lg border border-surface-100 px-3 py-2.5 cursor-pointer hover:bg-surface-50 transition">
                <input type="checkbox" name="events[]" value="{{ $event->id }}"
                    {{ $isOwner || in_array($event->id, $selectedEventIds, true) ? 'checked' : '' }}
                    {{ $isOwner ? 'disabled' : '' }}
                    class="mt-0.5 h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                @if($isOwner)
                <input type="hidden" name="events[]" value="{{ $event->id }}">
                @endif
                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-center gap-1.5">
                        <span class="block text-sm font-medium text-surface-800 truncate">{{ $event->name }}</span>
                        @if($isOwner)
                        <span class="badge bg-gold-100 text-gold-700">Owner</span>
                        @endif
                    </span>
                    <span class="block text-[11px] text-surface-400">
                        {{ $event->event_date->format('d M Y') }} &middot; {{ $event->location }}
                    </span>
                    <span class="block text-[10px] text-surface-400 mt-0.5">
                        Owner: {{ $event->user?->name ?? 'Unassigned' }}
                    </span>
                    @if($otherManagers->isNotEmpty())
                    <span class="mt-1 block truncate text-[10px] font-medium text-blue-600">
                        Also managed by: {{ $otherManagers->pluck('name')->join(', ') }}
                    </span>
                    @endif
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
