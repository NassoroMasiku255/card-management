@extends('layouts.app')

@section('title', 'Dashboard - Card Management')

@section('content')
@php
    $hour = now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $totalInvitationsSent = $events->sum('invitations_count');
    $upcoming = $events->where('event_date', '>=', now()->startOfDay())->sortBy('event_date')->first();
    $recentEvents = $events->take(6);
@endphp

<div class="mb-8">
    <h1 class="font-display text-2xl font-bold text-surface-900">{{ $greeting }}, {{ explode(' ', Auth::user()->name)[0] }} <span class="inline-block">👋</span></h1>
    <p class="text-surface-500 mt-1">Here's what's happening with your events today.</p>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="card-surface p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-surface-500">Total Events</p>
                <p class="text-3xl font-bold text-surface-900 mt-1">{{ $totalEvents }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-calendar-days text-blue-600"></i>
            </div>
        </div>
    </div>

    <div class="card-surface p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-surface-500">Active Events</p>
                <p class="text-3xl font-bold text-surface-900 mt-1">{{ $activeEvents }}</p>
            </div>
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-bolt text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="card-surface p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-surface-500">Total Guests</p>
                <p class="text-3xl font-bold text-surface-900 mt-1">{{ $totalGuests }}</p>
            </div>
            <div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-users text-primary-600"></i>
            </div>
        </div>
    </div>

    <div class="card-surface p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-surface-500">Invitations Sent</p>
                <p class="text-3xl font-bold text-surface-900 mt-1">{{ $totalInvitationsSent }}</p>
            </div>
            <div class="w-12 h-12 bg-gold-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-paper-plane text-gold-600"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-stretch">

    <!-- Spotlight: next upcoming event -->
    <div class="lg:col-span-2">
        @if($upcoming)
        @php $accent = $upcoming->card_accent_color ?: '#e11d48'; @endphp
        <div class="card-surface h-full relative overflow-hidden p-7"
            style="background: linear-gradient(135deg, {{ $accent }}14, transparent 60%);">
            <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full opacity-30" style="background: {{ $accent }};filter: blur(50px);"></div>
            <div class="relative">
                <span class="badge bg-white text-surface-600 shadow-soft mb-4 inline-flex items-center gap-1.5">
                    <i class="fas fa-star text-gold-500"></i> Next Up
                </span>
                <h2 class="font-display text-2xl font-bold text-surface-900">{{ $upcoming->name }}</h2>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 mt-3 text-sm text-surface-600">
                    <span><i class="fas fa-calendar mr-1.5"></i>{{ $upcoming->event_date->format('l, d M Y') }}</span>
                    <span><i class="fas fa-location-dot mr-1.5"></i>{{ $upcoming->location }}</span>
                    <span><i class="fas fa-users mr-1.5"></i>{{ $upcoming->guests_count }} guests</span>
                </div>

                <div class="flex items-center gap-3 mt-6">
                    <div class="px-4 py-2 rounded-xl bg-white shadow-soft">
                        <span class="text-2xl font-bold" style="color: {{ $accent }};">{{ now()->startOfDay()->diffInDays($upcoming->event_date->startOfDay()) }}</span>
                        <span class="text-xs text-surface-500 ml-1">days to go</span>
                    </div>
                    <a href="{{ route('events.show', $upcoming) }}" class="btn btn-primary">
                        Manage Event <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        @else
        <div class="card-surface h-full flex flex-col items-center justify-center text-center p-10">
            <div class="w-16 h-16 bg-primary-50 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-calendar-check text-primary-400 text-2xl"></i>
            </div>
            <h3 class="text-surface-700 font-medium">No upcoming events</h3>
            <p class="text-surface-400 text-sm mt-1 mb-4">All caught up — plan your next celebration</p>
            <a href="{{ route('events.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Event
            </a>
        </div>
        @endif
    </div>

    <!-- Quick actions -->
    <div class="card-surface p-6">
        <h3 class="text-sm font-semibold text-surface-800 mb-4">Quick Actions</h3>
        <div class="space-y-2.5">
            <a href="{{ route('events.create') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-surface-50 transition group">
                <div class="w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center shrink-0 group-hover:bg-primary-100 transition">
                    <i class="fas fa-plus text-primary-600 text-sm"></i>
                </div>
                <span class="text-sm font-medium text-surface-700">Create New Event</span>
            </a>
            <a href="{{ route('events.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-surface-50 transition group">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0 group-hover:bg-blue-100 transition">
                    <i class="fas fa-calendar-days text-blue-600 text-sm"></i>
                </div>
                <span class="text-sm font-medium text-surface-700">View All Events</span>
            </a>
            @if($upcoming)
            <a href="{{ route('events.guests.import', $upcoming) }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-surface-50 transition group">
                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0 group-hover:bg-green-100 transition">
                    <i class="fas fa-file-excel text-green-600 text-sm"></i>
                </div>
                <span class="text-sm font-medium text-surface-700">Import Guests</span>
            </a>
            <a href="{{ route('events.scanner.index', $upcoming) }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-surface-50 transition group">
                <div class="w-10 h-10 rounded-lg bg-gold-50 flex items-center justify-center shrink-0 group-hover:bg-gold-100 transition">
                    <i class="fas fa-qrcode text-gold-600 text-sm"></i>
                </div>
                <span class="text-sm font-medium text-surface-700">Open Scanner</span>
            </a>
            @endif
        </div>
    </div>
</div>

<!-- Recent events -->
<div class="mb-4 flex items-center justify-between">
    <h2 class="text-base font-semibold text-surface-900">Your Events</h2>
    @if($events->count() > 6)
    <a href="{{ route('events.index') }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">View All ({{ $events->count() }})</a>
    @endif
</div>

@if($events->isEmpty())
<div class="card-surface px-6 py-14 text-center">
    <div class="w-16 h-16 bg-surface-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-calendar-plus text-surface-400 text-xl"></i>
    </div>
    <h3 class="text-surface-700 font-medium">No events yet</h3>
    <p class="text-surface-400 text-sm mt-1">Create your first event to get started</p>
    <a href="{{ route('events.create') }}" class="inline-flex items-center gap-1 mt-4 text-primary-600 hover:text-primary-700 font-medium text-sm">
        <i class="fas fa-plus"></i> Create Event
    </a>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    @foreach($recentEvents as $event)
    @php $accent = $event->card_accent_color ?: '#e11d48'; @endphp
    <a href="{{ route('events.show', $event) }}" class="card-surface card-surface-hover transition overflow-hidden group">
        <div class="h-2" style="background: {{ $accent }};"></div>
        <div class="p-5">
            <div class="flex items-center justify-between gap-2 mb-2">
                <h3 class="font-semibold text-surface-900 truncate">{{ $event->name }}</h3>
                <span class="badge shrink-0 {{ $event->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-surface-100 text-surface-600' }}">
                    {{ ucfirst($event->status) }}
                </span>
            </div>
            <div class="text-sm text-surface-500 space-y-1">
                <div><i class="fas fa-calendar mr-1.5"></i>{{ $event->event_date->format('d M Y') }}</div>
                <div class="truncate"><i class="fas fa-location-dot mr-1.5"></i>{{ $event->location }}</div>
            </div>
            <div class="flex items-center justify-between mt-4 pt-3 border-t border-surface-100 text-sm">
                <span class="text-surface-500"><i class="fas fa-users mr-1"></i>{{ $event->guests_count }}</span>
                <span class="text-primary-600 font-semibold group-hover:translate-x-0.5 transition-transform inline-block">
                    Manage <i class="fas fa-arrow-right ml-1"></i>
                </span>
            </div>
        </div>
    </a>
    @endforeach
</div>
@endif
@endsection
