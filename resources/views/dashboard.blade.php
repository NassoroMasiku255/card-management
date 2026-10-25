@extends('layouts.app')

@section('title', 'Dashboard - NiaLike')

@section('content')
@php
    $hour = now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', Auth::user()->name)[0];

    $deliveryRate = $totalInvitations > 0 ? (int) round(($totalInvitationsSent / $totalInvitations) * 100) : 0;
    $checkinRate = $totalGuests > 0 ? (int) round(($checkedIn / $totalGuests) * 100) : 0;
    $activeRate = $totalEvents > 0 ? (int) round(($activeEvents / $totalEvents) * 100) : 0;

    $rsvpTotal = max($rsvpAttending + $rsvpNotAttending + $rsvpPending, 1);
    $attendPct = round(($rsvpAttending / $rsvpTotal) * 100);
    $notAttendPct = round(($rsvpNotAttending / $rsvpTotal) * 100);
    $pendingPct = max(100 - $attendPct - $notAttendPct, 0);

    $pendingInvitations = max(0, $totalInvitations - $totalInvitationsSent);
    $rsvpResponded = $rsvpAttending + $rsvpNotAttending;
    $rsvpResponseRate = $rsvpTotal > 0 ? (int) round(($rsvpResponded / $rsvpTotal) * 100) : 0;

    $money = function ($amount) {
        $amount = (float) $amount;
        if ($amount >= 1000000) return number_format($amount / 1000000, 1).'M';
        if ($amount >= 1000) return number_format($amount / 1000, 0).'K';
        return number_format($amount);
    };

    $upcomingDays = $upcoming
        ? max(0, (int) now()->startOfDay()->diffInDays($upcoming->event_date->startOfDay(), false))
        : null;
    $upcomingAccent = $upcoming ? ($upcoming->card_accent_color ?: '#e11d48') : '#e11d48';
    $upcomingDelivery = $upcoming && $upcoming->invitations_count > 0
        ? (int) round(($upcoming->invitations_sent_count / $upcoming->invitations_count) * 100)
        : 0;
@endphp

<!-- Greeting -->
<div class="mb-7 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-600">Dashboard</p>
        <h1 class="mt-1.5 font-display text-2xl font-bold text-surface-900 sm:text-3xl">
            {{ $greeting }}, {{ $firstName }} <span class="inline-block">👋</span>
        </h1>
        <p class="mt-1.5 text-sm text-surface-500">
            Here is the full picture of your events on {{ now()->format('l, d M Y') }}.
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('events.index') }}" class="btn btn-outline">
            <i class="fas fa-calendar-days text-surface-400"></i> All Events
        </a>
        <a href="{{ route('events.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Event
        </a>
    </div>
</div>

<!-- Stat cards -->
<div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <div class="card-surface card-surface-hover p-5 transition">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-surface-400">Total Events</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-surface-900">{{ number_format($totalEvents) }}</p>
                <p class="mt-1 text-xs text-surface-500"><span class="font-semibold text-green-600">{{ $activeEvents }} active</span> · {{ $activeRate }}% of all</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                <i class="fas fa-calendar-days"></i>
            </div>
        </div>
        <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-surface-100">
            <div class="h-full rounded-full bg-blue-500" style="width: {{ $activeRate }}%;"></div>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-5 transition">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-surface-400">Total Guests</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-surface-900">{{ number_format($totalGuests) }}</p>
                <p class="mt-1 text-xs text-surface-500"><span class="font-semibold text-primary-600">{{ number_format($estimatedAttendance) }}</span> expected seats</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-2 text-[11px] text-surface-400">
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-primary-400"></span> Single &amp; double cards included</span>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-5 transition">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-surface-400">Invitations Sent</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-surface-900">{{ number_format($totalInvitationsSent) }}</p>
                <p class="mt-1 text-xs text-surface-500"><span class="font-semibold text-green-600">{{ $deliveryRate }}%</span> delivered · {{ number_format($pendingInvitations) }} pending</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-50 text-green-600">
                <i class="fas fa-paper-plane"></i>
            </div>
        </div>
        <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-surface-100">
            <div class="h-full rounded-full bg-green-500" style="width: {{ $deliveryRate }}%;"></div>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-5 transition">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-surface-400">Checked In</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-surface-900">{{ number_format($checkedIn) }}</p>
                <p class="mt-1 text-xs text-surface-500"><span class="font-semibold text-gold-600">{{ $checkinRate }}%</span> attendance · {{ number_format($rsvpAttending) }} confirmed</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gold-50 text-gold-600">
                <i class="fas fa-qrcode"></i>
            </div>
        </div>
        <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-surface-100">
            <div class="h-full rounded-full bg-gold-500" style="width: {{ $checkinRate }}%;"></div>
        </div>
    </div>
</div>

<!-- Spotlight + quick actions -->
<div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2">
        @if($upcoming)
        @php
            $cover = $upcoming->cover_image ? asset('storage/'.$upcoming->cover_image) : null;
            $heroBg = $cover
                ? "linear-gradient(115deg, rgba(18,18,20,.94) 10%, rgba(18,18,20,.62) 58%, rgba(18,18,20,.35)), url('{$cover}')"
                : "linear-gradient(125deg, {$upcomingAccent} 0%, #881337 58%, #18181b 100%)";
        @endphp
        <div class="relative h-full overflow-hidden rounded-2xl p-6 text-white shadow-soft-lg sm:p-8"
            style="background: {{ $heroBg }}; background-size: cover; background-position: center;">
            <div class="absolute -right-12 -top-16 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-20 right-1/3 h-44 w-44 rounded-full bg-gold-400/20 blur-3xl"></div>

            <div class="relative flex h-full flex-col">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-white/85 backdrop-blur">
                        <i class="fas fa-star text-gold-300"></i> Next up
                    </span>
                    <div class="flex items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 py-2 backdrop-blur">
                        <span class="font-display text-2xl font-bold leading-none">{{ $upcomingDays }}</span>
                        <span class="text-[10px] font-medium uppercase leading-tight tracking-wider text-white/65">{{ $upcomingDays === 1 ? 'day' : 'days' }}<br>to go</span>
                    </div>
                </div>

                <div class="mt-auto pt-10">
                    <h2 class="font-display text-2xl font-bold leading-tight sm:text-3xl">{{ $upcoming->name }}</h2>
                    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/75">
                        <span><i class="fas fa-calendar mr-1.5 text-white/50"></i>{{ $upcoming->event_date->format('l, d M Y') }}</span>
                        @if($upcoming->event_time)
                        <span><i class="fas fa-clock mr-1.5 text-white/50"></i>{{ $upcoming->event_time }}</span>
                        @endif
                        <span><i class="fas fa-location-dot mr-1.5 text-white/50"></i>{{ $upcoming->location }}</span>
                        @if($upcoming->hall)
                        <span><i class="fas fa-building mr-1.5 text-white/50"></i>{{ $upcoming->hall }}</span>
                        @endif
                    </div>

                    <div class="mt-6 grid max-w-lg grid-cols-3 gap-3">
                        <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2.5 backdrop-blur">
                            <p class="text-lg font-bold">{{ number_format($upcoming->guests_count) }}</p>
                            <p class="text-[10px] uppercase tracking-wider text-white/55">Guests</p>
                        </div>
                        <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2.5 backdrop-blur">
                            <p class="text-lg font-bold">{{ number_format($upcoming->invitations_sent_count) }}</p>
                            <p class="text-[10px] uppercase tracking-wider text-white/55">Invited</p>
                        </div>
                        <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2.5 backdrop-blur">
                            <p class="text-lg font-bold">{{ number_format($upcoming->rsvp_attending_count) }}</p>
                            <p class="text-[10px] uppercase tracking-wider text-white/55">Attending</p>
                        </div>
                    </div>

                    <div class="mt-5 max-w-lg">
                        <div class="mb-1.5 flex items-center justify-between text-[11px] text-white/60">
                            <span>Invitation delivery</span>
                            <span class="font-semibold text-white">{{ $upcomingDelivery }}%</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-white/15">
                            <div class="h-full rounded-full bg-gold-300" style="width: {{ $upcomingDelivery }}%;"></div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('events.show', $upcoming) }}" class="btn bg-white px-5 text-surface-900 hover:bg-surface-100">
                            Manage event <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                        <a href="{{ route('events.guests.index', $upcoming) }}" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/20">
                            <i class="fas fa-users"></i> Guests
                        </a>
                        <a href="{{ route('events.scanner.index', $upcoming) }}" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/20">
                            <i class="fas fa-qrcode"></i> Scanner
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="card-surface flex h-full min-h-[320px] flex-col items-center justify-center p-10 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary-400">
                <i class="fas fa-calendar-check text-2xl"></i>
            </div>
            <h3 class="mt-4 font-display text-xl font-bold text-surface-800">No upcoming events</h3>
            <p class="mt-1 max-w-sm text-sm text-surface-400">Create an event to see its countdown, guests and invitation progress here.</p>
            <a href="{{ route('events.create') }}" class="btn btn-primary mt-5"><i class="fas fa-plus"></i> Create Event</a>
        </div>
        @endif
    </div>

    <div class="flex flex-col gap-5">
        <div class="card-surface p-5">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-surface-900">Quick actions</h3>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-surface-400">Shortcuts</span>
            </div>
            <div class="grid grid-cols-2 gap-2.5">
                <a href="{{ route('events.create') }}" class="group rounded-xl border border-surface-100 bg-surface-50/60 p-3 transition hover:border-primary-200 hover:bg-primary-50/50">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-100 text-primary-600 transition group-hover:bg-primary-500 group-hover:text-white">
                        <i class="fas fa-plus text-xs"></i>
                    </div>
                    <p class="mt-2.5 text-xs font-semibold text-surface-700">New event</p>
                </a>
                @if($upcoming)
                <a href="{{ route('events.guests.import', $upcoming) }}" class="group rounded-xl border border-surface-100 bg-surface-50/60 p-3 transition hover:border-green-200 hover:bg-green-50/50">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-100 text-green-600 transition group-hover:bg-green-500 group-hover:text-white">
                        <i class="fas fa-file-excel text-xs"></i>
                    </div>
                    <p class="mt-2.5 text-xs font-semibold text-surface-700">Import guests</p>
                </a>
                <a href="{{ route('events.invitations.index', $upcoming) }}" class="group rounded-xl border border-surface-100 bg-surface-50/60 p-3 transition hover:border-blue-200 hover:bg-blue-50/50">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600 transition group-hover:bg-blue-500 group-hover:text-white">
                        <i class="fas fa-paper-plane text-xs"></i>
                    </div>
                    <p class="mt-2.5 text-xs font-semibold text-surface-700">Invitations</p>
                </a>
                <a href="{{ route('events.scanner.index', $upcoming) }}" class="group rounded-xl border border-surface-100 bg-surface-50/60 p-3 transition hover:border-gold-200 hover:bg-gold-50/50">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gold-100 text-gold-600 transition group-hover:bg-gold-500 group-hover:text-white">
                        <i class="fas fa-qrcode text-xs"></i>
                    </div>
                    <p class="mt-2.5 text-xs font-semibold text-surface-700">Open scanner</p>
                </a>
                <a href="{{ route('events.report', $upcoming) }}" class="group rounded-xl border border-surface-100 bg-surface-50/60 p-3 transition hover:border-purple-200 hover:bg-purple-50/50">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 text-purple-600 transition group-hover:bg-purple-500 group-hover:text-white">
                        <i class="fas fa-chart-pie text-xs"></i>
                    </div>
                    <p class="mt-2.5 text-xs font-semibold text-surface-700">Reports</p>
                </a>
                @else
                <a href="{{ route('events.index') }}" class="group rounded-xl border border-surface-100 bg-surface-50/60 p-3 transition hover:border-blue-200 hover:bg-blue-50/50">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600 transition group-hover:bg-blue-500 group-hover:text-white">
                        <i class="fas fa-calendar-days text-xs"></i>
                    </div>
                    <p class="mt-2.5 text-xs font-semibold text-surface-700">All events</p>
                </a>
                @endif
            </div>
        </div>

        <div class="card-surface flex-1 p-5">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-surface-900">Recent activity</h3>
                <i class="fas fa-clock-rotate-left text-xs text-surface-300"></i>
            </div>
            @if($activityLogs->isEmpty())
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-surface-100 text-surface-400">
                    <i class="fas fa-wave-square"></i>
                </div>
                <p class="mt-3 text-sm font-medium text-surface-600">No activity yet</p>
                <p class="mt-0.5 text-xs text-surface-400">Actions on your events will show here.</p>
            </div>
            @else
            <div class="space-y-4">
                @foreach($activityLogs as $log)
                @php
                    [$activityIcon, $activityTone] = match ($log->action) {
                        'guest_added', 'guests_imported' => ['fa-user-plus', 'bg-blue-50 text-blue-600'],
                        'invitation_sent', 'bulk_invitations_sent', 'whatsapp_message_sent' => ['fa-paper-plane', 'bg-green-50 text-green-600'],
                        'payment_recorded', 'payment_deleted' => ['fa-coins', 'bg-gold-50 text-gold-600'],
                        'guest_checked_in' => ['fa-qrcode', 'bg-primary-50 text-primary-600'],
                        'card_image_uploaded' => ['fa-image', 'bg-purple-50 text-purple-600'],
                        default => ['fa-circle-info', 'bg-surface-100 text-surface-500'],
                    };
                @endphp
                <div class="flex gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $activityTone }}">
                        <i class="fas {{ $activityIcon }} text-[11px]"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="line-clamp-2 text-xs leading-relaxed text-surface-600">{{ $log->description }}</p>
                        <p class="mt-1 flex items-center gap-1.5 text-[10px] text-surface-400">
                            <span class="truncate">{{ $log->event?->name }}</span>
                            <span>·</span>
                            <span class="shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Insights -->
<div class="mb-8 grid grid-cols-1 gap-5 lg:grid-cols-3">
    <div class="card-surface p-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600"><i class="fas fa-paper-plane text-xs"></i></div>
                <h3 class="text-sm font-semibold text-surface-900">Invitation delivery</h3>
            </div>
            <span class="text-2xl font-bold text-surface-900">{{ $deliveryRate }}%</span>
        </div>
        <div class="mt-5 h-2.5 overflow-hidden rounded-full bg-surface-100">
            <div class="h-full rounded-full bg-gradient-to-r from-green-500 to-emerald-400" style="width: {{ $deliveryRate }}%;"></div>
        </div>
        <div class="mt-4 grid grid-cols-3 gap-3 text-center">
            <div class="rounded-lg bg-surface-50 py-2.5">
                <p class="text-base font-bold text-surface-900">{{ number_format($totalInvitationsSent) }}</p>
                <p class="text-[10px] uppercase tracking-wider text-surface-400">Sent</p>
            </div>
            <div class="rounded-lg bg-surface-50 py-2.5">
                <p class="text-base font-bold text-amber-600">{{ number_format($pendingInvitations) }}</p>
                <p class="text-[10px] uppercase tracking-wider text-surface-400">Pending</p>
            </div>
            <div class="rounded-lg bg-surface-50 py-2.5">
                <p class="text-base font-bold text-surface-900">{{ number_format($totalInvitations) }}</p>
                <p class="text-[10px] uppercase tracking-wider text-surface-400">Total</p>
            </div>
        </div>
    </div>

    <div class="card-surface p-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600"><i class="fas fa-envelope-circle-check text-xs"></i></div>
                <h3 class="text-sm font-semibold text-surface-900">RSVP breakdown</h3>
            </div>
            <span class="badge bg-blue-50 text-blue-700">{{ $rsvpResponseRate }}% responded</span>
        </div>
        <div class="mt-5 flex items-center gap-6">
            <div class="relative h-28 w-28 shrink-0 rounded-full"
                style="background: conic-gradient(#22c55e 0 {{ $attendPct }}%, #f87171 {{ $attendPct }}% {{ $attendPct + $notAttendPct }}%, #facc15 {{ $attendPct + $notAttendPct }}% 100%);">
                <div class="absolute inset-[10px] flex flex-col items-center justify-center rounded-full bg-white">
                    <span class="font-display text-2xl font-bold text-surface-900">{{ number_format($rsvpAttending) }}</span>
                    <span class="text-[9px] font-semibold uppercase tracking-wider text-surface-400">Attending</span>
                </div>
            </div>
            <div class="min-w-0 flex-1 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="flex items-center gap-2 text-xs text-surface-500"><span class="h-2 w-2 rounded-full bg-green-500"></span>Nitafika</span>
                    <span class="text-sm font-bold text-green-600">{{ number_format($rsvpAttending) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="flex items-center gap-2 text-xs text-surface-500"><span class="h-2 w-2 rounded-full bg-red-400"></span>Sitafika</span>
                    <span class="text-sm font-bold text-red-500">{{ number_format($rsvpNotAttending) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="flex items-center gap-2 text-xs text-surface-500"><span class="h-2 w-2 rounded-full bg-yellow-400"></span>Pending</span>
                    <span class="text-sm font-bold text-yellow-600">{{ number_format($rsvpPending) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card-surface relative overflow-hidden p-5">
        <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-gold-100/70"></div>
        <div class="relative">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gold-50 text-gold-600"><i class="fas fa-coins text-xs"></i></div>
                    <h3 class="text-sm font-semibold text-surface-900">Contributions</h3>
                </div>
                <span class="text-2xl font-bold text-gold-700">{{ $collectionRate }}%</span>
            </div>
            <div class="mt-5">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-surface-400">Total pledged</p>
                <p class="mt-0.5 font-display text-2xl font-bold text-surface-900">TZS {{ $money($totalPledged) }}</p>
            </div>
            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-surface-100">
                <div class="h-full rounded-full bg-gradient-to-r from-gold-500 to-amber-400" style="width: {{ $collectionRate }}%;"></div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-lg bg-green-50 px-3 py-2.5">
                    <p class="text-[10px] uppercase tracking-wider text-green-600">Collected</p>
                    <p class="mt-0.5 text-sm font-bold text-green-700">TZS {{ $money($totalPaid) }}</p>
                </div>
                <div class="rounded-lg bg-red-50 px-3 py-2.5">
                    <p class="text-[10px] uppercase tracking-wider text-red-500">Outstanding</p>
                    <p class="mt-0.5 text-sm font-bold text-red-600">TZS {{ $money($outstanding) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Events -->
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="font-display text-xl font-bold text-surface-900">Your events</h2>
        <p class="mt-0.5 text-xs text-surface-400">A snapshot of your most recent events</p>
    </div>
    @if($events->count() > 6)
    <a href="{{ route('events.index') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">
        View all ({{ $events->count() }}) <i class="fas fa-arrow-right ml-1 text-xs"></i>
    </a>
    @endif
</div>

@if($events->isEmpty())
<div class="card-surface px-6 py-16 text-center">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-surface-100 text-surface-400">
        <i class="fas fa-calendar-plus text-xl"></i>
    </div>
    <h3 class="mt-4 font-display text-lg font-bold text-surface-800">No events yet</h3>
    <p class="mt-1 text-sm text-surface-400">Create your first event to start inviting guests and tracking attendance.</p>
    <a href="{{ route('events.create') }}" class="btn btn-primary mt-5"><i class="fas fa-plus"></i> Create Event</a>
</div>
@else
<div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
    @foreach($recentEvents as $event)
    @php
        $accent = $event->card_accent_color ?: '#e11d48';
        $cover = $event->cover_image ? asset('storage/'.$event->cover_image) : null;
        $isUpcoming = $event->event_date->isFuture();
        $eventDelivery = $event->invitations_count > 0 ? (int) round(($event->invitations_sent_count / $event->invitations_count) * 100) : 0;
        $eventCheckin = $event->guests_count > 0 ? (int) round(($event->checked_in_count / $event->guests_count) * 100) : 0;
        $eventPaid = (float) $event->total_paid;
        $eventPledged = (float) $event->total_pledged;
        $eventCollection = $eventPledged > 0 ? (int) min(100, round(($eventPaid / $eventPledged) * 100)) : 0;
    @endphp
    <a href="{{ route('events.show', $event) }}" class="group overflow-hidden rounded-2xl border border-surface-200 bg-white shadow-soft transition hover:-translate-y-1 hover:shadow-soft-lg">
        <div class="relative h-32 overflow-hidden"
            style="background: {{ $cover ? "linear-gradient(180deg, rgba(18,18,20,.15), rgba(18,18,20,.78)), url('{$cover}') center/cover" : "linear-gradient(135deg, {$accent}, #18181b)" }};">
            <div class="absolute inset-0 opacity-15" style="background-image: radial-gradient(circle at 20% 20%, #fff 1px, transparent 1px); background-size: 16px 16px;"></div>
            <div class="absolute left-4 top-4 flex items-center gap-2">
                <span class="rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-surface-700 backdrop-blur">{{ ucfirst($event->status) }}</span>
                @if($isUpcoming)
                <span class="rounded-full border border-white/20 bg-black/25 px-2.5 py-1 text-[10px] font-semibold text-white backdrop-blur">
                    <i class="fas fa-clock mr-1"></i>{{ $event->event_date->diffForHumans(null, true) }} left
                </span>
                @endif
            </div>
            @if(auth()->user()->isAdmin() && $event->user)
            <span class="absolute right-4 top-4 rounded-full border border-white/20 bg-black/25 px-2.5 py-1 text-[10px] font-medium text-white/85 backdrop-blur">
                <i class="fas fa-user mr-1"></i>{{ $event->user->name }}
            </span>
            @endif
            <div class="absolute bottom-4 left-4 right-4">
                <h3 class="truncate font-display text-lg font-bold text-white">{{ $event->name }}</h3>
                <p class="mt-0.5 truncate text-xs text-white/70"><i class="fas fa-location-dot mr-1"></i>{{ $event->location }}</p>
            </div>
        </div>

        <div class="p-5">
            <div class="flex items-center justify-between text-xs text-surface-500">
                <span><i class="fas fa-calendar mr-1.5 text-surface-400"></i>{{ $event->event_date->format('d M Y') }}</span>
                <span class="font-semibold text-surface-700">{{ number_format($event->guests_count) }} guests</span>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-lg bg-surface-50 py-2">
                    <p class="text-sm font-bold text-surface-900">{{ number_format($event->invitations_sent_count) }}</p>
                    <p class="text-[9px] uppercase tracking-wider text-surface-400">Sent</p>
                </div>
                <div class="rounded-lg bg-surface-50 py-2">
                    <p class="text-sm font-bold text-green-600">{{ number_format($event->rsvp_attending_count) }}</p>
                    <p class="text-[9px] uppercase tracking-wider text-surface-400">Attending</p>
                </div>
                <div class="rounded-lg bg-surface-50 py-2">
                    <p class="text-sm font-bold text-gold-600">{{ number_format($event->checked_in_count) }}</p>
                    <p class="text-[9px] uppercase tracking-wider text-surface-400">Checked in</p>
                </div>
            </div>

            <div class="mt-4 space-y-2.5">
                <div>
                    <div class="mb-1 flex items-center justify-between text-[10px] text-surface-400">
                        <span>Invitation delivery</span><span class="font-semibold text-surface-600">{{ $eventDelivery }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-surface-100"><div class="h-full rounded-full bg-green-500" style="width: {{ $eventDelivery }}%;"></div></div>
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between text-[10px] text-surface-400">
                        <span>Contributions</span><span class="font-semibold text-surface-600">{{ $eventCollection }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-surface-100"><div class="h-full rounded-full bg-gold-500" style="width: {{ $eventCollection }}%;"></div></div>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-surface-100 pt-3">
                <span class="text-[11px] text-surface-400"><i class="fas fa-qrcode mr-1"></i>{{ $eventCheckin }}% checked in</span>
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600">
                    Manage <i class="fas fa-arrow-right text-[10px] transition-transform group-hover:translate-x-0.5"></i>
                </span>
            </div>
        </div>
    </a>
    @endforeach
</div>
@endif
@endsection
