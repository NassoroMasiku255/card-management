@extends('layouts.app')

@section('title', $event->name)

@section('content')
@php
    $accent = $event->card_accent_color ?: '#e11d48';
    $cover = $event->cover_image ? asset('storage/'.$event->cover_image) : null;

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

    $navLink = 'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-[13px] font-medium text-surface-600 transition hover:bg-primary-50 hover:text-primary-700';
    $cardLink = 'inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700';
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

<!-- Header -->
<div class="card-surface mb-6 overflow-hidden border-l-4 border-primary-500">

    <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-4">
            @if($cover)
            <img src="{{ $cover }}" alt="" class="h-14 w-14 shrink-0 rounded-xl object-cover sm:h-16 sm:w-16">
            @endif
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge {{ $statusTone }}">{{ ucfirst($event->status) }}</span>
                    <span class="badge bg-surface-100 text-surface-600">
                        @if($daysToEvent !== null)
                            {{ $daysToEvent }} {{ $daysToEvent === 1 ? 'day' : 'days' }} to go
                        @else
                            {{ $event->event_date->isToday() ? 'Happening today' : 'Event day passed' }}
                        @endif
                    </span>
                </div>
                <h1 class="mt-2.5 font-display text-2xl font-bold leading-tight text-surface-900 sm:text-3xl">{{ $event->name }}</h1>
                <div class="mt-2.5 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm text-surface-600">
                    <span><i class="fas fa-calendar mr-1.5 text-surface-400"></i>{{ $event->event_date->format('l, d M Y') }}</span>
                    @if($event->event_time)
                    <span><i class="fas fa-clock mr-1.5 text-surface-400"></i>{{ $event->event_time }}</span>
                    @endif
                    <span><i class="fas fa-location-dot mr-1.5 text-surface-400"></i>{{ $event->location }}</span>
                    @if($event->hall)
                    <span><i class="fas fa-building mr-1.5 text-surface-400"></i>{{ $event->hall }}</span>
                    @endif
                </div>
                @if($event->description)
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-surface-500">{{ $event->description }}</p>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a href="{{ route('events.scanner.index', $event) }}" class="btn btn-primary">
                <i class="fas fa-qrcode"></i> Open scanner
            </a>
            <a href="{{ route('events.edit', $event) }}" class="btn btn-outline">
                <i class="fas fa-pen"></i> Edit
            </a>
        </div>
    </div>

    <nav class="flex flex-wrap gap-1 border-t border-surface-100 px-3 py-2 sm:px-4" aria-label="Event sections">
        <a href="{{ route('events.guests.index', $event) }}" class="{{ $navLink }}"><i class="fas fa-users text-xs text-surface-400"></i> Guests</a>
        <a href="{{ route('events.invitations.index', $event) }}" class="{{ $navLink }}"><i class="fas fa-paper-plane text-xs text-surface-400"></i> Invitations</a>
        <a href="{{ route('events.report', $event) }}" class="{{ $navLink }}"><i class="fas fa-chart-pie text-xs text-surface-400"></i> Report</a>
        <a href="{{ route('events.messages.report', $event) }}" class="{{ $navLink }}"><i class="fas fa-tower-broadcast text-xs text-surface-400"></i> Messages</a>
        <a href="{{ route('events.budget.index', $event) }}" class="{{ $navLink }}"><i class="fas fa-calculator text-xs text-surface-400"></i> Budget</a>
        <a href="{{ route('events.guests.import', $event) }}" class="{{ $navLink }}"><i class="fas fa-file-import text-xs text-surface-400"></i> Import</a>
        <a href="{{ route('events.countdown-card', $event) }}" target="_blank" class="{{ $navLink }}"><i class="fab fa-whatsapp text-sm text-surface-400"></i> Countdown</a>
    </nav>
</div>

<!-- Key numbers -->
<div class="mb-6 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-surface-200 bg-surface-200 shadow-soft sm:grid-cols-3 xl:grid-cols-6">
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Guests</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($stats['total_guests']) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ $stats['singles'] }} single · {{ $stats['doubles'] }} double</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Invitations</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($stats['invitations_sent']) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ $deliveryRate }}% delivered · {{ $stats['invitations_pending'] }} pending</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Attending</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($stats['rsvp_attending']) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ number_format($confirmedAttendance) }} seats confirmed</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Checked in</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($stats['checked_in']) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ $checkinRate }}% of guests · {{ $stats['not_checked_in'] }} remaining</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Pledged</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ $money($stats['total_pledged']) }}</p>
        <p class="mt-1 text-xs text-surface-400">TZS total contribution</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Collected</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ $money($stats['total_paid']) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ $stats['collection_rate'] }}% of pledged</p>
    </div>
</div>

<!-- Main content -->
<div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
    <!-- Left column -->
    <div class="space-y-6 xl:col-span-2">
        <!-- Recent guests -->
        <div class="card-surface overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Recent guests</h2>
                    <p class="mt-0.5 text-xs text-surface-500">Latest guests added to this event</p>
                </div>
                <a href="{{ route('events.guests.index', $event) }}" class="{{ $cardLink }} shrink-0">View all <i class="fas fa-arrow-right text-[9px]"></i></a>
            </div>

            @if($event->guests->isEmpty())
            <div class="px-6 py-12 text-center">
                <p class="text-sm font-medium text-surface-700">No guests added yet</p>
                <p class="mt-1 text-xs text-surface-500">Add guests manually or import your list.</p>
                <div class="mt-4 flex justify-center gap-2">
                    <a href="{{ route('events.guests.create', $event) }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add guest</a>
                    <a href="{{ route('events.guests.import', $event) }}" class="btn btn-outline btn-sm"><i class="fas fa-file-import"></i> Import</a>
                </div>
            </div>
            @else
            <div class="responsive-table-wrap overflow-x-auto">
                <table class="responsive-table w-full">
                    <thead>
                        <tr class="border-b border-surface-100">
                            <th class="px-5 py-2.5 text-left text-xs font-medium text-surface-500 sm:px-6">Guest</th>
                            <th class="px-5 py-2.5 text-left text-xs font-medium text-surface-500 sm:px-6">Phone</th>
                            <th class="px-5 py-2.5 text-left text-xs font-medium text-surface-500 sm:px-6">Card</th>
                            <th class="px-5 py-2.5 text-left text-xs font-medium text-surface-500 sm:px-6">RSVP</th>
                            <th class="px-5 py-2.5 text-left text-xs font-medium text-surface-500 sm:px-6">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100">
                        @foreach($event->guests->take(10) as $guest)
                        <tr class="transition hover:bg-surface-50">
                            <td class="px-5 py-3 sm:px-6" data-label="Guest">
                                <div class="max-lg:text-right">
                                    <div class="truncate text-sm font-medium text-surface-900">{{ $guest->full_name }}</div>
                                    <div class="text-xs text-surface-400">{{ $guest->unique_id }}</div>
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
                                        {{ $guest->invitation->send_status === 'delivered' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $guest->invitation->send_status === 'read' ? 'bg-teal-100 text-teal-700' : '' }}
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

        <!-- Reminder payment accounts -->
        <section class="card-surface overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Reminder payment accounts</h2>
                    <p class="mt-0.5 text-xs text-surface-500">All accounts listed here are included in reminder SMS messages.</p>
                </div>
                <span class="badge shrink-0 bg-surface-100 text-surface-600">{{ $event->paymentMethods->count() }} saved</span>
            </div>

            @if($event->paymentMethods->isNotEmpty())
            <div class="divide-y divide-surface-100">
                @foreach($event->paymentMethods as $paymentMethod)
                <div class="flex items-center justify-between gap-3 px-5 py-3 sm:px-6">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-surface-900">{{ $paymentMethod->payment_name }}</p>
                        <p class="mt-0.5 text-xs text-surface-500">{{ $paymentMethod->payment_number }}@if($paymentMethod->account_holder). {{ $paymentMethod->account_holder }}@endif</p>
                    </div>
                    <form method="POST" action="{{ route('events.payment-methods.destroy', [$event, $paymentMethod]) }}"
                        onsubmit="return confirm('Remove this payment account?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-action text-surface-400 hover:bg-red-50 hover:text-red-600" title="Remove payment account">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
            @else
            <p class="px-5 py-4 text-sm text-surface-500 sm:px-6">No event payment accounts have been added.</p>
            @endif

            <form method="POST" action="{{ route('events.payment-methods.store', $event) }}"
                class="grid grid-cols-1 gap-3 border-t border-surface-100 px-5 py-4 sm:grid-cols-3 sm:items-end sm:px-6"
                data-loading-text="Adding payment account..." data-loading-hint="Saving this account for the event.">
                @csrf
                <div>
                    <label for="payment_name" class="label-field">Account name</label>
                    <input id="payment_name" name="payment_name" type="text" maxlength="255" required class="input-field" placeholder="e.g. M-Pesa">
                </div>
                <div>
                    <label for="payment_number" class="label-field">Payment number</label>
                    <input id="payment_number" name="payment_number" type="text" maxlength="100" required class="input-field" placeholder="e.g. 0712 345 678">
                </div>
                <div>
                    <label for="account_holder" class="label-field">Account holder</label>
                    <input id="account_holder" name="account_holder" type="text" maxlength="255" class="input-field" placeholder="e.g. Nassoro Masiku">
                </div>
                <div class="sm:col-span-3">
                    <button type="submit" class="btn btn-outline btn-sm">
                        <i class="fas fa-plus"></i> Add account
                    </button>
                </div>
            </form>
        </section>

        <!-- Activity -->
        <div class="card-surface overflow-hidden">
            <div class="border-b border-surface-100 px-5 py-4 sm:px-6">
                <h2 class="text-sm font-semibold text-surface-900">Event activity</h2>
                <p class="mt-0.5 text-xs text-surface-500">Everything that has happened recently</p>
            </div>

            @if($activityLogs->isEmpty())
            <div class="px-6 py-10 text-center">
                <p class="text-sm font-medium text-surface-700">No activity yet</p>
                <p class="mt-1 text-xs text-surface-500">Guest additions, invitations and check-ins will appear here.</p>
            </div>
            @else
            <ul class="divide-y divide-surface-100">
                @foreach($activityLogs as $log)
                @php
                    $activityIcon = match ($log->action) {
                        'guest_added', 'guests_imported' => 'fa-user-plus',
                        'invitation_sent', 'bulk_invitations_sent', 'whatsapp_message_sent' => 'fa-paper-plane',
                        'payment_recorded', 'payment_deleted' => 'fa-coins',
                        'budget_item_added', 'budget_item_updated', 'budget_item_removed', 'budget_guests_updated' => 'fa-calculator',
                        'guest_checked_in' => 'fa-qrcode',
                        'card_image_uploaded' => 'fa-image',
                        default => 'fa-circle-info',
                    };
                @endphp
                <li class="flex gap-3 px-5 py-3.5 sm:px-6">
                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-100 text-surface-500">
                        <i class="fas {{ $activityIcon }} text-[11px]"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm leading-relaxed text-surface-700">{{ $log->description }}</p>
                        <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-surface-400">
                            <span>{{ $log->created_at->format('d M Y, H:i') }}</span>
                            <span>·</span>
                            <span>{{ $log->created_at->diffForHumans() }}</span>
                            @if($log->user)
                            <span>·</span>
                            <span>{{ $log->user->name }}</span>
                            @endif
                        </p>
                    </div>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

    <!-- Right sidebar -->
    <div class="space-y-6">
        <!-- Check-in progress -->
        <div class="card-surface p-5">
            <div class="flex items-baseline justify-between gap-3">
                <h3 class="text-sm font-semibold text-surface-900">Check-in progress</h3>
                <span class="text-sm font-semibold text-surface-900">{{ $checkinRate }}%</span>
            </div>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-surface-100">
                <div class="h-full rounded-full bg-primary-500" style="width: {{ $checkinRate }}%;"></div>
            </div>
            <dl class="mt-4 grid grid-cols-3 gap-3">
                <div>
                    <dt class="text-xs text-surface-500">Expected</dt>
                    <dd class="mt-0.5 text-lg font-semibold text-surface-900">{{ number_format($stats['total_guests']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-surface-500">Attended</dt>
                    <dd class="mt-0.5 text-lg font-semibold text-surface-900">{{ number_format($stats['checked_in']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-surface-500">Remaining</dt>
                    <dd class="mt-0.5 text-lg font-semibold text-surface-900">{{ number_format($stats['not_checked_in']) }}</dd>
                </div>
            </dl>
            <a href="{{ route('events.scanner.index', $event) }}" class="btn btn-outline btn-sm btn-block mt-4">
                <i class="fas fa-qrcode"></i> Open scanner
            </a>
        </div>

        <!-- RSVP -->
        <div class="card-surface p-5">
            <div class="flex items-baseline justify-between gap-3">
                <h3 class="text-sm font-semibold text-surface-900">RSVP status</h3>
                <span class="text-xs text-surface-500">{{ number_format($stats['rsvp_attending']) }} attending</span>
            </div>
            <div class="mt-3 flex h-1.5 gap-0.5 overflow-hidden rounded-full bg-surface-100">
                <div class="h-full bg-green-500" style="width: {{ $attendPct }}%;"></div>
                <div class="h-full bg-red-400" style="width: {{ $notAttendPct }}%;"></div>
                <div class="h-full bg-yellow-400" style="width: {{ $pendingPct }}%;"></div>
            </div>
            <div class="mt-4 space-y-2.5">
                <div class="flex items-center justify-between gap-2">
                    <span class="flex items-center gap-2 text-sm text-surface-600"><span class="h-2 w-2 rounded-full bg-green-500"></span>Nitahudhuria</span>
                    <span class="text-sm font-semibold text-surface-900">{{ number_format($stats['rsvp_attending']) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="flex items-center gap-2 text-sm text-surface-600"><span class="h-2 w-2 rounded-full bg-red-400"></span>Sitahudhuria</span>
                    <span class="text-sm font-semibold text-surface-900">{{ number_format($stats['rsvp_not_attending']) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="flex items-center gap-2 text-sm text-surface-600"><span class="h-2 w-2 rounded-full bg-yellow-400"></span>Pending</span>
                    <span class="text-sm font-semibold text-surface-900">{{ number_format($stats['rsvp_pending']) }}</span>
                </div>
            </div>
            <p class="mt-4 border-t border-surface-100 pt-3 text-xs text-surface-500">
                {{ number_format($stats['estimated_attendance']) }} estimated seats · {{ number_format($confirmedAttendance) }} confirmed
            </p>
        </div>

        <!-- Contributions -->
        <div class="card-surface p-5">
            <div class="flex items-baseline justify-between gap-3">
                <h3 class="text-sm font-semibold text-surface-900">Contributions</h3>
                <span class="text-xs text-surface-500">{{ $stats['collection_rate'] }}% collected</span>
            </div>
            <p class="mt-3 text-xs text-surface-500">Total pledged</p>
            <p class="mt-0.5 text-2xl font-semibold tracking-tight text-surface-900">TZS {{ $money($stats['total_pledged']) }}</p>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-surface-100">
                <div class="h-full rounded-full bg-green-500" style="width: {{ $stats['collection_rate'] }}%;"></div>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-3">
                <div>
                    <dt class="text-xs text-surface-500">Paid</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-green-700">TZS {{ $money($stats['total_paid']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-surface-500">Outstanding</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-red-600">TZS {{ $money($stats['outstanding']) }}</dd>
                </div>
            </dl>
            <a href="{{ route('events.report', $event) }}" class="{{ $cardLink }} mt-4">
                View full report <i class="fas fa-arrow-right text-[9px]"></i>
            </a>
        </div>

        <!-- Budget & vendors -->
        <div class="card-surface p-5">
            <div class="flex items-baseline justify-between gap-3">
                <h3 class="text-sm font-semibold text-surface-900">Budget &amp; vendors</h3>
                <span class="text-xs text-surface-500">{{ $budget['paidRate'] }}% paid</span>
            </div>
            <p class="mt-0.5 text-xs text-surface-500">{{ $budget['itemCount'] }} services · {{ $budget['vendorCount'] }} vendors</p>
            <div class="mt-3 flex items-end justify-between gap-3">
                <div>
                    <p class="text-xs text-surface-500">Total budget</p>
                    <p class="mt-0.5 text-2xl font-semibold tracking-tight text-surface-900">TZS {{ $money($budget['totalBudget']) }}</p>
                </div>
                @if($budget['estimatedGuests'] > 0)
                <p class="text-right text-xs text-surface-500">{{ number_format($budget['estimatedGuests']) }} guests<br><span class="font-medium text-surface-700">TZS {{ $money($budget['costPerGuest']) }}/guest</span></p>
                @endif
            </div>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-surface-100">
                <div class="h-full rounded-full bg-green-500" style="width: {{ $budget['paidRate'] }}%;"></div>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-3">
                <div>
                    <dt class="text-xs text-surface-500">Vendor paid</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-green-700">TZS {{ $money($budget['totalPaid']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-surface-500">Outstanding</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-red-600">TZS {{ $money($budget['outstanding']) }}</dd>
                </div>
            </dl>
            <a href="{{ route('events.budget.index', $event) }}" class="{{ $cardLink }} mt-4">
                Manage budget <i class="fas fa-arrow-right text-[9px]"></i>
            </a>
        </div>

        <!-- Top contributors -->
        <div class="card-surface p-5">
            <h3 class="text-sm font-semibold text-surface-900">Top contributors</h3>
            @if($topGuests->isEmpty())
            <p class="mt-3 text-sm text-surface-500">No contributions recorded yet.</p>
            @else
            <div class="mt-4 space-y-3.5">
                @foreach($topGuests as $index => $guest)
                @php
                    $guestPledged = (float) $guest->amount_contributed;
                    $guestPaid = (float) $guest->paid_amount;
                    $guestPct = $guestPledged > 0 ? (int) min(100, round(($guestPaid / $guestPledged) * 100)) : ($guestPaid > 0 ? 100 : 0);
                @endphp
                <div class="flex items-start gap-3">
                    <span class="w-4 shrink-0 text-xs font-medium text-surface-400">{{ $index + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-sm text-surface-700">{{ $guest->full_name }}</p>
                            <p class="shrink-0 text-xs font-semibold text-surface-900">TZS {{ $money($guestPaid) }}</p>
                        </div>
                        <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-surface-100">
                            <div class="h-full rounded-full {{ $guestPct >= 100 ? 'bg-green-500' : 'bg-gold-500' }}" style="width: {{ $guestPct }}%;"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <a href="{{ route('events.guests.index', $event) }}" class="{{ $cardLink }} mt-4">
                Manage guests <i class="fas fa-arrow-right text-[9px]"></i>
            </a>
            @endif
        </div>

        <!-- Payment thank-you status -->
        <div class="card-surface p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-surface-900">Payment thank-you</h3>
                    <p class="mt-0.5 text-xs text-surface-500">Automatic WhatsApp message</p>
                </div>
                <span class="badge shrink-0 {{ $event->payment_thank_you_enabled ? 'bg-green-100 text-green-700' : 'bg-surface-100 text-surface-500' }}">
                    {{ $event->payment_thank_you_enabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>
            <p class="mt-3 text-xs leading-relaxed text-surface-500">
                @if($event->payment_thank_you_enabled)
                    Guests receive a WhatsApp thank-you after each payment with the amount paid and their remaining balance.
                @else
                    Thank-you messages are not sent after payments for this event. An administrator can enable them in the event settings.
                @endif
            </p>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('events.edit', $event) }}" class="{{ $cardLink }} mt-3">
                Manage in settings <i class="fas fa-arrow-right text-[9px]"></i>
            </a>
            @endif
        </div>
    </div>
</div>
@endsection
