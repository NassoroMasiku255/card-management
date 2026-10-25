@extends('layouts.app')

@section('title', 'Events - NiaLike')

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

<!-- Hero header -->
<div class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-[#6d0f2a] via-[#be123c] to-[#b8932a] p-6 text-white shadow-soft-lg sm:p-8">
    <div class="absolute -left-10 -top-16 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>
    <div class="absolute -bottom-20 right-10 h-56 w-56 rounded-full bg-gold-300/20 blur-3xl"></div>

    <div class="relative flex flex-wrap items-start justify-between gap-5">
        <div>
            <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-white/60">Event workspace</span>
            <h1 class="mt-2 font-display text-3xl font-bold sm:text-4xl">Your events, all in one view.</h1>
            <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/70">
                Create and manage invitations, guests, contributions and check-in for every celebration.
            </p>
        </div>
        <a href="{{ route('events.create') }}" class="btn bg-white px-5 text-primary-700 hover:bg-primary-50">
            <i class="fas fa-plus"></i> New Event
        </a>
    </div>

    <div class="relative mt-7 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">Events</p>
            <p class="mt-1 text-2xl font-bold">{{ $events->count() }}</p>
            <p class="mt-0.5 text-[10px] text-white/55">{{ $activeCount }} active</p>
        </div>
        <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">Guests</p>
            <p class="mt-1 text-2xl font-bold">{{ number_format($totalGuestsAll) }}</p>
            <p class="mt-0.5 text-[10px] text-white/55">{{ number_format($totalAttending) }} attending</p>
        </div>
        <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">Invitations</p>
            <p class="mt-1 text-2xl font-bold">{{ number_format($totalSent) }}</p>
            <p class="mt-0.5 text-[10px] text-white/55">{{ $deliveryRate }}% delivered</p>
        </div>
        <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">Pledged</p>
            <p class="mt-1 text-2xl font-bold">TZS {{ $money($totalPledged) }}</p>
            <p class="mt-0.5 text-[10px] text-white/55">{{ $collectionRate }}% collected · {{ number_format($totalCheckedIn) }} checked in</p>
        </div>
    </div>
</div>

<!-- Search + filters -->
<div class="card-surface mb-6 p-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="relative flex-1">
            <i class="fas fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-surface-400"></i>
            <input type="text" id="event-search" placeholder="Search by event name, location or hall..."
                class="input-field pl-10" autocomplete="off">
        </div>
        <div class="flex flex-wrap items-center gap-2" id="status-filters">
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
    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-primary-50 text-primary-400">
        <i class="fas fa-calendar-plus text-3xl"></i>
    </div>
    <h3 class="mt-4 font-display text-xl font-bold text-surface-800">No events created yet</h3>
    <p class="mt-1 text-sm text-surface-400">Start by creating your first event — it only takes a minute.</p>
    <a href="{{ route('events.create') }}" class="btn btn-primary mt-5"><i class="fas fa-plus"></i> Create Event</a>
</div>
@else

<!-- Empty search result state -->
<div id="no-results" class="hidden card-surface px-6 py-14 text-center">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-surface-100 text-surface-400">
        <i class="fas fa-magnifying-glass"></i>
    </div>
    <h3 class="mt-3 font-semibold text-surface-700">No events match your search</h3>
    <p class="mt-1 text-sm text-surface-400">Try a different name, location or filter.</p>
</div>

<div class="mb-4 flex items-center justify-between">
    <p id="result-count" class="text-xs font-medium text-surface-400">Showing {{ $events->count() }} {{ Str::plural('event', $events->count()) }}</p>
    <p class="text-xs text-surface-400"><i class="fas fa-circle-info mr-1"></i> Select an event to manage it</p>
</div>

<div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3" id="events-grid">
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
    @endphp
    <div class="event-card group overflow-hidden rounded-2xl border border-surface-200 bg-white shadow-soft transition hover:border-surface-300 hover:shadow-soft-lg"
        data-status="{{ $event->status }}" data-search="{{ $searchBlob }}">

        <!-- Cover -->
        <div class="relative h-36 overflow-hidden"
            style="background: {{ $cover ? "linear-gradient(180deg, rgba(18,18,20,.1), rgba(18,18,20,.8)), url('{$cover}') center/cover" : "linear-gradient(135deg, {$accent}, #18181b)" }};">
            <div class="absolute inset-0 opacity-15" style="background-image: radial-gradient(circle at 20% 20%, #fff 1px, transparent 1px); background-size: 16px 16px;"></div>
            <div class="absolute left-4 top-4 flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-surface-700 backdrop-blur">{{ ucfirst($event->status) }}</span>
                @if($isUpcoming)
                <span class="rounded-full border border-white/20 bg-black/25 px-2.5 py-1 text-[10px] font-semibold text-white backdrop-blur">
                    <i class="fas fa-clock mr-1"></i>{{ $event->event_date->diffForHumans(null, true) }} left
                </span>
                @endif
            </div>
            @if(auth()->user()->isAdmin() && $event->user)
            <span class="absolute right-4 top-4 max-w-[45%] truncate rounded-full border border-white/20 bg-black/25 px-2.5 py-1 text-[10px] font-medium text-white/85 backdrop-blur">
                <i class="fas fa-user mr-1"></i>{{ $event->user->name }}
            </span>
            @endif
            <div class="absolute bottom-4 left-4 flex h-12 w-12 items-center justify-center rounded-xl border border-white/20 bg-white/15 text-white backdrop-blur">
                <i class="fas fa-heart text-lg"></i>
            </div>
        </div>

        <div class="p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ route('events.show', $event) }}" class="block truncate font-display text-lg font-bold text-surface-900 transition hover:text-primary-600">{{ $event->name }}</a>
                    <p class="mt-1 flex items-center gap-1.5 text-xs text-surface-500"><i class="fas fa-calendar text-surface-400"></i>{{ $event->event_date->format('l, d M Y') }}@if($event->event_time) · {{ $event->event_time }}@endif</p>
                    <p class="mt-1 flex items-center gap-1.5 truncate text-xs text-surface-500"><i class="fas fa-location-dot text-surface-400"></i>{{ $event->location }}@if($event->hall) · {{ $event->hall }}@endif</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-lg bg-surface-50 py-2">
                    <p class="text-sm font-bold text-surface-900">{{ number_format($event->guests_count) }}</p>
                    <p class="text-[9px] uppercase tracking-wider text-surface-400">Guests</p>
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
                        <span><i class="fas fa-paper-plane mr-1"></i>Invitations delivered</span>
                        <span class="font-semibold text-surface-600">{{ $delivery }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-surface-100"><div class="h-full rounded-full bg-green-500" style="width: {{ $delivery }}%;"></div></div>
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between text-[10px] text-surface-400">
                        <span><i class="fas fa-coins mr-1"></i>TZS {{ $money($paid) }} of {{ $money($pledged) }}</span>
                        <span class="font-semibold text-surface-600">{{ $collection }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-surface-100"><div class="h-full rounded-full bg-gold-500" style="width: {{ $collection }}%;"></div></div>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-surface-100 pt-3">
                <div class="flex items-center gap-1">
                    <a href="{{ route('events.guests.index', $event) }}" class="icon-action h-8 w-8 text-surface-400 hover:text-primary-600" title="Guests"><i class="fas fa-users text-xs"></i></a>
                    <a href="{{ route('events.invitations.index', $event) }}" class="icon-action h-8 w-8 text-surface-400 hover:text-primary-600" title="Invitations"><i class="fas fa-paper-plane text-xs"></i></a>
                    <a href="{{ route('events.scanner.index', $event) }}" class="icon-action h-8 w-8 text-surface-400 hover:text-primary-600" title="Scanner"><i class="fas fa-qrcode text-xs"></i></a>
                    <a href="{{ route('events.report', $event) }}" class="icon-action h-8 w-8 text-surface-400 hover:text-primary-600" title="Report"><i class="fas fa-chart-pie text-xs"></i></a>
                </div>
                <a href="{{ route('events.show', $event) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700">
                    Manage <i class="fas fa-arrow-right text-[10px] transition-transform group-hover:translate-x-0.5"></i>
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
    .filter-pill { padding: .45rem .9rem; border-radius: 9999px; font-size: .8rem; font-weight: 600; color: #52525b; background: #f4f4f5; border: 1px solid transparent; transition: all .15s ease; white-space: nowrap; }
    .filter-pill:hover { background: #e4e4e7; }
    .filter-pill-active { background: #ffe4e6; color: #be123c; border-color: #fecdd5; }
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
