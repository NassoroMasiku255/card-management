@extends('layouts.app')

@section('title', 'Events')

@section('content')
@php
    $totalGuestsAll = $events->sum('guests_count');
    $activeCount = $events->where('status', 'active')->count();
    $totalInvitationsAll = $events->sum('invitations_count');
    $totalSent = $events->sum('invitations_sent_count');
    $totalAttending = $events->sum('rsvp_attending_count');
    $totalCheckedIn = $events->sum('checked_in_count');
    $totalPledged = (float) $events->sum('total_pledged');
    $deliveryRate = $totalInvitationsAll > 0 ? (int) round(($totalSent / $totalInvitationsAll) * 100) : 0;
    $collectionRate = $totalPledged > 0
        ? (int) min(100, round(((float) $events->sum('total_paid') / $totalPledged) * 100))
        : 0;

    $money = function ($amount) {
        $amount = (float) $amount;
        if ($amount >= 1000000) return number_format($amount / 1000000, 1).'M';
        if ($amount >= 1000) return number_format($amount / 1000, 0).'K';
        return number_format($amount);
    };
@endphp

<!-- Header -->
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="font-display text-2xl font-bold leading-tight text-surface-900 sm:text-3xl">Events</h1>
        <p class="mt-1.5 max-w-xl text-sm text-surface-500">
            Create and manage invitations, guests, contributions and check-in for every celebration.
        </p>
    </div>
    <a href="{{ route('events.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> New Event
    </a>
</div>

<!-- Key numbers -->
<div class="mb-6 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-surface-200 bg-surface-200 shadow-soft lg:grid-cols-4">
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Events</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ $events->count() }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ $activeCount }} active</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Guests</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($totalGuestsAll) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ number_format($totalAttending) }} attending</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Invitations</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($totalSent) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ $deliveryRate }}% delivered</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Pledged</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">TZS {{ $money($totalPledged) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ $collectionRate }}% collected · {{ number_format($totalCheckedIn) }} checked in</p>
    </div>
</div>

<!-- Search + filters -->
<div class="card-surface mb-5 p-3 sm:p-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="relative flex-1">
            <i class="fas fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-surface-400"></i>
            <input type="text" id="event-search" placeholder="Search by event name, location or hall..."
                class="input-field pl-10" autocomplete="off">
        </div>
        <div class="flex flex-wrap items-center gap-1.5" id="status-filters">
            <button type="button" data-filter="all" class="filter-pill filter-pill-active">All <span class="opacity-60">({{ $events->count() }})</span></button>
            <button type="button" data-filter="active" class="filter-pill">Active <span class="opacity-60">({{ $activeCount }})</span></button>
            <button type="button" data-filter="draft" class="filter-pill">Draft <span class="opacity-60">({{ $events->where('status', 'draft')->count() }})</span></button>
            <button type="button" data-filter="completed" class="filter-pill">Completed <span class="opacity-60">({{ $events->where('status', 'completed')->count() }})</span></button>
            <button type="button" data-filter="cancelled" class="filter-pill">Cancelled</button>
        </div>
    </div>
</div>

@if($events->isEmpty())
<div class="card-surface px-6 py-16 text-center">
    <p class="text-sm font-medium text-surface-700">No events created yet</p>
    <p class="mt-1 text-xs text-surface-400">Start by creating your first event — it only takes a minute.</p>
    <a href="{{ route('events.create') }}" class="btn btn-primary btn-sm mt-4"><i class="fas fa-plus"></i> Create Event</a>
</div>
@else

<!-- Empty search result state -->
<div id="no-results" class="hidden card-surface mb-4 px-6 py-12 text-center">
    <p class="text-sm font-medium text-surface-700">No events match your search</p>
    <p class="mt-1 text-xs text-surface-400">Try a different name, location or filter.</p>
</div>

<div class="mb-3 flex items-center justify-between">
    <p id="result-count" class="text-xs font-medium text-surface-400">Showing {{ $events->count() }} {{ Str::plural('event', $events->count()) }}</p>
    <p class="text-xs text-surface-400">Select an event to manage it</p>
</div>

<div class="space-y-3" id="events-grid">
    @foreach($events as $event)
    @php
        $accent = $event->card_accent_color ?: '#e11d48';
        $searchBlob = strtolower($event->name.' '.$event->location.' '.$event->hall);
        $isUpcoming = $event->event_date->isFuture();
        $cover = $event->cover_image ? asset('storage/'.$event->cover_image) : null;
        $delivery = $event->invitations_count > 0 ? (int) round(($event->invitations_sent_count / $event->invitations_count) * 100) : 0;
        $checkin = $event->guests_count > 0 ? (int) round(($event->checked_in_count / $event->guests_count) * 100) : 0;
        $pledged = (float) $event->total_pledged;
        $paid = (float) $event->total_paid;
        $collection = $pledged > 0 ? (int) min(100, round(($paid / $pledged) * 100)) : 0;
        $quickLink = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-surface-600 transition hover:bg-surface-100 hover:text-surface-900';
    @endphp
    <div class="event-card card-surface overflow-hidden transition hover:border-surface-300"
        data-status="{{ $event->status }}" data-search="{{ $searchBlob }}">

        <div class="flex flex-col gap-5 p-5 lg:flex-row lg:items-center">
            <!-- Date + details -->
            <div class="flex min-w-0 flex-1 items-start gap-4">
                <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center overflow-hidden rounded-xl border border-surface-200 bg-surface-50 text-center">
                    <span class="text-xl font-semibold leading-none text-surface-900">{{ $event->event_date->format('d') }}</span>
                    <span class="mt-1 text-[10px] font-semibold uppercase tracking-wider" style="color: {{ $accent }};">{{ $event->event_date->format('M Y') }}</span>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge {{ $event->status === 'active' ? 'bg-green-100 text-green-700' : ($event->status === 'completed' ? 'bg-blue-100 text-blue-700' : ($event->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-surface-100 text-surface-600')) }}">
                            {{ ucfirst($event->status) }}
                        </span>
                        <span class="badge bg-surface-100 text-surface-600">
                            @if($isUpcoming)
                            {{ $event->event_date->diffForHumans(null, true) }} left
                            @else
                            {{ $event->event_date->isToday() ? 'Today' : 'Past event' }}
                            @endif
                        </span>
                        @if(auth()->user()->isAdmin() && $event->user)
                        <span class="inline-flex items-center gap-1.5 text-xs text-surface-400">
                            <i class="fas fa-user text-[10px]"></i>{{ $event->user->name }}
                        </span>
                        @endif
                    </div>
                    <div class="mt-2 flex min-w-0 items-center gap-2.5">
                        @if($cover)
                        <img src="{{ $cover }}" alt="" class="h-7 w-7 shrink-0 rounded-lg object-cover">
                        @endif
                        <a href="{{ route('events.show', $event) }}" class="block min-w-0 truncate font-display text-xl font-bold text-surface-900 transition hover:text-primary-600">{{ $event->name }}</a>
                    </div>
                    <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-surface-500">
                        <span><i class="fas fa-calendar mr-1.5 text-surface-400"></i>{{ $event->event_date->format('l, d M Y') }}@if($event->event_time) · {{ $event->event_time }}@endif</span>
                        <span class="truncate"><i class="fas fa-location-dot mr-1.5 text-surface-400"></i>{{ $event->location }}@if($event->hall) · {{ $event->hall }}@endif</span>
                    </div>
                </div>
            </div>

            <!-- Numbers -->
            <div class="grid shrink-0 grid-cols-4 gap-4 border-t border-surface-100 pt-4 lg:w-[22rem] lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0">
                <div>
                    <p class="text-[11px] font-medium text-surface-500">Guests</p>
                    <p class="mt-0.5 text-lg font-semibold tracking-tight text-surface-900">{{ number_format($event->guests_count) }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-surface-500">Sent</p>
                    <p class="mt-0.5 text-lg font-semibold tracking-tight text-surface-900">{{ number_format($event->invitations_sent_count) }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-surface-500">Attending</p>
                    <p class="mt-0.5 text-lg font-semibold tracking-tight text-surface-900">{{ number_format($event->rsvp_attending_count) }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-surface-500">Checked in</p>
                    <p class="mt-0.5 text-lg font-semibold tracking-tight text-surface-900">{{ number_format($event->checked_in_count) }}</p>
                </div>
            </div>
        </div>

        <!-- Progress + actions -->
        <div class="flex flex-col gap-4 border-t border-surface-100 px-5 py-3.5 lg:flex-row lg:items-center">
            <div class="grid flex-1 gap-x-6 gap-y-3 sm:grid-cols-3">
                <div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-surface-500">Invitations delivered</span>
                        <span class="font-semibold text-surface-700">{{ $delivery }}%</span>
                    </div>
                    <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-surface-100"><div class="h-full rounded-full bg-green-500" style="width: {{ $delivery }}%;"></div></div>
                </div>
                <div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-surface-500">Checked in</span>
                        <span class="font-semibold text-surface-700">{{ $checkin }}%</span>
                    </div>
                    <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-surface-100"><div class="h-full rounded-full bg-primary-500" style="width: {{ $checkin }}%;"></div></div>
                </div>
                <div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-surface-500">TZS {{ $money($paid) }} of {{ $money($pledged) }}</span>
                        <span class="font-semibold text-surface-700">{{ $collection }}%</span>
                    </div>
                    <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-surface-100"><div class="h-full rounded-full bg-gold-500" style="width: {{ $collection }}%;"></div></div>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-1">
                <a href="{{ route('events.guests.index', $event) }}" class="{{ $quickLink }}"><i class="fas fa-users text-[11px] text-surface-400"></i> Guests</a>
                <a href="{{ route('events.invitations.index', $event) }}" class="{{ $quickLink }}"><i class="fas fa-paper-plane text-[11px] text-surface-400"></i> Invites</a>
                <a href="{{ route('events.scanner.index', $event) }}" class="{{ $quickLink }}"><i class="fas fa-qrcode text-[11px] text-surface-400"></i> Scan</a>
                <a href="{{ route('events.report', $event) }}" class="{{ $quickLink }}"><i class="fas fa-chart-pie text-[11px] text-surface-400"></i> Report</a>
                <a href="{{ route('events.show', $event) }}" class="btn btn-primary btn-sm ml-1">
                    Open event <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection

@push('styles')
<style>
    .filter-pill { padding: .4rem .8rem; border-radius: .6rem; font-size: .8rem; font-weight: 500; color: #52525b; background: transparent; border: 1px solid transparent; transition: all .15s ease; white-space: nowrap; }
    .filter-pill:hover { background: #f4f4f5; }
    .filter-pill-active, .filter-pill-active:hover { background: #18181b; color: #fff; }
</style>
@endpush

@push('scripts')
<script>
    const searchInput = document.getElementById('event-search');
    const cards = Array.from(document.querySelectorAll('.event-card'));
    const noResults = document.getElementById('no-results');
    const resultCount = document.getElementById('result-count');
    const pills = document.querySelectorAll('.filter-pill');
    let activeFilter = 'all';

    function applyFilters() {
        const term = (searchInput?.value || '').trim().toLowerCase();
        let visibleCount = 0;

        cards.forEach(card => {
            const matchesSearch = !term || card.dataset.search.includes(term);
            const matchesStatus = activeFilter === 'all' || card.dataset.status === activeFilter;
            const show = matchesSearch && matchesStatus;
            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (noResults) noResults.classList.toggle('hidden', visibleCount !== 0);
        if (resultCount) resultCount.textContent = 'Showing ' + visibleCount + (visibleCount === 1 ? ' event' : ' events');
    }

    searchInput?.addEventListener('input', applyFilters);

    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            pills.forEach(p => p.classList.remove('filter-pill-active'));
            pill.classList.add('filter-pill-active');
            activeFilter = pill.dataset.filter;
            applyFilters();
        });
    });
</script>
@endpush
