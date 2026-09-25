@extends('layouts.app')

@section('title', 'Events - Card Management')

@section('content')
@php
    $totalGuestsAll = $events->sum('guests_count');
    $activeCount = $events->where('status', 'active')->count();
@endphp

<!-- Header -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display text-2xl font-bold text-surface-900">Events</h1>
        <p class="text-surface-500 mt-1">Manage your wedding &amp; event invitations</p>
    </div>
    <a href="{{ route('events.create') }}" class="btn btn-primary shrink-0">
        <i class="fas fa-plus"></i> New Event
    </a>
</div>

<!-- Search + filters -->
<div class="card-surface p-4 mb-6">
    <div class="flex flex-col md:flex-row gap-3 md:items-center">
        <div class="relative flex-1">
            <i class="fas fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-surface-400 text-sm"></i>
            <input type="text" id="event-search" placeholder="Search events by name, location or hall..."
                class="input-field pl-10" autocomplete="off">
        </div>
        <div class="flex items-center gap-2 flex-wrap" id="status-filters">
            <button type="button" data-filter="all" class="filter-pill filter-pill-active">All <span class="opacity-60">({{ $events->count() }})</span></button>
            <button type="button" data-filter="active" class="filter-pill">Active <span class="opacity-60">({{ $activeCount }})</span></button>
            <button type="button" data-filter="draft" class="filter-pill">Draft</button>
            <button type="button" data-filter="completed" class="filter-pill">Completed</button>
            <button type="button" data-filter="cancelled" class="filter-pill">Cancelled</button>
        </div>
    </div>
</div>

@if($events->isEmpty())
<div class="card-surface px-6 py-16 text-center">
    <div class="w-20 h-20 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-calendar-plus text-primary-400 text-3xl"></i>
    </div>
    <h3 class="text-lg text-surface-700 font-medium">No events created yet</h3>
    <p class="text-surface-400 mt-2">Start by creating your first event</p>
    <a href="{{ route('events.create') }}" class="btn btn-primary mt-5 inline-flex">
        <i class="fas fa-plus"></i> Create Event
    </a>
</div>
@else

<!-- Empty search result state -->
<div id="no-results" class="hidden card-surface px-6 py-14 text-center">
    <div class="w-16 h-16 bg-surface-100 rounded-full flex items-center justify-center mx-auto mb-3">
        <i class="fas fa-magnifying-glass text-surface-400"></i>
    </div>
    <h3 class="text-surface-700 font-medium">No events match your search</h3>
    <p class="text-surface-400 text-sm mt-1">Try a different name, location or filter</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="events-grid">
    @foreach($events as $event)
    @php
        $accent = $event->card_accent_color ?: '#e11d48';
        $searchBlob = strtolower($event->name.' '.$event->location.' '.$event->hall);
        $isUpcoming = $event->event_date->isFuture();
    @endphp
    <div class="event-card card-surface card-surface-hover transition overflow-hidden group"
        data-status="{{ $event->status }}" data-search="{{ $searchBlob }}">

        <!-- Cover / accent banner -->
        <div class="h-24 relative flex items-end p-4"
            style="background: linear-gradient(135deg, {{ $accent }}, {{ $accent }}99);">
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 20% 20%, #fff 1px, transparent 1px); background-size: 14px 14px;"></div>
            <span class="relative badge bg-white/90 backdrop-blur text-surface-700 shadow-soft">
                {{ ucfirst($event->status) }}
            </span>
            @if($isUpcoming)
            <span class="relative ml-auto badge bg-white/90 backdrop-blur text-surface-700 shadow-soft">
                <i class="fas fa-clock mr-1"></i>{{ $event->event_date->diffForHumans(null, true) }} left
            </span>
            @endif
        </div>

        <div class="p-6 pt-5 -mt-8 relative">
            <div class="w-12 h-12 rounded-xl bg-white shadow-soft-lg flex items-center justify-center mb-3 border border-surface-100">
                <i class="fas fa-heart" style="color: {{ $accent }};"></i>
            </div>

            <h3 class="font-display font-bold text-surface-900 text-lg leading-snug truncate">{{ $event->name }}</h3>

            <div class="space-y-1.5 text-sm text-surface-500 my-3">
                <div><i class="fas fa-calendar w-5 text-center mr-1.5"></i>{{ $event->event_date->format('d M Y') }}</div>
                <div class="truncate"><i class="fas fa-location-dot w-5 text-center mr-1.5"></i>{{ $event->location }}</div>
                @if($event->hall)
                <div class="truncate"><i class="fas fa-building w-5 text-center mr-1.5"></i>{{ $event->hall }}</div>
                @endif
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-surface-100">
                <div class="flex gap-4 text-sm">
                    <span class="text-surface-500"><i class="fas fa-users mr-1"></i> {{ $event->guests_count }}</span>
                    <span class="text-surface-500"><i class="fas fa-envelope mr-1"></i> {{ $event->invitations_count }}</span>
                </div>
                <a href="{{ route('events.show', $event) }}" class="text-primary-600 hover:text-primary-700 font-semibold text-sm">
                    Manage <i class="fas fa-arrow-right ml-1 group-hover:translate-x-0.5 transition-transform inline-block"></i>
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
