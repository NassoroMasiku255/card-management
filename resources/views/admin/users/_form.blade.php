@php
    $selectedEventIds = collect(old('events', $selectedEventIds ?? []))->map(fn ($id) => (int) $id)->all();
    $assignedEvents = isset($user) ? $user->events : collect();
    $whatsappEventIds = collect(old('whatsapp_events', $assignedEvents->filter(fn ($assigned) => $assigned->pivot->can_send_whatsapp)->pluck('id')->all()))
        ->map(fn ($id) => (int) $id)->all();
    $smsEventIds = collect(old('sms_events', $assignedEvents->filter(fn ($assigned) => $assigned->pivot->can_send_sms)->pluck('id')->all()))
        ->map(fn ($id) => (int) $id)->all();
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
                <h3 class="font-display text-base font-bold text-surface-900">Event Access</h3>
                <p class="text-xs text-surface-500 mt-0.5">
                    Select the events this user can manage. Sharing an event does not change its owner or remove other managers.
                </p>
                <p class="text-xs text-surface-500 mt-1">
                    For each event, tick <span class="font-medium text-surface-700">WhatsApp</span> and/or <span class="font-medium text-surface-700">SMS</span> to let this user send that type of message for that event only.
                    Without a tick they can still see delivery statuses and manage guests and payments.
                </p>
            </div>
            <span class="badge bg-surface-100 text-surface-600">{{ count($selectedEventIds) }} selected</span>
        </div>

        @if(isset($user) && $user->is_admin)
        <p class="mb-3 rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 text-xs text-purple-800">
            This user is an administrator and can already access every event and send WhatsApp and SMS messages for all of them. These selections are for reference only.
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
            @php
                $hasAccess = $isOwner || in_array($event->id, $selectedEventIds, true);
            @endphp
            <div class="event-access-row rounded-lg border border-surface-100 transition hover:bg-surface-50">
            <label class="flex items-start gap-3 px-3 pt-2.5 pb-2 cursor-pointer">
                <input type="checkbox" name="events[]" value="{{ $event->id }}" data-event-access
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
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 border-t border-surface-100 px-3 py-2 pl-10">
                <span class="text-[11px] font-medium text-surface-500">Can send:</span>
                <label class="flex cursor-pointer items-center gap-1.5 text-xs text-surface-700">
                    <input type="checkbox" name="whatsapp_events[]" value="{{ $event->id }}" data-event-send
                        {{ $hasAccess && in_array($event->id, $whatsappEventIds, true) ? 'checked' : '' }}
                        {{ $hasAccess ? '' : 'disabled' }}
                        class="h-3.5 w-3.5 rounded border-surface-300 text-green-600 focus:ring-green-500 disabled:opacity-40">
                    <i class="fab fa-whatsapp text-green-600"></i> WhatsApp
                </label>
                <label class="flex cursor-pointer items-center gap-1.5 text-xs text-surface-700">
                    <input type="checkbox" name="sms_events[]" value="{{ $event->id }}" data-event-send
                        {{ $hasAccess && in_array($event->id, $smsEventIds, true) ? 'checked' : '' }}
                        {{ $hasAccess ? '' : 'disabled' }}
                        class="h-3.5 w-3.5 rounded border-surface-300 text-blue-600 focus:ring-blue-500 disabled:opacity-40">
                    <i class="fas fa-comment-sms text-blue-600"></i> SMS
                </label>
            </div>
            </div>
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

@push('scripts')
<script>
    // Send ticks only apply to events the user can access.
    document.querySelectorAll('.event-access-row').forEach(row => {
        const access = row.querySelector('[data-event-access]');
        const sendTicks = row.querySelectorAll('[data-event-send]');
        if (!access || access.disabled) return;

        access.addEventListener('change', () => {
            sendTicks.forEach(tick => {
                tick.disabled = !access.checked;
                if (!access.checked) tick.checked = false;
            });
        });
    });
</script>
@endpush
