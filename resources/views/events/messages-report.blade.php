@extends('layouts.app')

@section('title', 'Message Tracking - ' . $event->name)

@section('content')
@php
    $accent = $event->card_accent_color ?: '#e11d48';
    $total = max((int) $stats['totalMessages'], 1);
    $deliveredOnly = max(0, (int) $stats['delivered'] - (int) $stats['read']);
    $readPct = (int) round(($stats['read'] / $total) * 100);
    $deliveredPct = (int) round(($deliveredOnly / $total) * 100);
    $failedPct = (int) round(($stats['failed'] / $total) * 100);
    $pendingPct = max(0, 100 - $readPct - $deliveredPct - $failedPct);
    $replyPct = min(100, (int) $stats['replyRate']);
    $deliveredRate = (int) $stats['deliveryRate'];
    $readRate = (int) $stats['readRate'];
    $failureRate = (int) $stats['failureRate'];

    $statusMeta = [
        'invitation' => ['icon' => 'fa-envelope-open-text', 'tone' => 'bg-primary-50 text-primary-600'],
        'reminder' => ['icon' => 'fa-bell', 'tone' => 'bg-amber-50 text-amber-600'],
        'payment_thank_you' => ['icon' => 'fa-hand-holding-heart', 'tone' => 'bg-green-50 text-green-600'],
    ];
@endphp

<!-- Header -->
<div class="mb-5 print:hidden">
    <div class="relative overflow-hidden rounded-2xl p-5 text-white shadow-soft-lg sm:p-7"
        style="background: linear-gradient(125deg, #101014 0%, #1f2937 45%, {{ $accent }} 135%);">
        <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-28 left-1/3 h-64 w-64 rounded-full opacity-30 blur-3xl" style="background: {{ $accent }};"></div>

        <div class="relative">
            <a href="{{ route('events.show', $event) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-white/60 transition hover:text-white">
                <i class="fas fa-arrow-left text-[10px]"></i> Back to {{ $event->name }}
            </a>

            <div class="mt-4 flex flex-wrap items-start justify-between gap-5">
                <div class="flex min-w-0 items-start gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-white backdrop-blur">
                        <i class="fas fa-tower-broadcast text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gold-300">Communication intelligence</p>
                            <span class="rounded-full border border-white/15 bg-white/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-white/70">Live tracking</span>
                        </div>
                        <h1 class="mt-1 font-display text-2xl font-bold text-white sm:text-3xl">Message Tracking Report</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-white/55">
                            <span><i class="fas fa-calendar-days mr-1.5 text-white/35"></i>{{ $event->event_date->format('l, d M Y') }}</span>
                            <span><i class="fas fa-location-dot mr-1.5 text-white/35"></i>{{ $event->location }}</span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-green-300/20 bg-green-400/10 px-2.5 py-1 font-semibold text-green-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-300"></span> WhatsApp connected
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <a href="{{ route('events.report', $event) }}" class="btn flex-1 border border-white/20 bg-white/10 text-white hover:bg-white/20 sm:flex-none">
                        <i class="fas fa-chart-pie"></i> Payment report
                    </a>
                    <a href="{{ route('events.invitations.index', $event) }}" class="btn flex-1 border border-white/20 bg-white/10 text-white hover:bg-white/20 sm:flex-none">
                        <i class="fas fa-paper-plane"></i> Invitations
                    </a>
                    <button type="button" onclick="window.print()" class="btn flex-1 bg-white text-surface-900 hover:bg-surface-100 sm:flex-none">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-5 hidden print:block">
    <h1 class="text-xl font-bold text-surface-900">{{ $event->name }} - Message Tracking Report</h1>
    <p class="mt-1 text-sm text-surface-500">{{ $event->event_date->format('d M Y') }} &middot; {{ $event->location }}</p>
</div>

<!-- KPI cards -->
<div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-6">
    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-surface-400">Messages sent</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-surface-900 sm:text-3xl">{{ number_format($stats['totalMessages']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ number_format($stats['guestsReached']) }} guests reached</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600"><i class="fas fa-paper-plane text-xs"></i></span>
        </div>
        <div class="mt-3 flex items-center gap-1.5">
            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[9px] font-bold text-amber-600">{{ number_format($stats['pending']) }} pending</span>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-green-600/70">Delivered</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-green-600 sm:text-3xl">{{ number_format($stats['delivered']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $deliveredRate }}% delivery rate</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-600"><i class="fas fa-circle-check text-xs"></i></span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-green-50">
            <div class="h-full rounded-full bg-green-500" style="width: {{ $deliveredRate }}%;"></div>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-teal-600/70">Read</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-teal-600 sm:text-3xl">{{ number_format($stats['read']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $readRate }}% of delivered</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600"><i class="fas fa-envelope-open text-xs"></i></span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-teal-50">
            <div class="h-full rounded-full bg-teal-500" style="width: {{ $readRate }}%;"></div>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-red-500/80">Failed</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-red-500 sm:text-3xl">{{ number_format($stats['failed']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $failureRate }}% of all messages</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-500"><i class="fas fa-triangle-exclamation text-xs"></i></span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-red-50">
            <div class="h-full rounded-full bg-red-500" style="width: {{ $failureRate }}%;"></div>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-purple-600/70">Replies</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-purple-600 sm:text-3xl">{{ number_format($stats['replyCount']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ number_format($stats['uniqueRepliers']) }} unique guests</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600"><i class="fas fa-reply text-xs"></i></span>
        </div>
        <div class="mt-3 flex items-center gap-1.5">
            <span class="rounded-full bg-purple-50 px-2 py-0.5 text-[9px] font-bold text-purple-600">{{ $replyPct }}% reply rate</span>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-surface-400">Guests reached</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-surface-900 sm:text-3xl">{{ number_format($stats['guestsReached']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">of {{ number_format($event->guests->count()) }} event guests</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-surface-100 text-surface-600"><i class="fas fa-users text-xs"></i></span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-surface-100">
            <div class="h-full rounded-full bg-gradient-to-r from-gold-500 to-amber-300" style="width: {{ $event->guests->count() > 0 ? min(100, (int) round(($stats['guestsReached'] / $event->guests->count()) * 100)) : 0 }}%;"></div>
        </div>
    </div>
</div>

<!-- Channels -->
<div class="mb-6">
    <div class="mb-3 flex items-end justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-surface-900">Messaging channels</h2>
            <p class="mt-0.5 text-xs text-surface-400">Delivery performance by channel</p>
        </div>
        <span class="hidden text-[10px] font-semibold uppercase tracking-wider text-surface-400 sm:block">WhatsApp vs normal messages</span>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <!-- WhatsApp channel -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 via-green-700 to-teal-800 p-5 text-white shadow-soft-lg sm:p-6">
            <div class="pointer-events-none absolute -right-10 -top-14 h-48 w-48 rounded-full bg-white/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-8 h-40 w-40 rounded-full bg-lime-300/20 blur-3xl"></div>
            <div class="relative">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/20 bg-white/15 backdrop-blur">
                            <i class="fab fa-whatsapp text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold">{{ $channels['whatsapp']['label'] }}</p>
                            <p class="text-[10px] font-medium uppercase tracking-wider text-white/55">Primary channel</p>
                        </div>
                    </div>
                    <span class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider text-white/75">Active</span>
                </div>

                <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/50">Messages sent</p>
                        <p class="mt-1 font-display text-4xl font-bold leading-none">{{ number_format($channels['whatsapp']['total']) }}</p>
                        <p class="mt-1 text-[11px] text-white/55">{{ number_format($channels['whatsapp']['delivered']) }} delivered &middot; {{ number_format($channels['whatsapp']['read']) }} read</p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-center backdrop-blur">
                        <p class="font-display text-2xl font-bold">{{ $deliveredRate }}%</p>
                        <p class="text-[9px] font-bold uppercase tracking-wider text-white/55">delivery rate</p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-3 gap-2">
                    <div class="rounded-xl border border-white/10 bg-white/10 px-3 py-2.5 backdrop-blur">
                        <p class="flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wider text-white/50"><span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>Delivered</p>
                        <p class="mt-1 text-lg font-bold">{{ number_format($channels['whatsapp']['delivered']) }}</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/10 px-3 py-2.5 backdrop-blur">
                        <p class="flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wider text-white/50"><span class="h-1.5 w-1.5 rounded-full bg-teal-300"></span>Read</p>
                        <p class="mt-1 text-lg font-bold">{{ number_format($channels['whatsapp']['read']) }}</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/10 px-3 py-2.5 backdrop-blur">
                        <p class="flex items-center gap-1.5 text-[9px] font-bold uppercase tracking-wider text-white/50"><span class="h-1.5 w-1.5 rounded-full bg-red-300"></span>Failed</p>
                        <p class="mt-1 text-lg font-bold">{{ number_format($channels['whatsapp']['failed']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Normal messages channel -->
        <div class="relative overflow-hidden rounded-2xl border border-surface-200 bg-white p-5 shadow-soft sm:p-6">
            <div class="pointer-events-none absolute -right-12 -top-16 h-48 w-48 rounded-full bg-surface-100 blur-3xl"></div>
            <div class="relative">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-surface-100 text-surface-500">
                            <i class="fas fa-comment-dots text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-surface-800">{{ $channels['normal']['label'] }}</p>
                            <p class="text-[10px] font-medium uppercase tracking-wider text-surface-400">SMS &amp; in-app</p>
                        </div>
                    </div>
                    <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider text-amber-600">Coming soon</span>
                </div>

                <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-surface-400">Messages sent</p>
                        <p class="mt-1 font-display text-4xl font-bold leading-none text-surface-300">{{ number_format($channels['normal']['total']) }}</p>
                        <p class="mt-1 text-[11px] text-surface-400">Normal messages are not enabled yet</p>
                    </div>
                    <div class="rounded-2xl bg-surface-50 px-4 py-3 text-center">
                        <p class="font-display text-2xl font-bold text-surface-300">—</p>
                        <p class="text-[9px] font-bold uppercase tracking-wider text-surface-400">delivery rate</p>
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-dashed border-surface-200 bg-surface-50/70 px-4 py-3">
                    <div class="flex items-start gap-2.5">
                        <i class="fas fa-wand-magic-sparkles mt-0.5 text-[11px] text-gold-500"></i>
                        <p class="text-[11px] leading-relaxed text-surface-500">Normal messaging is being prepared. When it launches, SMS and in-app delivery will appear here alongside WhatsApp with the same tracking detail.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Funnel + health -->
<div class="mb-6 grid grid-cols-1 gap-5 lg:grid-cols-5">
    <!-- Funnel -->
    <div class="card-surface overflow-hidden lg:col-span-3">
        <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-rose-600 text-white shadow-soft">
                    <i class="fas fa-filter-circle-dollar text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Message journey</h2>
                    <p class="text-[11px] text-surface-400">From sent to replied</p>
                </div>
            </div>
            <span class="badge bg-primary-50 text-primary-600">{{ number_format($stats['totalMessages']) }} total</span>
        </div>

        <div class="space-y-4 p-5">
            @php
                $funnel = [
                    ['label' => 'Sent', 'value' => $stats['totalMessages'], 'pct' => $stats['totalMessages'] > 0 ? 100 : 0, 'color' => 'from-primary-500 to-rose-500', 'icon' => 'fa-paper-plane', 'hint' => 'Attempts recorded'],
                    ['label' => 'Delivered', 'value' => $stats['delivered'], 'pct' => $deliveredRate, 'color' => 'from-green-500 to-emerald-500', 'icon' => 'fa-circle-check', 'hint' => $deliveredRate.'% of messages'],
                    ['label' => 'Read', 'value' => $stats['read'], 'pct' => $total > 0 ? (int) round(($stats['read'] / $total) * 100) : 0, 'color' => 'from-teal-500 to-cyan-500', 'icon' => 'fa-envelope-open', 'hint' => $readRate.'% of delivered'],
                    ['label' => 'Replied', 'value' => $stats['replyCount'], 'pct' => $replyPct, 'color' => 'from-purple-500 to-fuchsia-500', 'icon' => 'fa-reply', 'hint' => $stats['uniqueRepliers'].' unique guests'],
                ];
            @endphp

            @foreach($funnel as $step)
            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-surface-50 text-surface-500"><i class="fas {{ $step['icon'] }} text-[10px]"></i></span>
                        <div>
                            <p class="text-xs font-semibold text-surface-700">{{ $step['label'] }}</p>
                            <p class="text-[10px] text-surface-400">{{ $step['hint'] }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold text-surface-900">{{ number_format($step['value']) }}</span>
                        <span class="ml-1 text-[10px] font-semibold text-surface-400">{{ $step['pct'] }}%</span>
                    </div>
                </div>
                <div class="h-2.5 overflow-hidden rounded-full bg-surface-100">
                    <div class="h-full rounded-full bg-gradient-to-r {{ $step['color'] }} transition-all duration-500" style="width: {{ $step['pct'] }}%;"></div>
                </div>
            </div>
            @endforeach

            <div class="mt-5 rounded-xl border border-surface-100 bg-surface-50/70 p-3.5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-[11px] text-surface-500"><i class="fas fa-circle-info mr-1 text-surface-400"></i>Read receipts are only available for WhatsApp messages.</span>
                    <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-semibold text-surface-500">{{ $readRate }}% read rate</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery health -->
    <div class="card-surface overflow-hidden lg:col-span-2">
        <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-soft">
                    <i class="fas fa-heart-pulse text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Delivery health</h2>
                    <p class="text-[11px] text-surface-400">Current status mix</p>
                </div>
            </div>
            <span class="badge {{ $failureRate > 0 ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' }}">{{ $failureRate > 0 ? $failureRate.'% failed' : 'Healthy' }}</span>
        </div>

        <div class="flex flex-col items-center p-5">
            <div class="relative h-40 w-40 rounded-full"
                @if($stats['totalMessages'] > 0)
                style="background: conic-gradient(#14b8a6 0 {{ $readPct }}%, #22c55e {{ $readPct }}% {{ $readPct + $deliveredPct }}%, #f59e0b {{ $readPct + $deliveredPct }}% {{ $readPct + $deliveredPct + $pendingPct }}%, #ef4444 {{ $readPct + $deliveredPct + $pendingPct }}% 100%);"
                @else
                style="background: #f4f4f5;"
                @endif>
                <div class="absolute inset-[14px] flex flex-col items-center justify-center rounded-full bg-white shadow-inner">
                    <span class="font-display text-4xl font-bold tracking-tight text-surface-900">{{ $deliveredRate }}%</span>
                    <span class="text-[9px] font-bold uppercase tracking-[0.14em] text-surface-400">delivered</span>
                </div>
            </div>

            <div class="mt-6 grid w-full grid-cols-2 gap-2.5">
                @foreach($statusStats->where('count', '>', 0) as $status)
                <div class="flex items-center justify-between rounded-xl border border-surface-100 bg-surface-50/60 px-3 py-2.5">
                    <span class="flex items-center gap-2 text-[11px] font-medium text-surface-600">
                        <span class="h-2 w-2 rounded-full {{ $status['tone'] }}"></span>{{ $status['label'] }}
                    </span>
                    <span class="text-xs font-bold text-surface-800">{{ number_format($status['count']) }}</span>
                </div>
                @endforeach
                @if($statusStats->where('count', '>', 0)->isEmpty())
                <div class="col-span-2 rounded-xl border border-dashed border-surface-200 p-4 text-center text-xs text-surface-400">No status updates yet.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Types + activity -->
<div class="mb-6 grid grid-cols-1 gap-5 lg:grid-cols-2">
    <!-- Message types -->
    <div class="card-surface overflow-hidden">
        <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-gold-400 to-amber-600 text-white shadow-soft">
                    <i class="fas fa-layer-group text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Message types</h2>
                    <p class="text-[11px] text-surface-400">Performance by message purpose</p>
                </div>
            </div>
            <span class="badge bg-surface-100 text-surface-500">{{ $typeStats->count() }} types</span>
        </div>

        <div class="space-y-3 p-5">
            @forelse($typeStats as $type)
            @php
                $meta = $statusMeta[$type['type']] ?? ['icon' => 'fa-comment', 'tone' => 'bg-surface-100 text-surface-500'];
            @endphp
            <div class="rounded-xl border border-surface-100 bg-surface-50/50 p-3.5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $meta['tone'] }}"><i class="fas {{ $meta['icon'] }} text-[10px]"></i></span>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-surface-800">{{ $type['label'] }}</p>
                            <p class="text-[10px] text-surface-400">{{ number_format($type['delivered']) }} delivered &middot; {{ number_format($type['read']) }} read</p>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-sm font-bold text-surface-900">{{ number_format($type['count']) }}</p>
                        <p class="text-[9px] font-semibold uppercase tracking-wider text-surface-400">{{ $type['delivery_rate'] }}% delivered</p>
                    </div>
                </div>
                <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-surface-100">
                    <div class="h-full rounded-full {{ $type['failed'] > 0 ? 'bg-gradient-to-r from-green-500 to-amber-400' : 'bg-gradient-to-r from-green-500 to-emerald-400' }}" style="width: {{ $type['delivery_rate'] }}%;"></div>
                </div>
            </div>
            @empty
            <div class="rounded-xl border border-dashed border-surface-200 py-10 text-center">
                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-surface-100 text-surface-400"><i class="fas fa-comment-slash"></i></div>
                <p class="mt-2 text-sm font-medium text-surface-600">No messages sent yet</p>
                <p class="mt-0.5 text-xs text-surface-400">Invitations, reminders and payment thank-yous will appear here.</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- 7 day activity -->
    <div class="card-surface overflow-hidden">
        <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-soft">
                    <i class="fas fa-chart-column text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Last 7 days</h2>
                    <p class="text-[11px] text-surface-400">Daily messaging activity</p>
                </div>
            </div>
            <span class="text-[11px] font-semibold text-surface-400">{{ number_format($dailyActivity->sum('count')) }} messages</span>
        </div>

        <div class="p-5">
            <div class="flex h-48 items-end justify-between gap-2 sm:gap-4">
                @foreach($dailyActivity as $day)
                <div class="group flex h-full flex-1 flex-col items-center justify-end gap-2">
                    <div class="relative flex w-full max-w-10 flex-1 items-end justify-center">
                        <div class="w-full rounded-t-lg bg-gradient-to-t from-primary-600 to-rose-400 transition-all duration-300 group-hover:from-primary-700 group-hover:to-rose-500" style="height: {{ max(4, (int) round(($day['count'] / $dailyMax) * 100)) }}%;" title="{{ $day['count'] }} messages">
                            <div class="absolute -top-6 left-1/2 hidden -translate-x-1/2 rounded bg-surface-900 px-1.5 py-0.5 text-[9px] font-bold text-white group-hover:block">{{ $day['count'] }}</div>
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="text-[10px] font-bold text-surface-500">{{ $day['label'] }}</p>
                        <p class="text-[9px] text-surface-400">{{ $day['count'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4 flex items-center justify-center gap-4 rounded-xl bg-surface-50/70 px-3 py-2.5">
                <span class="flex items-center gap-1.5 text-[10px] font-medium text-surface-500"><span class="h-2 w-2 rounded-full bg-primary-500"></span>Messages sent</span>
                <span class="flex items-center gap-1.5 text-[10px] font-medium text-surface-500"><span class="h-2 w-2 rounded-full bg-green-500"></span>{{ number_format($dailyActivity->sum('delivered')) }} delivered in period</span>
            </div>
        </div>
    </div>
</div>

<!-- Replies -->
<div class="card-surface mb-6 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-100 px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-fuchsia-600 text-white shadow-soft">
                <i class="fas fa-reply-all text-sm"></i>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Guest replies</h2>
                <p class="text-[11px] text-surface-400">Every incoming reply, matched to its guest</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-full bg-purple-50 px-3 py-1 text-[10px] font-bold text-purple-600">{{ number_format($stats['replyCount']) }} replies</span>
            <span class="rounded-full bg-surface-100 px-3 py-1 text-[10px] font-bold text-surface-500">{{ number_format($stats['uniqueRepliers']) }} guests</span>
        </div>
    </div>

    @if($replies->isEmpty())
    <div class="px-6 py-14 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-purple-50 text-purple-400">
            <i class="fas fa-comment-dots text-lg"></i>
        </div>
        <p class="mt-3 text-sm font-medium text-surface-600">No replies yet</p>
        <p class="mt-1 text-xs text-surface-400">When guests reply on WhatsApp, their exact message and RSVP outcome will appear here.</p>
    </div>
    @else
    <div class="divide-y divide-surface-100">
        @foreach($recentReplies as $reply)
        @php
            $replyGuest = $reply->guest;
            $rsvp = $replyGuest?->invitation?->rsvp_status;
        @endphp
        <div class="flex flex-wrap items-start gap-3.5 px-5 py-4 transition hover:bg-purple-50/20 sm:flex-nowrap">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-100 to-fuchsia-100 text-xs font-bold text-purple-700">
                {{ strtoupper(substr($replyGuest?->full_name ?? '?', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-bold text-surface-800">{{ $replyGuest?->full_name ?? 'Unknown guest' }}</p>
                    <span class="badge bg-surface-100 text-surface-500">{{ $reply->reply_kind ?? 'Reply' }}</span>
                    @if($rsvp === 'attending')
                    <span class="badge bg-green-100 text-green-700">RSVP · Nitahudhuria</span>
                    @elseif($rsvp === 'not_attending')
                    <span class="badge bg-red-100 text-red-600">RSVP · Sitahudhuria</span>
                    @elseif($rsvp === 'pending')
                    <span class="badge bg-amber-100 text-amber-700">RSVP pending</span>
                    @endif
                </div>
                <div class="mt-2 rounded-xl border border-surface-100 bg-surface-50/70 px-3.5 py-2.5">
                    <p class="text-xs leading-relaxed text-surface-700">“{{ $reply->reply_text }}”</p>
                </div>
                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-surface-400">
                    <span><i class="fab fa-whatsapp mr-1 text-green-500"></i>{{ $reply->source_phone ?: $replyGuest?->phone_number }}</span>
                    <span><i class="fas fa-clock mr-1"></i>{{ $reply->created_at->format('d M Y, H:i') }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @if($replies->count() > $recentReplies->count())
    <div class="border-t border-surface-100 bg-surface-50/50 px-5 py-3 text-center text-[11px] text-surface-400">
        Showing the latest {{ $recentReplies->count() }} of {{ number_format($replies->count()) }} replies.
    </div>
    @endif
    @endif
</div>

<!-- Outbound messages -->
<div class="card-surface overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-100 px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 text-green-600">
                <i class="fas fa-paper-plane text-sm"></i>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Outbound message log</h2>
                <p class="text-[11px] text-surface-400">Latest delivery events for this event</p>
            </div>
        </div>
        <span class="rounded-full bg-surface-100 px-3 py-1 text-[10px] font-bold text-surface-500">{{ number_format($stats['totalMessages']) }} total</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-50/80">
                <tr class="text-left text-[10px] font-bold uppercase tracking-[0.1em] text-surface-400">
                    <th class="px-5 py-3">Recipient</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3">Template</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Latest update</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($recentMessages as $message)
                <tr class="transition hover:bg-surface-50/70">
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-surface-800">{{ $message->guest?->full_name ?? 'Unknown guest' }}</div>
                        <div class="mt-0.5 text-[11px] text-surface-400">{{ $message->to_phone }}</div>
                    </td>
                    <td class="px-5 py-3.5"><span class="badge bg-surface-100 text-surface-600">{{ \Illuminate\Support\Str::headline($message->message_type) }}</span></td>
                    <td class="px-5 py-3.5 text-xs text-surface-500">{{ $message->template_name ?: '—' }}</td>
                    <td class="px-5 py-3.5"><span class="badge {{ $message->status_badge_class }}">{{ $message->status_label }}</span></td>
                    <td class="px-5 py-3.5 text-xs text-surface-500">{{ ($message->status_updated_at ?? $message->created_at)?->format('d M Y, H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-14 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-surface-100 text-surface-400"><i class="fas fa-inbox"></i></div>
                        <p class="mt-2 text-sm font-medium text-surface-600">No outbound messages yet</p>
                        <p class="mt-1 text-xs text-surface-400">Send invitations to start tracking delivery here.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($messages->count() > $recentMessages->count())
    <div class="border-t border-surface-100 bg-surface-50/50 px-5 py-3 text-center text-[11px] text-surface-400">
        Showing the latest {{ $recentMessages->count() }} of {{ number_format($messages->count()) }} messages.
    </div>
    @endif
</div>

<style>
    @media print {
        body { background: #fff !important; }
        .card-surface { box-shadow: none !important; border: 1px solid #e5e7eb !important; break-inside: avoid; }
        table { font-size: 10px !important; }
        th, td { padding: 5px 8px !important; }
        .rounded-2xl, .rounded-xl { border-radius: 4px !important; }
    }
</style>
@endsection
