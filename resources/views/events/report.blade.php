@extends('layouts.app')

@section('title', 'Payment Report - ' . $event->name)

@section('content')
@php
    $totalGuests = $guests->count();
    $statusTotal = max(array_sum($stats['statusCounts']), 1);
    $accent = $event->card_accent_color ?: '#e11d48';
    $statusMeta = [
        'paid' => ['label' => 'Paid', 'color' => 'bg-green-500', 'soft' => 'bg-green-50 text-green-700'],
        'partial' => ['label' => 'Partial', 'color' => 'bg-amber-500', 'soft' => 'bg-amber-50 text-amber-700'],
        'unpaid' => ['label' => 'Unpaid', 'color' => 'bg-surface-400', 'soft' => 'bg-surface-100 text-surface-600'],
        'overpaid' => ['label' => 'Overpaid', 'color' => 'bg-blue-500', 'soft' => 'bg-blue-50 text-blue-700'],
    ];
    $collectionRate = (int) $stats['collectionRate'];
@endphp

<div class="mb-5 print:hidden">
    <div class="relative overflow-hidden rounded-2xl border border-surface-200 bg-white p-5 shadow-soft sm:p-6">
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full opacity-[0.12] blur-3xl" style="background: {{ $accent }};"></div>
        <div class="pointer-events-none absolute -bottom-24 right-1/3 h-40 w-40 rounded-full bg-gold-300/20 blur-3xl"></div>

        <div class="relative">
            <a href="{{ route('events.show', $event) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-surface-400 transition hover:text-surface-700">
                <i class="fas fa-arrow-left text-[10px]"></i> Back to {{ $event->name }}
            </a>

            <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-soft" style="background: {{ $accent }}14; color: {{ $accent }};">
                        <i class="fas fa-chart-pie text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-gold-600">Financial overview</p>
                        <h1 class="mt-1 font-display text-2xl font-bold text-surface-900 sm:text-3xl">Payment &amp; Attendance Report</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-surface-500">
                            <span><i class="fas fa-calendar-days mr-1.5 text-surface-400"></i>{{ $event->event_date->format('l, d M Y') }}</span>
                            <span><i class="fas fa-location-dot mr-1.5 text-surface-400"></i>{{ $event->location }}</span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gold-50 px-2.5 py-1 font-semibold text-gold-700">
                                <i class="fas fa-coins text-[9px]"></i>{{ $collectionRate }}% collected
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <a href="{{ route('events.guests.index', $event) }}" class="btn btn-outline btn-sm flex-1 sm:flex-none">
                        <i class="fas fa-users"></i> Guests
                    </a>
                    <a href="{{ route('events.messages.report', $event) }}" class="btn btn-outline btn-sm flex-1 sm:flex-none">
                        <i class="fas fa-tower-broadcast"></i> Messages
                    </a>
                    <a href="{{ route('events.budget.index', $event) }}" class="btn btn-outline btn-sm flex-1 sm:flex-none">
                        <i class="fas fa-calculator"></i> Budget
                    </a>
                    <button type="button" onclick="window.print()" class="btn btn-primary btn-sm flex-1 sm:flex-none">
                        <i class="fas fa-print"></i> Print report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-5 hidden print:block">
    <h1 class="text-xl font-bold text-surface-900">{{ $event->name }} - Payment Report</h1>
    <p class="mt-1 text-sm text-surface-500">{{ $event->event_date->format('d M Y') }} &middot; {{ $event->location }}</p>
</div>

<!-- KPI cards -->
<div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-surface-400">Total guests</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-surface-900 sm:text-3xl">{{ number_format($totalGuests) }}</p>
                <p class="mt-1 text-[11px] text-surface-400">{{ number_format($stats['estimatedAttendance']) }} estimated seats</p>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><i class="fas fa-users"></i></span>
        </div>
        <div class="mt-4 flex items-center gap-2">
            <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-blue-600">{{ number_format($stats['singles']) }} single</span>
            <span class="rounded-full bg-purple-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-purple-600">{{ number_format($stats['doubles']) }} double</span>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-surface-400">Total Pledged</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-surface-900 sm:text-3xl">{{ number_format($stats['totalPledged']) }}</p>
                <p class="mt-1 text-[11px] text-surface-400">TZS committed by guests</p>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-surface-100 text-surface-500"><i class="fas fa-file-invoice-dollar"></i></span>
        </div>
        <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-surface-100">
            <div class="h-full rounded-full bg-surface-400" style="width: 100%;"></div>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-green-600/70">Total paid</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-green-600 sm:text-3xl">{{ number_format($stats['totalPaid']) }}</p>
                <p class="mt-1 text-[11px] text-surface-400">{{ $collectionRate }}% of pledged amount</p>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-600"><i class="fas fa-circle-check"></i></span>
        </div>
        <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-green-50">
            <div class="h-full rounded-full bg-green-500" style="width: {{ $collectionRate }}%;"></div>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-red-600/70">Outstanding</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-red-500 sm:text-3xl">{{ number_format($stats['outstanding']) }}</p>
                <p class="mt-1 text-[11px] text-surface-400">
                    {{ $stats['overpaid'] > 0 ? number_format($stats['overpaid']).' TZS overpaid' : 'Still to collect' }}
                </p>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-500"><i class="fas fa-hourglass-half"></i></span>
        </div>
        <div class="mt-4 flex items-center gap-2">
            <span class="rounded-full bg-red-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-red-600">{{ number_format($stats['statusCounts']['partial']) }} partial</span>
            <span class="rounded-full bg-surface-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-surface-500">{{ number_format($stats['statusCounts']['unpaid']) }} unpaid</span>
        </div>
    </div>
</div>

<!-- Budget & vendor position -->
<div class="card-surface mb-6 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-100 px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-gold-400 to-amber-600 text-white shadow-soft">
                <i class="fas fa-calculator text-sm"></i>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Budget &amp; vendor position</h2>
                <p class="text-[11px] text-surface-400">How event costs relate to guest contributions</p>
            </div>
        </div>
        <div class="flex items-center gap-2 print:hidden">
            <a href="{{ route('events.budget.report', $event) }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700">Budget report</a>
            <span class="text-surface-300">|</span>
            <a href="{{ route('events.budget.index', $event) }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700">Manage budget</a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-xl border border-surface-100 bg-surface-50/60 p-3.5">
            <p class="text-[9px] font-bold uppercase tracking-wider text-surface-400">Event budget</p>
            <p class="mt-1 text-lg font-bold text-surface-900">{{ number_format($budgetPosition['totalBudget']) }}</p>
            <p class="text-[10px] text-surface-400">{{ $budget['itemCount'] }} services</p>
        </div>
        <div class="rounded-xl border border-green-100 bg-green-50/60 p-3.5">
            <p class="text-[9px] font-bold uppercase tracking-wider text-green-600/70">Paid to vendors</p>
            <p class="mt-1 text-lg font-bold text-green-700">{{ number_format($budgetPosition['vendorPaid']) }}</p>
            <p class="text-[10px] text-green-600/70">{{ $budgetPosition['paidRate'] }}% of budget</p>
        </div>
        <div class="rounded-xl border border-red-100 bg-red-50/60 p-3.5">
            <p class="text-[9px] font-bold uppercase tracking-wider text-red-500">Vendor balance</p>
            <p class="mt-1 text-lg font-bold text-red-600">{{ number_format($budgetPosition['vendorOutstanding']) }}</p>
            <p class="text-[10px] text-red-500/70">still to pay</p>
        </div>
        <div class="rounded-xl border border-surface-100 bg-surface-50/60 p-3.5">
            <p class="text-[9px] font-bold uppercase tracking-wider text-surface-400">Guest pledges</p>
            <p class="mt-1 text-lg font-bold text-surface-900">{{ number_format($stats['totalPledged']) }}</p>
            <p class="text-[10px] text-surface-400">{{ $budgetPosition['fundingCoverage'] }}% of budget</p>
        </div>
        <div class="rounded-xl border {{ $budgetPosition['projectedBalance'] >= 0 ? 'border-green-100 bg-green-50/60' : 'border-amber-100 bg-amber-50/60' }} p-3.5">
            <p class="text-[9px] font-bold uppercase tracking-wider {{ $budgetPosition['projectedBalance'] >= 0 ? 'text-green-600/70' : 'text-amber-600/80' }}">{{ $budgetPosition['projectedBalance'] >= 0 ? 'Projected surplus' : 'Funding gap' }}</p>
            <p class="mt-1 text-lg font-bold {{ $budgetPosition['projectedBalance'] >= 0 ? 'text-green-700' : 'text-amber-700' }}">{{ number_format(abs($budgetPosition['projectedBalance'])) }}</p>
            <p class="text-[10px] {{ $budgetPosition['projectedBalance'] >= 0 ? 'text-green-600/70' : 'text-amber-600/80' }}">pledges vs budget</p>
        </div>
        <div class="rounded-xl border border-surface-100 bg-surface-50/60 p-3.5">
            <p class="text-[9px] font-bold uppercase tracking-wider text-surface-400">Cost per guest</p>
            <p class="mt-1 text-lg font-bold text-surface-900">{{ $budgetPosition['estimatedGuests'] > 0 ? number_format($budgetPosition['costPerGuest']) : '—' }}</p>
            <p class="text-[10px] text-surface-400">{{ $budgetPosition['estimatedGuests'] > 0 ? number_format($budgetPosition['estimatedGuests']).' expected guests' : 'estimated guests not set' }}</p>
        </div>
    </div>

    <div class="grid gap-4 border-t border-surface-100 bg-surface-50/30 px-5 py-4 sm:grid-cols-2">
        <div>
            <div class="flex items-center justify-between text-[11px]">
                <span class="text-surface-500">Guest funding covers budget</span>
                <span class="font-bold text-surface-800">{{ $budgetPosition['fundingCoverage'] }}%</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-surface-200">
                <div class="h-full rounded-full bg-gradient-to-r from-gold-500 to-amber-300" style="width: {{ $budgetPosition['fundingCoverage'] }}%;"></div>
            </div>
        </div>
        <div>
            <div class="flex items-center justify-between text-[11px]">
                <span class="text-surface-500">Vendor payments made</span>
                <span class="font-bold text-surface-800">{{ $budgetPosition['paidRate'] }}%</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-surface-200">
                <div class="h-full rounded-full bg-gradient-to-r from-green-500 to-emerald-300" style="width: {{ $budgetPosition['paidRate'] }}%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Collection + status -->
<div class="mb-6 grid grid-cols-1 gap-5 lg:grid-cols-5">
    <div class="card-surface flex flex-col overflow-hidden lg:col-span-2">
        <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-gold-400 to-amber-600 text-white shadow-soft">
                    <i class="fas fa-arrow-trend-up text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Collection progress</h2>
                    <p class="text-[11px] text-surface-400">Paid against pledged</p>
                </div>
            </div>
            <span class="badge bg-gold-50 text-gold-700">{{ $collectionRate }}%</span>
        </div>

        <div class="flex flex-1 flex-col items-center justify-center p-6">
            <div class="relative h-40 w-40 rounded-full"
                style="background: conic-gradient(#d4af37 0 {{ $collectionRate }}%, #f4f4f5 {{ $collectionRate }}% 100%);">
                <div class="absolute inset-[14px] flex flex-col items-center justify-center rounded-full bg-white shadow-inner">
                    <span class="font-display text-4xl font-bold tracking-tight text-surface-900">{{ $collectionRate }}%</span>
                    <span class="text-[9px] font-bold uppercase tracking-[0.14em] text-surface-400">collected</span>
                </div>
            </div>

            <div class="mt-6 grid w-full grid-cols-2 gap-3">
                <div class="rounded-xl border border-green-100 bg-green-50/60 p-3 text-center">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-green-600/70">Paid</p>
                    <p class="mt-1 text-sm font-bold text-green-700">{{ number_format($stats['totalPaid']) }}</p>
                </div>
                <div class="rounded-xl border border-red-100 bg-red-50/60 p-3 text-center">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-red-600/70">Remaining</p>
                    <p class="mt-1 text-sm font-bold text-red-600">{{ number_format($stats['outstanding']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card-surface flex flex-col overflow-hidden lg:col-span-3">
        <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-rose-600 text-white shadow-soft">
                    <i class="fas fa-wallet text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Payment status</h2>
                    <p class="text-[11px] text-surface-400">How guest balances are distributed</p>
                </div>
            </div>
            <span class="text-[11px] font-semibold text-surface-400">{{ number_format($totalGuests) }} guests</span>
        </div>

        <div class="flex-1 p-5">
            <div class="flex h-3 w-full overflow-hidden rounded-full bg-surface-100">
                @foreach($stats['statusCounts'] as $status => $count)
                <div class="{{ $statusMeta[$status]['color'] }}" style="width: {{ ($count / $statusTotal) * 100 }}%;" title="{{ $statusMeta[$status]['label'] }}: {{ $count }}"></div>
                @endforeach
            </div>

            <div class="mt-5 grid gap-2.5 sm:grid-cols-2">
                @foreach($stats['statusCounts'] as $status => $count)
                <div class="flex items-center justify-between rounded-xl border border-surface-100 bg-surface-50/60 px-3.5 py-3">
                    <span class="flex items-center gap-2 text-xs font-medium text-surface-600">
                        <span class="h-2 w-2 rounded-full {{ $statusMeta[$status]['color'] }}"></span>
                        {{ $statusMeta[$status]['label'] }}
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="text-[10px] text-surface-400">{{ (int) round(($count / $statusTotal) * 100) }}%</span>
                        <span class="text-sm font-bold {{ $statusMeta[$status]['soft'] }} rounded-md px-2 py-0.5">{{ number_format($count) }}</span>
                    </span>
                </div>
                @endforeach
            </div>

            <div class="mt-4 rounded-xl border border-surface-100 bg-surface-50/70 px-4 py-3">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-surface-500">Collection rate</span>
                    <span class="font-bold text-surface-900">{{ $collectionRate }}%</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-surface-200">
                    <div class="h-full rounded-full bg-gradient-to-r from-gold-500 to-amber-300" style="width: {{ $collectionRate }}%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance -->
<div class="mb-6 grid grid-cols-1 gap-5 lg:grid-cols-2">
    <div class="card-surface flex flex-col overflow-hidden">
        <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-soft">
                    <i class="fas fa-chair text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Attendance estimate</h2>
                    <p class="text-[11px] text-surface-400">Expected seats and RSVP responses</p>
                </div>
            </div>
        </div>

        <div class="flex-1 p-5">
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600/70">Single cards</span>
                        <i class="fas fa-user text-[11px] text-blue-400"></i>
                    </div>
                    <p class="mt-1.5 text-2xl font-bold text-blue-700">{{ number_format($stats['singles']) }}</p>
                    <p class="text-[10px] text-blue-500">1 seat each</p>
                </div>
                <div class="rounded-xl border border-purple-100 bg-purple-50/70 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600/70">Double cards</span>
                        <i class="fas fa-user-group text-[11px] text-purple-400"></i>
                    </div>
                    <p class="mt-1.5 text-2xl font-bold text-purple-700">{{ number_format($stats['doubles']) }}</p>
                    <p class="text-[10px] text-purple-500">2 seats each</p>
                </div>
            </div>

            <div class="mt-4 space-y-2.5">
                <div class="flex items-center justify-between rounded-xl border border-surface-100 px-3.5 py-3">
                    <span class="text-xs text-surface-500">Estimated total attendance</span>
                    <span class="text-sm font-bold text-surface-900">{{ number_format($stats['estimatedAttendance']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-xl border border-green-100 bg-green-50/60 px-3.5 py-3">
                    <span class="flex items-center gap-2 text-xs text-green-700"><i class="fas fa-circle-check text-[10px]"></i>RSVP confirmed seats</span>
                    <span class="text-sm font-bold text-green-700">{{ number_format($stats['confirmedAttendance']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-xl border border-amber-100 bg-amber-50/60 px-3.5 py-3">
                    <span class="flex items-center gap-2 text-xs text-amber-700"><i class="fas fa-clock text-[10px]"></i>Pending RSVP seats</span>
                    <span class="text-sm font-bold text-amber-700">{{ number_format($stats['pendingAttendance']) }}</span>
                </div>
            </div>
            <p class="mt-3 text-[10px] text-surface-400">Estimate assumes every single card brings 1 guest and every double card brings 2 guests.</p>
        </div>
    </div>

    <div class="card-surface flex flex-col overflow-hidden">
        <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-gold-400 to-amber-600 text-white shadow-soft">
                    <i class="fas fa-layer-group text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Breakdown by category</h2>
                    <p class="text-[11px] text-surface-400">Guest groups and their contribution totals</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-50/80">
                    <tr class="text-left text-[10px] font-bold uppercase tracking-[0.1em] text-surface-400">
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3 text-right">Guests</th>
                        <th class="px-5 py-3 text-right">Pledged</th>
                        <th class="px-5 py-3 text-right">Paid</th>
                        <th class="px-5 py-3 text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100">
                    @forelse($categoryBreakdown as $category)
                    <tr class="transition hover:bg-surface-50/70">
                        <td class="px-5 py-3 font-semibold text-surface-800">{{ $category['category'] }}</td>
                        <td class="px-5 py-3 text-right text-surface-600">{{ number_format($category['guests']) }}</td>
                        <td class="px-5 py-3 text-right text-surface-600">{{ number_format($category['pledged']) }}</td>
                        <td class="px-5 py-3 text-right font-medium text-green-600">{{ number_format($category['paid']) }}</td>
                        <td class="px-5 py-3 text-right {{ $category['balance'] > 0 ? 'text-red-500' : 'text-surface-400' }}">{{ number_format($category['balance']) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-surface-400">No guest categories available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Outstanding guests -->
<div class="card-surface mb-6 overflow-hidden">
    <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-500">
                <i class="fas fa-triangle-exclamation text-sm"></i>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Largest outstanding balances</h2>
                <p class="text-[11px] text-surface-400">Guests with the highest unpaid amounts</p>
            </div>
        </div>
        <a href="{{ route('events.guests.index', [$event, 'payment_status' => 'partial']) }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700 print:hidden">View partial payments</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-50/80">
                <tr class="text-left text-[10px] font-bold uppercase tracking-[0.1em] text-surface-400">
                    <th class="px-5 py-3">Guest</th>
                    <th class="px-5 py-3">Card</th>
                    <th class="px-5 py-3 text-right">Pledged</th>
                    <th class="px-5 py-3 text-right">Paid</th>
                    <th class="px-5 py-3 text-right">Balance</th>
                    <th class="px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($outstandingGuests as $guest)
                <tr class="transition hover:bg-red-50/30">
                    <td class="px-5 py-3.5">
                        <a href="{{ route('events.guests.edit', [$event, $guest]) }}" class="font-semibold text-surface-900 hover:text-primary-600 print:text-surface-900">
                            {{ $guest->full_name }}
                        </a>
                        <div class="mt-0.5 text-[11px] text-surface-400">{{ $guest->phone_number }}</div>
                    </td>
                    <td class="px-5 py-3.5"><span class="badge bg-surface-100 text-surface-600">{{ ucfirst($guest->card_type) }}</span></td>
                    <td class="px-5 py-3.5 text-right text-surface-600">{{ number_format($guest->pledged_amount) }}</td>
                    <td class="px-5 py-3.5 text-right font-medium text-green-600">{{ number_format($guest->paid_amount) }}</td>
                    <td class="px-5 py-3.5 text-right font-bold text-red-500">{{ number_format($guest->balance) }}</td>
                    <td class="px-5 py-3.5"><span class="badge {{ $guest->payment_status_badge_class }}">{{ $guest->payment_status_label }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center">
                        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-green-50 text-green-500"><i class="fas fa-circle-check"></i></div>
                        <p class="mt-2 text-sm font-medium text-green-700">All guest balances are settled.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Recent payments -->
<div class="card-surface mb-6 overflow-hidden">
    <div class="flex items-center gap-3 border-b border-surface-100 px-5 py-4">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 text-green-600">
            <i class="fas fa-receipt text-sm"></i>
        </div>
        <div>
            <h2 class="text-sm font-semibold text-surface-900">Recent payments</h2>
            <p class="text-[11px] text-surface-400">Latest contributions recorded for this event</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-50/80">
                <tr class="text-left text-[10px] font-bold uppercase tracking-[0.1em] text-surface-400">
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Guest</th>
                    <th class="px-5 py-3">Method</th>
                    <th class="px-5 py-3">Reference</th>
                    <th class="px-5 py-3">Recorded by</th>
                    <th class="px-5 py-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($recentPayments as $payment)
                <tr class="transition hover:bg-surface-50/70">
                    <td class="px-5 py-3.5 text-surface-500">{{ $payment->paid_at->format('d M Y') }}</td>
                    <td class="px-5 py-3.5 font-semibold text-surface-800">{{ $payment->guest?->full_name ?? 'Deleted guest' }}</td>
                    <td class="px-5 py-3.5"><span class="badge bg-surface-100 text-surface-600">{{ $payment->method_label }}</span></td>
                    <td class="px-5 py-3.5 text-surface-500">{{ $payment->reference ?: '—' }}</td>
                    <td class="px-5 py-3.5 text-surface-500">{{ $payment->user?->name ?? 'System' }}</td>
                    <td class="px-5 py-3.5 text-right font-bold text-green-600">{{ number_format($payment->amount) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-surface-400">No payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Full payment register -->
<div class="card-surface overflow-hidden">
    <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <i class="fas fa-list-check text-sm"></i>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Guest payment register</h2>
                <p class="text-[11px] text-surface-400">Complete record of guest contributions</p>
            </div>
        </div>
        <span class="rounded-full bg-surface-100 px-3 py-1 text-[11px] font-semibold text-surface-500">{{ number_format($totalGuests) }} guests</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-50/80">
                <tr class="text-left text-[10px] font-bold uppercase tracking-[0.1em] text-surface-400">
                    <th class="px-5 py-3">Guest</th>
                    <th class="px-5 py-3">Card</th>
                    <th class="px-5 py-3">Seats</th>
                    <th class="px-5 py-3 text-right">Pledged</th>
                    <th class="px-5 py-3 text-right">Paid</th>
                    <th class="px-5 py-3 text-right">Balance</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Last payment</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($guests as $guest)
                <tr class="transition hover:bg-surface-50/70">
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-surface-800">{{ $guest->full_name }}</div>
                        <div class="mt-0.5 text-[11px] text-surface-400">{{ $guest->unique_id }} &middot; {{ $guest->phone_number }}</div>
                    </td>
                    <td class="px-5 py-3.5"><span class="badge bg-surface-100 text-surface-600">{{ ucfirst($guest->card_type) }}</span></td>
                    <td class="px-5 py-3.5 text-surface-500">{{ $guest->estimated_attendees }}</td>
                    <td class="px-5 py-3.5 text-right text-surface-600">{{ number_format($guest->pledged_amount) }}</td>
                    <td class="px-5 py-3.5 text-right font-medium text-green-600">{{ number_format($guest->paid_amount) }}</td>
                    <td class="px-5 py-3.5 text-right {{ $guest->balance > 0 ? 'font-semibold text-red-500' : 'text-surface-400' }}">{{ number_format($guest->balance) }}</td>
                    <td class="px-5 py-3.5"><span class="badge {{ $guest->payment_status_badge_class }}">{{ $guest->payment_status_label }}</span></td>
                    <td class="px-5 py-3.5 text-surface-500">{{ $guest->last_payment_at?->format('d M Y') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-5 py-12 text-center text-sm text-surface-400">No guests available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
    @media print {
        body { background: #fff !important; }
        .card-surface { box-shadow: none !important; border: 1px solid #e5e7eb !important; break-inside: avoid; }
        table { font-size: 10px !important; }
        th, td { padding: 5px 8px !important; }
        a { text-decoration: none !important; }
        .rounded-2xl, .rounded-xl { border-radius: 4px !important; }
    }
</style>
@endsection
