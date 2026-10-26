@extends('layouts.app')

@section('title', $event->name)

@section('content')
@php
    $accent = $event->card_accent_color ?: '#e11d48';
    $cover = $event->cover_image ? asset('storage/'.$event->cover_image) : null;
    $heroBg = $cover
        ? "linear-gradient(115deg, rgba(18,18,20,.94) 8%, rgba(18,18,20,.66) 52%, rgba(18,18,20,.38)), url('{$cover}')"
        : "linear-gradient(125deg, {$accent} 0%, #881337 58%, #18181b 100%)";

    $totalInvitations = max($event->invitations->count(), 1);
    $checkinRate = $stats['total_guests'] > 0 ? (int) round(($stats['checked_in'] / $stats['total_guests']) * 100) : 0;
    $deliveryRate = (int) round(($stats['invitations_sent'] / $totalInvitations) * 100);
    $rsvpTotal = max($stats['rsvp_attending'] + $stats['rsvp_not_attending'] + $stats['rsvp_pending'], 1);
    $attendPct = round(($stats['rsvp_attending'] / $rsvpTotal) * 100);
    $notAttendPct = round(($stats['rsvp_not_attending'] / $rsvpTotal) * 100);
    $pendingPct = max(100 - $attendPct - $notAttendPct, 0);
    $confirmedAttendance = $event->guests
        ->filter(fn ($guest) => $guest->invitation?->rsvp_status === 'attending')
        ->sum(fn ($guest) => $guest->estimated_attendees);
    $daysToEvent = $event->event_date->isFuture()
        ? (int) now()->startOfDay()->diffInDays($event->event_date->startOfDay(), false)
        : null;

    $money = function ($amount) {
        $amount = (float) $amount;
        if ($amount >= 1000000) return number_format($amount / 1000000, 1).'M';
        if ($amount >= 1000) return number_format($amount / 1000, 0).'K';
        return number_format($amount);
    };

    $statusTone = match ($event->status) {
        'active' => 'bg-green-100 text-green-700',
        'completed' => 'bg-blue-100 text-blue-700',
        'cancelled' => 'bg-red-100 text-red-700',
        default => 'bg-surface-100 text-surface-600',
    };
@endphp

<!-- Breadcrumb -->
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <a href="{{ route('events.index') }}" class="text-sm font-medium text-surface-500 transition hover:text-surface-800">
        <i class="fas fa-arrow-left mr-1.5"></i> Back to events
    </a>
    <div class="flex items-center gap-2 text-xs text-surface-400">
        <span>Events</span>
        <i class="fas fa-chevron-right text-[8px]"></i>
        <span class="max-w-[180px] truncate font-medium text-surface-600">{{ $event->name }}</span>
    </div>
</div>

<!-- Hero -->
<div class="relative mb-6 overflow-hidden rounded-2xl p-6 text-white shadow-soft-lg sm:p-8"
    style="background: {{ $heroBg }}; background-size: cover; background-position: center;">
    <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
    <div class="absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-gold-400/20 blur-3xl"></div>

    <div class="relative">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-white/85 backdrop-blur">
                    <i class="fas fa-heart text-gold-300"></i> Event overview
                </span>
                <span class="badge {{ $statusTone }}">{{ ucfirst($event->status) }}</span>
            </div>
            @if($daysToEvent !== null)
            <div class="flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-3.5 py-2 backdrop-blur">
                <span class="font-display text-xl font-bold leading-none">{{ $daysToEvent }}</span>
                <span class="text-[10px] font-medium uppercase leading-tight tracking-wider text-white/65">{{ $daysToEvent === 1 ? 'day' : 'days' }}<br>to go</span>
            </div>
            @else
            <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-white/70">
                {{ $event->event_date->isToday() ? 'Happening today' : 'Event day passed' }}
            </span>
            @endif
        </div>

        <div class="mt-8 max-w-3xl">
            <h1 class="font-display text-3xl font-bold leading-tight sm:text-4xl">{{ $event->name }}</h1>
            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/75">
                <span><i class="fas fa-calendar mr-1.5 text-white/50"></i>{{ $event->event_date->format('l, d M Y') }}</span>
                @if($event->event_time)
                <span><i class="fas fa-clock mr-1.5 text-white/50"></i>{{ $event->event_time }}</span>
                @endif
                <span><i class="fas fa-location-dot mr-1.5 text-white/50"></i>{{ $event->location }}</span>
                @if($event->hall)
                <span><i class="fas fa-building mr-1.5 text-white/50"></i>{{ $event->hall }}</span>
                @endif
            </div>
            @if($event->description)
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/60">{{ $event->description }}</p>
            @endif
        </div>

        <div class="mt-7 flex flex-wrap items-center gap-2.5">
            <a href="{{ route('events.scanner.index', $event) }}" class="btn bg-white px-5 text-surface-900 hover:bg-surface-100">
                <i class="fas fa-qrcode"></i> Open scanner
            </a>
            <a href="{{ route('events.guests.index', $event) }}" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/20">
                <i class="fas fa-users"></i> Guests
            </a>
            <a href="{{ route('events.invitations.index', $event) }}" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/20">
                <i class="fas fa-paper-plane"></i> Invitations
            </a>
            <a href="{{ route('events.report', $event) }}" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/20">
                <i class="fas fa-chart-pie"></i> Report
            </a>
            <a href="{{ route('events.messages.report', $event) }}" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/20">
                <i class="fas fa-tower-broadcast"></i> Messages
            </a>
            <a href="{{ route('events.budget.index', $event) }}" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/20">
                <i class="fas fa-calculator"></i> Budget
            </a>
            <a href="{{ route('events.countdown-card', $event) }}" target="_blank" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/20">
                <i class="fab fa-whatsapp text-green-300"></i> Countdown
            </a>
            <a href="{{ route('events.edit', $event) }}" class="btn border border-white/25 bg-white/10 text-white hover:bg-white/20">
                <i class="fas fa-pen"></i> Edit
            </a>
        </div>
    </div>
</div>

<!-- KPI cards -->
<div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
    <div class="card-surface p-4">
        <div class="flex items-center justify-between">
            <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Guests</p>
            <i class="fas fa-users text-xs text-primary-400"></i>
        </div>
        <p class="mt-2 text-2xl font-bold text-surface-900">{{ number_format($stats['total_guests']) }}</p>
        <p class="mt-0.5 text-[10px] text-surface-400">{{ $stats['singles'] }} single · {{ $stats['doubles'] }} double</p>
    </div>

    <div class="card-surface p-4">
        <div class="flex items-center justify-between">
            <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Invitations</p>
            <i class="fas fa-paper-plane text-xs text-green-400"></i>
        </div>
        <p class="mt-2 text-2xl font-bold text-surface-900">{{ number_format($stats['invitations_sent']) }}</p>
        <p class="mt-0.5 text-[10px] text-surface-400">{{ $deliveryRate }}% delivered · {{ $stats['invitations_pending'] }} pending</p>
    </div>

    <div class="card-surface p-4">
        <div class="flex items-center justify-between">
            <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Attending</p>
            <i class="fas fa-envelope-circle-check text-xs text-green-400"></i>
        </div>
        <p class="mt-2 text-2xl font-bold text-green-600">{{ number_format($stats['rsvp_attending']) }}</p>
        <p class="mt-0.5 text-[10px] text-surface-400">{{ number_format($confirmedAttendance) }} seats confirmed</p>
    </div>

    <div class="card-surface p-4">
        <div class="flex items-center justify-between">
            <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Checked in</p>
            <i class="fas fa-qrcode text-xs text-gold-400"></i>
        </div>
        <p class="mt-2 text-2xl font-bold text-surface-900">{{ number_format($stats['checked_in']) }}</p>
        <p class="mt-0.5 text-[10px] text-surface-400">{{ $checkinRate }}% of guests · {{ $stats['not_checked_in'] }} remaining</p>
    </div>

    <div class="card-surface p-4">
        <div class="flex items-center justify-between">
            <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Pledged</p>
            <i class="fas fa-coins text-xs text-gold-400"></i>
        </div>
        <p class="mt-2 text-2xl font-bold text-surface-900">{{ $money($stats['total_pledged']) }}</p>
        <p class="mt-0.5 text-[10px] text-surface-400">TZS total contribution</p>
    </div>

    <div class="card-surface p-4">
        <div class="flex items-center justify-between">
            <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Collected</p>
            <i class="fas fa-wallet text-xs text-green-400"></i>
        </div>
        <p class="mt-2 text-2xl font-bold text-green-600">{{ $money($stats['total_paid']) }}</p>
        <p class="mt-0.5 text-[10px] text-surface-400">{{ $stats['collection_rate'] }}% of pledged</p>
    </div>
</div>

<!-- Quick access -->
<div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
    <a href="{{ route('events.guests.index', $event) }}" class="group rounded-xl border border-surface-200 bg-white p-4 shadow-soft transition hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-soft-lg">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600 transition group-hover:bg-primary-500 group-hover:text-white"><i class="fas fa-users text-xs"></i></div>
        <p class="mt-2.5 text-xs font-semibold text-surface-700">Guest list</p>
        <p class="mt-0.5 text-[10px] text-surface-400">Manage guests</p>
    </a>
    <a href="{{ route('events.invitations.index', $event) }}" class="group rounded-xl border border-surface-200 bg-white p-4 shadow-soft transition hover:-translate-y-0.5 hover:border-green-200 hover:shadow-soft-lg">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600 transition group-hover:bg-green-500 group-hover:text-white"><i class="fas fa-paper-plane text-xs"></i></div>
        <p class="mt-2.5 text-xs font-semibold text-surface-700">Invitations</p>
        <p class="mt-0.5 text-[10px] text-surface-400">Send &amp; track</p>
    </a>
    <a href="{{ route('events.scanner.index', $event) }}" class="group rounded-xl border border-surface-200 bg-white p-4 shadow-soft transition hover:-translate-y-0.5 hover:border-gold-200 hover:shadow-soft-lg">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gold-50 text-gold-600 transition group-hover:bg-gold-500 group-hover:text-white"><i class="fas fa-qrcode text-xs"></i></div>
        <p class="mt-2.5 text-xs font-semibold text-surface-700">Scanner</p>
        <p class="mt-0.5 text-[10px] text-surface-400">Check-in desk</p>
    </a>
    <a href="{{ route('events.report', $event) }}" class="group rounded-xl border border-surface-200 bg-white p-4 shadow-soft transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-soft-lg">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition group-hover:bg-blue-500 group-hover:text-white"><i class="fas fa-chart-pie text-xs"></i></div>
        <p class="mt-2.5 text-xs font-semibold text-surface-700">Reports</p>
        <p class="mt-0.5 text-[10px] text-surface-400">Full breakdown</p>
    </a>
    <a href="{{ route('events.messages.report', $event) }}" class="group rounded-xl border border-surface-200 bg-white p-4 shadow-soft transition hover:-translate-y-0.5 hover:border-green-200 hover:shadow-soft-lg">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600 transition group-hover:bg-green-500 group-hover:text-white"><i class="fas fa-tower-broadcast text-xs"></i></div>
        <p class="mt-2.5 text-xs font-semibold text-surface-700">Messages</p>
        <p class="mt-0.5 text-[10px] text-surface-400">Delivery &amp; replies</p>
    </a>
    <a href="{{ route('events.budget.index', $event) }}" class="group rounded-xl border border-surface-200 bg-white p-4 shadow-soft transition hover:-translate-y-0.5 hover:border-gold-200 hover:shadow-soft-lg">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gold-50 text-gold-600 transition group-hover:bg-gold-500 group-hover:text-white"><i class="fas fa-calculator text-xs"></i></div>
        <p class="mt-2.5 text-xs font-semibold text-surface-700">Budget</p>
        <p class="mt-0.5 text-[10px] text-surface-400">Services &amp; vendors</p>
    </a>
    <a href="{{ route('events.guests.import', $event) }}" class="group rounded-xl border border-surface-200 bg-white p-4 shadow-soft transition hover:-translate-y-0.5 hover:border-purple-200 hover:shadow-soft-lg">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-50 text-purple-600 transition group-hover:bg-purple-500 group-hover:text-white"><i class="fas fa-file-import text-xs"></i></div>
        <p class="mt-2.5 text-xs font-semibold text-surface-700">Import</p>
        <p class="mt-0.5 text-[10px] text-surface-400">Excel / CSV</p>
    </a>
    <a href="{{ route('events.edit', $event) }}" class="group rounded-xl border border-surface-200 bg-white p-4 shadow-soft transition hover:-translate-y-0.5 hover:border-surface-300 hover:shadow-soft-lg">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-surface-100 text-surface-600 transition group-hover:bg-surface-700 group-hover:text-white"><i class="fas fa-sliders text-xs"></i></div>
        <p class="mt-2.5 text-xs font-semibold text-surface-700">Settings</p>
        <p class="mt-0.5 text-[10px] text-surface-400">Edit event</p>
    </a>
</div>

<!-- Main content -->
<div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
    <!-- Left column -->
    <div class="space-y-6 xl:col-span-2">
        <!-- Recent guests -->
        <div class="card-surface overflow-hidden">
            <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Recent guests</h2>
                    <p class="mt-0.5 text-xs text-surface-400">Latest guests added to this event</p>
                </div>
                <a href="{{ route('events.guests.index', $event) }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700">View all <i class="fas fa-arrow-right ml-1 text-[9px]"></i></a>
            </div>

            @if($event->guests->isEmpty())
            <div class="px-6 py-14 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-100 text-surface-400">
                    <i class="fas fa-user-plus"></i>
                </div>
                <p class="mt-3 text-sm font-medium text-surface-600">No guests added yet</p>
                <p class="mt-1 text-xs text-surface-400">Add guests manually or import your list.</p>
                <div class="mt-4 flex justify-center gap-2">
                    <a href="{{ route('events.guests.create', $event) }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add guest</a>
                    <a href="{{ route('events.guests.import', $event) }}" class="btn btn-outline btn-sm"><i class="fas fa-file-import"></i> Import</a>
                </div>
            </div>
            @else
            <div class="responsive-table-wrap overflow-x-auto">
                <table class="responsive-table w-full">
                    <thead class="bg-surface-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-surface-400 sm:px-6">Guest</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-surface-400 sm:px-6">Phone</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-surface-400 sm:px-6">Card</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-surface-400 sm:px-6">RSVP</th>
                            <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-surface-400 sm:px-6">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100">
                        @foreach($event->guests->take(10) as $guest)
                        <tr class="transition hover:bg-surface-50/70">
                            <td class="px-5 py-3 sm:px-6" data-label="Guest">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-50 text-[10px] font-bold text-primary-600">
                                        {{ strtoupper(substr($guest->full_name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-surface-900">{{ $guest->full_name }}</div>
                                        <div class="text-[10px] text-surface-400">{{ $guest->unique_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-sm text-surface-600 sm:px-6" data-label="Phone">{{ $guest->phone_number }}</td>
                            <td class="px-5 py-3 sm:px-6" data-label="Card">
                                <span class="badge {{ $guest->card_type === 'double' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ ucfirst($guest->card_type) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 sm:px-6" data-label="RSVP">
                                @if($guest->invitation)
                                    <span class="badge
                                        {{ $guest->invitation->rsvp_status === 'attending' ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $guest->invitation->rsvp_status === 'not_attending' ? 'bg-red-100 text-red-700' : '' }}
                                        {{ $guest->invitation->rsvp_status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}">
                                        {{ $guest->invitation->rsvp_status === 'attending' ? 'Nitahudhuria' : ($guest->invitation->rsvp_status === 'not_attending' ? 'Sitahudhuria' : 'Pending') }}
                                    </span>
                                @else
                                    <span class="badge bg-surface-100 text-surface-500">No invite</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 sm:px-6" data-label="Status">
                                @if($guest->invitation)
                                    <span class="badge
                                        {{ $guest->invitation->send_status === 'sent' ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $guest->invitation->send_status === 'pending' ? 'bg-surface-100 text-surface-600' : '' }}
                                        {{ $guest->invitation->send_status === 'failed' ? 'bg-red-100 text-red-700' : '' }}">
                                        {{ ucfirst($guest->invitation->send_status) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <!-- Activity -->
        <div class="card-surface p-5 sm:p-6">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Event activity</h2>
                    <p class="mt-0.5 text-xs text-surface-400">Everything that has happened recently</p>
                </div>
                <i class="fas fa-clock-rotate-left text-sm text-surface-300"></i>
            </div>

            @if($activityLogs->isEmpty())
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-surface-100 text-surface-400"><i class="fas fa-wave-square"></i></div>
                <p class="mt-3 text-sm font-medium text-surface-600">No activity yet</p>
                <p class="mt-0.5 text-xs text-surface-400">Guest additions, invitations and check-ins will appear here.</p>
            </div>
            @else
            <div class="relative space-y-5 before:absolute before:bottom-2 before:left-[15px] before:top-2 before:w-px before:bg-surface-100">
                @foreach($activityLogs as $log)
                @php
                    [$activityIcon, $activityTone] = match ($log->action) {
                        'guest_added', 'guests_imported' => ['fa-user-plus', 'bg-blue-50 text-blue-600 ring-blue-100'],
                        'invitation_sent', 'bulk_invitations_sent', 'whatsapp_message_sent' => ['fa-paper-plane', 'bg-green-50 text-green-600 ring-green-100'],
                        'payment_recorded', 'payment_deleted' => ['fa-coins', 'bg-gold-50 text-gold-600 ring-gold-100'],
                        'budget_item_added', 'budget_item_updated', 'budget_item_removed', 'budget_guests_updated' => ['fa-calculator', 'bg-gold-50 text-gold-600 ring-gold-100'],
                        'guest_checked_in' => ['fa-qrcode', 'bg-primary-50 text-primary-600 ring-primary-100'],
                        'card_image_uploaded' => ['fa-image', 'bg-purple-50 text-purple-600 ring-purple-100'],
                        default => ['fa-circle-info', 'bg-surface-100 text-surface-500 ring-surface-100'],
                    };
                @endphp
                <div class="relative flex gap-3">
                    <div class="z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $activityTone }} ring-4">
                        <i class="fas {{ $activityIcon }} text-[10px]"></i>
                    </div>
                    <div class="min-w-0 flex-1 pt-0.5">
                        <p class="text-sm leading-relaxed text-surface-600">{{ $log->description }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-1.5 text-[10px] text-surface-400">
                            <span>{{ $log->created_at->format('d M Y, H:i') }}</span>
                            <span>·</span>
                            <span>{{ $log->created_at->diffForHumans() }}</span>
                            @if($log->user)
                            <span>·</span>
                            <span>{{ $log->user->name }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <!-- Right sidebar -->
    <div class="space-y-5">
        <!-- Payment thank-you status -->
        <div class="card-surface p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600"><i class="fab fa-whatsapp text-sm"></i></div>
                    <div>
                        <h3 class="text-sm font-semibold text-surface-900">Payment thank-you</h3>
                        <p class="text-[10px] text-surface-400">Automatic WhatsApp message</p>
                    </div>
                </div>
                <span class="badge {{ $event->payment_thank_you_enabled ? 'bg-green-100 text-green-700' : 'bg-surface-100 text-surface-500' }}">
                    {{ $event->payment_thank_you_enabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>
            <p class="mt-4 text-xs leading-relaxed text-surface-500">
                @if($event->payment_thank_you_enabled)
                    Guests receive a WhatsApp thank-you after each payment with the amount paid and their remaining balance.
                @else
                    Thank-you messages are not sent after payments for this event. Enable them in settings to keep guests updated automatically.
                @endif
            </p>
            <a href="{{ route('events.edit', $event) }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700">
                Manage in settings <i class="fas fa-arrow-right text-[9px]"></i>
            </a>
        </div>

        <!-- Check-in progress -->
        <div class="card-surface p-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><i class="fas fa-clipboard-check text-xs"></i></div>
                    <h3 class="text-sm font-semibold text-surface-900">Check-in progress</h3>
                </div>
                <span class="text-lg font-bold text-surface-900">{{ $checkinRate }}%</span>
            </div>
            <div class="mt-5 flex items-center gap-5">
                <div class="relative h-24 w-24 shrink-0 rounded-full" style="background: conic-gradient(#e11d48 0 {{ $checkinRate }}%, #f4f4f5 {{ $checkinRate }}% 100%);">
                    <div class="absolute inset-[8px] flex flex-col items-center justify-center rounded-full bg-white">
                        <span class="text-xl font-bold text-surface-900">{{ number_format($stats['checked_in']) }}</span>
                        <span class="text-[9px] font-semibold uppercase tracking-wider text-surface-400">checked in</span>
                    </div>
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-surface-500">Expected</span>
                        <span class="text-sm font-bold text-surface-900">{{ number_format($stats['total_guests']) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-surface-500">Attended</span>
                        <span class="text-sm font-bold text-green-600">{{ number_format($stats['checked_in']) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-surface-500">Remaining</span>
                        <span class="text-sm font-bold text-amber-600">{{ number_format($stats['not_checked_in']) }}</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('events.scanner.index', $event) }}" class="btn btn-primary btn-sm btn-block mt-5">
                <i class="fas fa-qrcode"></i> Open scanner
            </a>
        </div>

        <!-- RSVP -->
        <div class="card-surface p-5">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600"><i class="fas fa-envelope-circle-check text-xs"></i></div>
                <h3 class="text-sm font-semibold text-surface-900">RSVP status</h3>
            </div>
            <div class="mt-5 flex items-center gap-5">
                <div class="relative h-24 w-24 shrink-0 rounded-full"
                    style="background: conic-gradient(#22c55e 0 {{ $attendPct }}%, #f87171 {{ $attendPct }}% {{ $attendPct + $notAttendPct }}%, #facc15 {{ $attendPct + $notAttendPct }}% 100%);">
                    <div class="absolute inset-[8px] flex flex-col items-center justify-center rounded-full bg-white">
                        <span class="text-xl font-bold text-green-600">{{ number_format($stats['rsvp_attending']) }}</span>
                        <span class="text-[9px] font-semibold uppercase tracking-wider text-surface-400">attending</span>
                    </div>
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="flex items-center gap-2 text-xs text-surface-500"><span class="h-2 w-2 rounded-full bg-green-500"></span>Nitahudhuria</span>
                        <span class="text-sm font-bold text-green-600">{{ number_format($stats['rsvp_attending']) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="flex items-center gap-2 text-xs text-surface-500"><span class="h-2 w-2 rounded-full bg-red-400"></span>Sitahudhuria</span>
                        <span class="text-sm font-bold text-red-500">{{ number_format($stats['rsvp_not_attending']) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="flex items-center gap-2 text-xs text-surface-500"><span class="h-2 w-2 rounded-full bg-yellow-400"></span>Pending</span>
                        <span class="text-sm font-bold text-yellow-600">{{ number_format($stats['rsvp_pending']) }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 rounded-lg bg-surface-50 px-3 py-2.5 text-center text-[11px] text-surface-500">
                <i class="fas fa-chair mr-1 text-surface-400"></i>{{ number_format($stats['estimated_attendance']) }} estimated seats · {{ number_format($confirmedAttendance) }} confirmed
            </div>
        </div>

        <!-- Budget & vendors -->
        <div class="card-surface overflow-hidden">
            <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gold-50 text-gold-600"><i class="fas fa-calculator text-xs"></i></div>
                    <div>
                        <h3 class="text-sm font-semibold text-surface-900">Budget &amp; vendors</h3>
                        <p class="text-[10px] text-surface-400">{{ $budget['itemCount'] }} services · {{ $budget['vendorCount'] }} vendors</p>
                    </div>
                </div>
                <span class="badge bg-gold-50 text-gold-700">{{ $budget['paidRate'] }}% paid</span>
            </div>
            <div class="p-5">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Total budget</p>
                        <p class="mt-0.5 font-display text-xl font-bold text-surface-900">TZS {{ $money($budget['totalBudget']) }}</p>
                    </div>
                    @if($budget['estimatedGuests'] > 0)
                    <p class="text-right text-[10px] text-surface-400">{{ number_format($budget['estimatedGuests']) }} guests<br><span class="font-semibold text-surface-600">TZS {{ $money($budget['costPerGuest']) }}/guest</span></p>
                    @endif
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-surface-100">
                    <div class="h-full rounded-full bg-gradient-to-r from-gold-500 to-amber-300" style="width: {{ $budget['paidRate'] }}%;"></div>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <div class="rounded-lg bg-green-50 px-3 py-2">
                        <p class="text-[9px] font-bold uppercase tracking-wider text-green-600/70">Vendor paid</p>
                        <p class="mt-0.5 text-xs font-bold text-green-700">TZS {{ $money($budget['totalPaid']) }}</p>
                    </div>
                    <div class="rounded-lg bg-red-50 px-3 py-2">
                        <p class="text-[9px] font-bold uppercase tracking-wider text-red-500">Outstanding</p>
                        <p class="mt-0.5 text-xs font-bold text-red-600">TZS {{ $money($budget['outstanding']) }}</p>
                    </div>
                </div>
                <a href="{{ route('events.budget.index', $event) }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700">
                    Manage budget <i class="fas fa-arrow-right text-[9px]"></i>
                </a>
            </div>
        </div>

        <!-- Contributions -->
        <div class="card-surface relative overflow-hidden p-5">
            <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-gold-100/70"></div>
            <div class="relative">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gold-50 text-gold-600"><i class="fas fa-coins text-xs"></i></div>
                        <h3 class="text-sm font-semibold text-surface-900">Contributions</h3>
                    </div>
                    <span class="text-lg font-bold text-gold-700">{{ $stats['collection_rate'] }}%</span>
                </div>
                <div class="mt-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-surface-400">Total pledged</p>
                    <p class="mt-0.5 font-display text-2xl font-bold text-surface-900">TZS {{ $money($stats['total_pledged']) }}</p>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-surface-100">
                    <div class="h-full rounded-full bg-gradient-to-r from-gold-500 to-amber-400" style="width: {{ $stats['collection_rate'] }}%;"></div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2.5">
                    <div class="rounded-lg bg-green-50 px-3 py-2.5">
                        <p class="text-[10px] uppercase tracking-wider text-green-600">Paid</p>
                        <p class="mt-0.5 text-sm font-bold text-green-700">TZS {{ $money($stats['total_paid']) }}</p>
                    </div>
                    <div class="rounded-lg bg-red-50 px-3 py-2.5">
                        <p class="text-[10px] uppercase tracking-wider text-red-500">Outstanding</p>
                        <p class="mt-0.5 text-sm font-bold text-red-600">TZS {{ $money($stats['outstanding']) }}</p>
                    </div>
                </div>
                <a href="{{ route('events.report', $event) }}" class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700">
                    View full report <i class="fas fa-arrow-right text-[9px]"></i>
                </a>
            </div>
        </div>

        <!-- Top contributors -->
        <div class="card-surface p-5">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-surface-100 text-surface-600"><i class="fas fa-trophy text-xs"></i></div>
                    <h3 class="text-sm font-semibold text-surface-900">Top contributors</h3>
                </div>
            </div>
            @if($topGuests->isEmpty())
            <p class="py-4 text-center text-xs text-surface-400">No contributions recorded yet.</p>
            @else
            <div class="space-y-3.5">
                @foreach($topGuests as $index => $guest)
                @php
                    $guestPledged = (float) $guest->amount_contributed;
                    $guestPaid = (float) $guest->paid_amount;
                    $guestPct = $guestPledged > 0 ? (int) min(100, round(($guestPaid / $guestPledged) * 100)) : ($guestPaid > 0 ? 100 : 0);
                @endphp
                <div class="flex items-center gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $index === 0 ? 'bg-gold-100 text-gold-700' : 'bg-surface-100 text-surface-500' }} text-[10px] font-bold">{{ $index + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-xs font-medium text-surface-700">{{ $guest->full_name }}</p>
                            <p class="shrink-0 text-[10px] font-semibold text-surface-500">TZS {{ $money($guestPaid) }}</p>
                        </div>
                        <div class="mt-1 h-1 overflow-hidden rounded-full bg-surface-100">
                            <div class="h-full rounded-full {{ $guestPct >= 100 ? 'bg-green-500' : 'bg-gold-500' }}" style="width: {{ $guestPct }}%;"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <a href="{{ route('events.guests.index', $event) }}" class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700">
                Manage guests <i class="fas fa-arrow-right text-[9px]"></i>
            </a>
            @endif
        </div>
    </div>
</div>
@endsection
