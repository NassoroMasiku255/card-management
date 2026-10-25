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
<div class="mb-8 grid grid-cols-1 items-start gap-5 xl:grid-cols-3">
    <div class="self-start xl:col-span-2">
        @if($upcoming)
        @php
            $cover = $upcoming->cover_image ? asset('storage/'.$upcoming->cover_image) : null;
            $heroBg = $cover
                ? "linear-gradient(115deg, rgba(18,18,20,.94) 10%, rgba(18,18,20,.62) 58%, rgba(18,18,20,.35)), url('{$cover}')"
                : "linear-gradient(125deg, {$upcomingAccent} 0%, #881337 58%, #18181b 100%)";
        @endphp
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-soft-lg sm:p-8"
            style="background: {{ $heroBg }}; background-size: cover; background-position: center;">
            <div class="absolute -right-12 -top-16 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-20 right-1/3 h-44 w-44 rounded-full bg-gold-400/20 blur-3xl"></div>

            <div class="relative">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-white/85 backdrop-blur">
                        <i class="fas fa-star text-gold-300"></i> Next up
                    </span>
                    <div class="flex items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 py-2 backdrop-blur">
                        <span class="font-display text-2xl font-bold leading-none">{{ $upcomingDays }}</span>
                        <span class="text-[10px] font-medium uppercase leading-tight tracking-wider text-white/65">{{ $upcomingDays === 1 ? 'day' : 'days' }}<br>to go</span>
                    </div>
                </div>

                <div class="mt-7">
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

    <div class="order-3 flex flex-col gap-5 xl:order-none xl:col-start-3 xl:row-span-2 xl:row-start-1 xl:self-start">
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

<!-- Insights -->
<div class="order-2 grid grid-cols-1 gap-5 lg:grid-cols-5 xl:order-none xl:col-span-2 xl:col-start-1 xl:row-start-2">
    <!-- Guest response -->
    <div class="card-surface flex h-full flex-col overflow-hidden lg:col-span-3">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-soft">
                    <i class="fas fa-chart-simple text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-surface-900">Guest response</h3>
                    <p class="text-[11px] text-surface-400">RSVP, invitations and attendance</p>
                </div>
            </div>
            <span class="badge bg-blue-50 text-blue-700">{{ $rsvpResponseRate }}% responded</span>
        </div>

        <div class="flex-1 p-5">
            <div class="flex flex-col items-center gap-6 sm:flex-row">
                <div class="relative h-32 w-32 shrink-0 rounded-full"
                    style="background: conic-gradient(#22c55e 0 {{ $attendPct }}%, #f87171 {{ $attendPct }}% {{ $attendPct + $notAttendPct }}%, #facc15 {{ $attendPct + $notAttendPct }}% 100%);">
                    <div class="absolute inset-[11px] flex flex-col items-center justify-center rounded-full bg-white shadow-inner">
                        <span class="font-display text-3xl font-bold text-surface-900">{{ number_format($rsvpAttending) }}</span>
                        <span class="text-[9px] font-semibold uppercase tracking-[0.12em] text-surface-400">attending</span>
                    </div>
                </div>

                <div class="min-w-0 flex-1 space-y-3.5">
                    <div>
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="flex items-center gap-2 text-surface-600"><span class="h-2 w-2 rounded-full bg-green-500"></span>Nitafika</span>
                            <span class="text-surface-400"><strong class="text-sm text-green-600">{{ number_format($rsvpAttending) }}</strong> · {{ $attendPct }}%</span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-surface-100">
                            <div class="h-full rounded-full bg-green-500" style="width: {{ $attendPct }}%;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="flex items-center gap-2 text-surface-600"><span class="h-2 w-2 rounded-full bg-red-400"></span>Sitafika</span>
                            <span class="text-surface-400"><strong class="text-sm text-red-500">{{ number_format($rsvpNotAttending) }}</strong> · {{ $notAttendPct }}%</span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-surface-100">
                            <div class="h-full rounded-full bg-red-400" style="width: {{ $notAttendPct }}%;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="flex items-center gap-2 text-surface-600"><span class="h-2 w-2 rounded-full bg-yellow-400"></span>Pending</span>
                            <span class="text-surface-400"><strong class="text-sm text-yellow-600">{{ number_format($rsvpPending) }}</strong> · {{ $pendingPct }}%</span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-surface-100">
                            <div class="h-full rounded-full bg-yellow-400" style="width: {{ $pendingPct }}%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-surface-100 bg-surface-50/70 p-4">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-xs font-semibold text-surface-700">
                        <i class="fas fa-paper-plane text-green-500"></i> Invitation delivery
                    </span>
                    <span class="text-xs font-bold text-surface-900">{{ number_format($totalInvitationsSent) }} / {{ number_format($totalInvitations) }}</span>
                </div>
                <div class="mt-2.5 flex h-2 overflow-hidden rounded-full bg-surface-200">
                    <div class="h-full bg-gradient-to-r from-green-500 to-emerald-400" style="width: {{ $deliveryRate }}%;"></div>
                    <div class="h-full bg-amber-300" style="width: {{ 100 - $deliveryRate }}%;"></div>
                </div>
                <div class="mt-2 flex items-center justify-between text-[10px] text-surface-400">
                    <span class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>{{ number_format($totalInvitationsSent) }} delivered</span>
                    <span class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-amber-300"></span>{{ number_format($pendingInvitations) }} pending</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 divide-x divide-surface-100 border-t border-surface-100 bg-surface-50/50 text-center">
            <div class="px-3 py-3">
                <p class="text-sm font-bold text-surface-900">{{ number_format($totalGuests) }}</p>
                <p class="mt-0.5 text-[9px] font-semibold uppercase tracking-wider text-surface-400">Guests</p>
            </div>
            <div class="px-3 py-3">
                <p class="text-sm font-bold text-primary-600">{{ number_format($checkedIn) }}</p>
                <p class="mt-0.5 text-[9px] font-semibold uppercase tracking-wider text-surface-400">Checked in</p>
            </div>
            <div class="px-3 py-3">
                <p class="text-sm font-bold text-gold-600">{{ number_format($estimatedAttendance) }}</p>
                <p class="mt-0.5 text-[9px] font-semibold uppercase tracking-wider text-surface-400">Expected seats</p>
            </div>
        </div>
    </div>

    <!-- Contributions -->
    <div class="relative flex h-full flex-col overflow-hidden rounded-2xl bg-gradient-to-br from-[#1c1917] via-[#27272a] to-[#3f2d10] p-5 text-white shadow-soft-lg lg:col-span-2">
        <div class="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-gold-400/20 blur-2xl"></div>
        <div class="absolute -bottom-16 -left-10 h-40 w-40 rounded-full bg-primary-500/15 blur-3xl"></div>

        <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-gold-300/20 bg-gold-400/15 text-gold-300">
                    <i class="fas fa-coins"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold">Contributions</h3>
                    <p class="text-[11px] text-white/40">Pledges and collections</p>
                </div>
            </div>
            <span class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-semibold text-gold-200">{{ $collectionRate }}% collected</span>
        </div>

        <div class="relative mt-6">
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-white/40">Total pledged</p>
            <p class="mt-1 font-display text-3xl font-bold tracking-tight">TZS {{ $money($totalPledged) }}</p>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10">
                <div class="h-full rounded-full bg-gradient-to-r from-gold-500 to-amber-300" style="width: {{ $collectionRate }}%;"></div>
            </div>
        </div>

        <div class="relative mt-5 grid grid-cols-2 gap-2.5">
            <div class="rounded-xl border border-green-400/15 bg-green-400/10 px-3 py-3">
                <p class="text-[9px] font-semibold uppercase tracking-wider text-green-300/80">Collected</p>
                <p class="mt-1 text-sm font-bold text-green-300">TZS {{ $money($totalPaid) }}</p>
            </div>
            <div class="rounded-xl border border-rose-400/15 bg-rose-400/10 px-3 py-3">
                <p class="text-[9px] font-semibold uppercase tracking-wider text-rose-300/80">Outstanding</p>
                <p class="mt-1 text-sm font-bold text-rose-300">TZS {{ $money($outstanding) }}</p>
            </div>
        </div>

        <div class="relative mt-auto pt-5">
            <div class="mb-2 flex items-center justify-between text-[10px] text-white/35">
                <span>Collection progress</span>
                <span>{{ $collectionRate }}%</span>
            </div>
            <a href="{{ route('events.index') }}" class="flex items-center justify-between rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-xs font-semibold text-white/80 transition hover:bg-white/10">
                View event reports <i class="fas fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>
</div>
</div>

<!-- Command center -->
<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="font-display text-xl font-bold text-surface-900">Command center</h2>
        <p class="mt-0.5 text-xs text-surface-400">Everything that needs a nudge, in one place</p>
    </div>
    <a href="{{ route('events.index') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">
        All events <i class="fas fa-arrow-right ml-1 text-xs"></i>
    </a>
</div>

@php
    $attentionItems = [
        [
            'label' => 'Invitations to send',
            'value' => number_format($pendingInvitations),
            'caption' => $deliveryRate.'% already delivered',
            'cta' => $pendingInvitations > 0 ? 'Send now' : 'View invitations',
            'icon' => 'fa-paper-plane',
            'tone' => 'green',
            'href' => $upcoming ? route('events.invitations.index', $upcoming) : route('events.index'),
        ],
        [
            'label' => 'RSVPs awaiting reply',
            'value' => number_format($rsvpPending),
            'caption' => $rsvpResponseRate.'% response rate',
            'cta' => 'Follow up',
            'icon' => 'fa-envelope-open-text',
            'tone' => 'blue',
            'href' => $upcoming ? route('events.invitations.index', $upcoming) : route('events.index'),
        ],
        [
            'label' => 'Outstanding collections',
            'value' => 'TZS '.$money($outstanding),
            'caption' => $collectionRate.'% collected so far',
            'cta' => 'View reports',
            'icon' => 'fa-coins',
            'tone' => 'gold',
            'href' => $upcoming ? route('events.report', $upcoming) : route('events.index'),
        ],
        [
            'label' => 'Check-in progress',
            'value' => number_format($checkedIn),
            'caption' => $checkinRate.'% of all guests',
            'cta' => 'Open scanner',
            'icon' => 'fa-qrcode',
            'tone' => 'rose',
            'href' => $upcoming ? route('events.scanner.index', $upcoming) : route('events.index'),
        ],
    ];

    $toneClasses = [
        'green' => ['icon' => 'bg-green-100 text-green-600', 'hover' => 'hover:border-green-200', 'text' => 'text-green-600'],
        'blue' => ['icon' => 'bg-blue-100 text-blue-600', 'hover' => 'hover:border-blue-200', 'text' => 'text-blue-600'],
        'gold' => ['icon' => 'bg-gold-100 text-gold-600', 'hover' => 'hover:border-gold-200', 'text' => 'text-gold-600'],
        'rose' => ['icon' => 'bg-primary-100 text-primary-600', 'hover' => 'hover:border-primary-200', 'text' => 'text-primary-600'],
    ];
@endphp

<div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
    <!-- Action items -->
    <div class="card-surface flex flex-col overflow-hidden xl:col-span-2">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-rose-600 text-white shadow-soft">
                    <i class="fas fa-bolt text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-surface-900">Needs attention</h3>
                    <p class="text-[11px] text-surface-400">Your priorities across all events</p>
                </div>
            </div>
            <span class="badge bg-green-50 text-green-700"><span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-green-500"></span>Live overview</span>
        </div>

        <div class="grid flex-1 gap-3 p-5 sm:grid-cols-2">
            @foreach($attentionItems as $item)
            @php $tone = $toneClasses[$item['tone']]; @endphp
            <a href="{{ $item['href'] }}" class="group flex flex-col rounded-xl border border-surface-100 bg-surface-50/60 p-4 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-soft {{ $tone['hover'] }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $tone['icon'] }}">
                        <i class="fas {{ $item['icon'] }} text-xs"></i>
                    </div>
                    <i class="fas fa-arrow-up-right-from-square text-[10px] text-surface-300 transition group-hover:text-surface-500"></i>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight text-surface-900">{{ $item['value'] }}</p>
                <p class="mt-0.5 text-xs font-medium text-surface-600">{{ $item['label'] }}</p>
                <div class="mt-2.5 flex items-center justify-between gap-2 text-[10px]">
                    <span class="text-surface-400">{{ $item['caption'] }}</span>
                    <span class="inline-flex shrink-0 items-center gap-1 font-semibold {{ $tone['text'] }}">{{ $item['cta'] }} <i class="fas fa-arrow-right text-[8px]"></i></span>
                </div>
            </a>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-surface-100 bg-surface-50/50 px-5 py-3">
            @if($teamStats)
            <p class="text-[11px] text-surface-400">
                Managing <span class="font-semibold text-surface-600">{{ number_format($totalEvents) }}</span> events across
                <span class="font-semibold text-surface-600">{{ number_format($teamStats['owners']) }}</span> event owners ·
                <span class="font-semibold text-surface-600">{{ number_format($teamStats['total']) }}</span> team members ·
                <span class="font-semibold text-surface-600">{{ number_format($teamStats['whatsapp']) }}</span> WhatsApp-enabled
            </p>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700">
                Manage team <i class="fas fa-arrow-right text-[10px]"></i>
            </a>
            @else
            <p class="text-[11px] text-surface-400">
                <span class="font-semibold text-surface-600">{{ number_format($totalGuests) }}</span> guests ·
                <span class="font-semibold text-surface-600">{{ number_format($estimatedAttendance) }}</span> expected seats ·
                <span class="font-semibold text-surface-600">{{ number_format($activeEvents) }}</span> active events
            </p>
            <a href="{{ route('events.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700">
                Browse events <i class="fas fa-arrow-right text-[10px]"></i>
            </a>
            @endif
        </div>
    </div>

    <!-- Top contributors -->
    <div class="card-surface flex flex-col overflow-hidden">
        <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-gold-400 to-amber-600 text-white shadow-soft">
                    <i class="fas fa-crown text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-surface-900">Top contributors</h3>
                    <p class="text-[11px] text-surface-400">Most generous guests</p>
                </div>
            </div>
            <span class="rounded-full bg-gold-50 px-2.5 py-1 text-[10px] font-bold text-gold-700">TZS {{ $money($totalPaid) }}</span>
        </div>

        <div class="flex-1 p-5">
            @if($topContributors->isEmpty())
            <div class="flex h-full min-h-[220px] flex-col items-center justify-center text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-100 text-surface-400">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <p class="mt-3 text-sm font-medium text-surface-600">No contributions yet</p>
                <p class="mt-0.5 max-w-[220px] text-xs text-surface-400">Record guest pledges and payments to see your top supporters here.</p>
            </div>
            @else
            <div class="space-y-3.5">
                @foreach($topContributors as $guest)
                @php
                    $initials = collect(preg_split('/\s+/', trim($guest->full_name)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                        ->implode('');
                    $avatarTones = ['bg-primary-100 text-primary-700', 'bg-blue-100 text-blue-700', 'bg-green-100 text-green-700', 'bg-gold-100 text-gold-700', 'bg-purple-100 text-purple-700'];
                    $avatarTone = $avatarTones[$loop->index % count($avatarTones)];
                    $rate = $guest->collection_rate;
                    $barTone = $rate >= 100 ? 'bg-green-500' : ($rate >= 50 ? 'bg-gold-500' : 'bg-primary-500');
                @endphp
                <div class="flex items-center gap-3">
                    <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[10px] font-bold {{ $avatarTone }}">
                        {{ $initials }}
                        @if($loop->first)
                        <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-gold-400 text-[7px] text-white shadow"><i class="fas fa-crown"></i></span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-xs font-semibold text-surface-800">{{ $guest->full_name }}</p>
                            <p class="shrink-0 text-xs font-bold text-surface-900">TZS {{ $money($guest->paid_amount) }}</p>
                        </div>
                        <div class="mt-0.5 flex items-center justify-between gap-2 text-[10px] text-surface-400">
                            <span class="truncate">{{ $guest->event?->name }}</span>
                            <span class="shrink-0">{{ $rate }}%</span>
                        </div>
                        <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-surface-100">
                            <div class="h-full rounded-full {{ $barTone }}" style="width: {{ $rate }}%;"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="border-t border-surface-100 px-5 py-3">
            <a href="{{ $upcoming ? route('events.report', $upcoming) : route('events.index') }}" class="flex items-center justify-between text-xs font-semibold text-primary-600 hover:text-primary-700">
                View contribution reports <i class="fas fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    </div>
</div>
@endsection
