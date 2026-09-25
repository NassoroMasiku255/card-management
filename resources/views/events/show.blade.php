@extends('layouts.app')

@section('title', $event->name . ' - Card Management')

@section('content')
@php
    $checkinRate = $stats['total_guests'] > 0 ? round(($stats['checked_in'] / $stats['total_guests']) * 100) : 0;
    $rsvpTotal = max($stats['rsvp_attending'] + $stats['rsvp_not_attending'] + $stats['rsvp_pending'], 1);
    $attendPct = round(($stats['rsvp_attending'] / $rsvpTotal) * 100);
    $notAttendPct = round(($stats['rsvp_not_attending'] / $rsvpTotal) * 100);
    $pendingPct = max(100 - $attendPct - $notAttendPct, 0);
@endphp

<div class="mb-6">
    <a href="{{ route('events.index') }}" class="text-surface-500 hover:text-surface-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to Events
    </a>
</div>

<!-- Header: event identity + all actions on one row -->
<div class="card-surface p-5 md:p-6 mb-6 relative overflow-hidden">
    <div class="absolute inset-x-0 top-0 h-1" style="background: linear-gradient(90deg, {{ $event->card_accent_color ?: '#e11d48' }}, {{ $event->card_accent_color ?: '#e11d48' }}55);"></div>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4 min-w-0">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-soft"
                style="background: {{ $event->card_accent_color ?: '#e11d48' }}1a;">
                <i class="fas fa-heart text-xl" style="color: {{ $event->card_accent_color ?: '#e11d48' }};"></i>
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="font-display text-xl md:text-2xl font-bold text-surface-900 truncate">{{ $event->name }}</h1>
                    <span class="badge {{ $event->status === 'active' ? 'bg-green-100 text-green-700' : ($event->status === 'completed' ? 'bg-blue-100 text-blue-700' : 'bg-surface-100 text-surface-600') }}">
                        {{ ucfirst($event->status) }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-sm text-surface-500">
                    <span><i class="fas fa-calendar mr-1"></i> {{ $event->event_date->format('d M Y') }}</span>
                    <span><i class="fas fa-location-dot mr-1"></i> {{ $event->location }}</span>
                    @if($event->hall)<span><i class="fas fa-building mr-1"></i> {{ $event->hall }}</span>@endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <a href="{{ route('events.guests.index', $event) }}" class="btn btn-outline btn-sm">
                <i class="fas fa-users"></i> Guest
            </a>
            <a href="{{ route('events.invitations.index', $event) }}" class="btn btn-outline btn-sm">
                <i class="fas fa-paper-plane"></i> Invitation
            </a>
            <a href="{{ route('events.edit', $event) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-pen"></i> Edit
            </a>
            <a href="{{ route('events.scanner.index', $event) }}" class="btn btn-success btn-sm">
                <i class="fas fa-qrcode"></i> Scanner
            </a>
        </div>
    </div>
</div>

<!-- Main content + statistics sidebar -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

    <!-- Left: Recent Guests -->
    <div class="lg:col-span-2 card-surface">
        <div class="px-6 py-4 border-b border-surface-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-surface-900">Recent Guests</h2>
            <a href="{{ route('events.guests.index', $event) }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">View All</a>
        </div>

        @if($event->guests->isEmpty())
        <div class="px-6 py-14 text-center">
            <div class="w-14 h-14 bg-surface-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-user-plus text-surface-400"></i>
            </div>
            <p class="text-surface-500">No guests added yet.</p>
            <a href="{{ route('events.guests.create', $event) }}" class="inline-flex items-center gap-1 mt-3 text-primary-600 hover:text-primary-700 font-medium text-sm">
                <i class="fas fa-plus"></i> Add your first guest
            </a>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-surface-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Guest</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Card Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">RSVP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100">
                    @foreach($event->guests->take(10) as $guest)
                    <tr class="hover:bg-surface-50">
                        <td class="px-6 py-3">
                            <div class="font-medium text-surface-900">{{ $guest->full_name }}</div>
                            <div class="text-xs text-surface-400">{{ $guest->unique_id }}</div>
                        </td>
                        <td class="px-6 py-3 text-sm text-surface-600">{{ $guest->phone_number }}</td>
                        <td class="px-6 py-3">
                            <span class="badge {{ $guest->card_type === 'double' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ ucfirst($guest->card_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            @if($guest->invitation)
                                <span class="badge
                                    {{ $guest->invitation->rsvp_status === 'attending' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $guest->invitation->rsvp_status === 'not_attending' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $guest->invitation->rsvp_status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}">
                                    {{ $guest->invitation->rsvp_status === 'attending' ? 'Nitafika' : ($guest->invitation->rsvp_status === 'not_attending' ? 'Sitafika' : 'Pending') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-3">
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

    <!-- Right: Statistics sidebar -->
    <div class="space-y-5">

        <!-- Check-in progress -->
        <div class="card-surface p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-surface-800"><i class="fas fa-clipboard-check text-primary-500 mr-1.5"></i>Check-in Progress</h3>
                <span class="text-sm font-bold text-surface-900">{{ $checkinRate }}%</span>
            </div>
            <div class="w-full h-2.5 rounded-full bg-surface-100 overflow-hidden">
                <div class="h-full rounded-full bg-primary-500" style="width: {{ $checkinRate }}%;"></div>
            </div>
            <div class="flex items-center justify-between mt-3 text-xs text-surface-500">
                <span><i class="fas fa-circle-check text-green-500 mr-1"></i>{{ $stats['checked_in'] }} checked in</span>
                <span>{{ $stats['not_checked_in'] }} remaining</span>
            </div>
        </div>

        <!-- Guest overview -->
        <div class="card-surface p-5">
            <h3 class="text-sm font-semibold text-surface-800 mb-4"><i class="fas fa-users text-blue-500 mr-1.5"></i>Guest Overview</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-surface-500 flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-surface-400"></span>Total Guests</span>
                    <span class="text-sm font-bold text-surface-900">{{ $stats['total_guests'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-surface-500 flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Invitations Sent</span>
                    <span class="text-sm font-bold text-blue-600">{{ $stats['invitations_sent'] }}</span>
                </div>
            </div>
        </div>

        <!-- RSVP breakdown -->
        <div class="card-surface p-5">
            <h3 class="text-sm font-semibold text-surface-800 mb-3"><i class="fas fa-envelope-circle-check text-green-500 mr-1.5"></i>RSVP Status</h3>
            <div class="w-full h-2.5 rounded-full overflow-hidden flex bg-surface-100 mb-4">
                <div class="h-full bg-green-500" style="width: {{ $attendPct }}%;"></div>
                <div class="h-full bg-red-400" style="width: {{ $notAttendPct }}%;"></div>
                <div class="h-full bg-yellow-400" style="width: {{ $pendingPct }}%;"></div>
            </div>
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-surface-500 flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Nitafika (Will Attend)</span>
                    <span class="text-sm font-bold text-green-600">{{ $stats['rsvp_attending'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-surface-500 flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>Sitafika (Won't Attend)</span>
                    <span class="text-sm font-bold text-red-500">{{ $stats['rsvp_not_attending'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-surface-500 flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span>Pending</span>
                    <span class="text-sm font-bold text-yellow-600">{{ $stats['rsvp_pending'] }}</span>
                </div>
            </div>
        </div>

        <!-- Contributions -->
        <div class="card-surface p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-gold-100 rounded-full opacity-60"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gold-100 flex items-center justify-center shrink-0">
                    <i class="fas fa-coins text-gold-600"></i>
                </div>
                <div>
                    <div class="text-xs text-surface-500">Total Contributions</div>
                    <div class="text-lg font-bold text-gold-700">{{ number_format($stats['total_contribution']) }} TZS</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
