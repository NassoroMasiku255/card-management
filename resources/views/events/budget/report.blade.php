@extends('layouts.app')

@section('title', 'Budget Report - ' . $event->name)

@section('content')
@php
    $money = function ($amount) {
        return number_format((float) $amount);
    };
    $budgetHealth = $projectedBalance >= 0 ? 'surplus' : 'gap';
@endphp

<!-- Screen header -->
<div class="mb-5 print:hidden">
    <div class="relative overflow-hidden rounded-2xl p-5 text-white shadow-soft-lg sm:p-7"
        style="background: linear-gradient(125deg, #17130a 0%, #3f2d13 52%, #8a6a22 145%);">
        <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-1/4 h-56 w-56 rounded-full bg-gold-300/20 blur-3xl"></div>

        <div class="relative">
            <a href="{{ route('events.budget.index', $event) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-white/60 transition hover:text-white">
                <i class="fas fa-arrow-left text-[10px]"></i> Back to budget
            </a>
            <div class="mt-4 flex flex-wrap items-start justify-between gap-5">
                <div class="flex min-w-0 items-start gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-gold-200 backdrop-blur">
                        <i class="fas fa-chart-pie text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gold-300">Financial summary</p>
                        <h1 class="mt-1 font-display text-2xl font-bold text-white sm:text-3xl">Budget Report</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-white/55">
                            <span><i class="fas fa-calendar-days mr-1.5 text-white/35"></i>{{ $event->event_date->format('d M Y') }}</span>
                            <span><i class="fas fa-location-dot mr-1.5 text-white/35"></i>{{ $event->location }}</span>
                            <span><i class="fas fa-clock mr-1.5 text-white/35"></i>Generated {{ now()->format('d M Y, H:i') }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <a href="{{ route('events.budget.index', $event) }}" class="btn flex-1 border border-white/20 bg-white/10 text-white hover:bg-white/20 sm:flex-none">
                        <i class="fas fa-pen"></i> Manage budget
                    </a>
                    <button type="button" onclick="window.print()" class="btn flex-1 bg-white text-surface-900 hover:bg-surface-100 sm:flex-none">
                        <i class="fas fa-print"></i> Print report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print header -->
<div class="mb-5 hidden print:block">
    <div class="flex items-start justify-between gap-4 border-b border-surface-200 pb-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-surface-400">Card Management System</p>
            <h1 class="mt-1 text-xl font-bold text-surface-900">{{ $event->name }} &mdash; Budget Report</h1>
            <p class="mt-1 text-sm text-surface-500">{{ $event->event_date->format('d M Y') }} &middot; {{ $event->location }}</p>
        </div>
        <div class="text-right text-[11px] text-surface-400">
            <p>Generated {{ now()->format('d M Y, H:i') }}</p>
            <p>{{ $budget['itemCount'] }} services &middot; {{ $budget['vendorCount'] }} vendors</p>
        </div>
    </div>
</div>

<!-- Primary KPIs -->
<div class="mb-5 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
    <div class="card-surface p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-surface-400">Total event budget</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-surface-900 sm:text-3xl">TZS {{ $money($budget['totalBudget']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $budget['itemCount'] }} services across {{ $budget['categoryBreakdown']->count() }} categories</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gold-50 text-gold-600"><i class="fas fa-sack-dollar text-xs"></i></span>
        </div>
    </div>

    <div class="card-surface p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-green-600/70">Paid to vendors</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-green-600 sm:text-3xl">TZS {{ $money($budget['totalPaid']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $budget['paidRate'] }}% of budget settled</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-600"><i class="fas fa-circle-check text-xs"></i></span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-green-50"><div class="h-full rounded-full bg-green-500" style="width: {{ $budget['paidRate'] }}%;"></div></div>
    </div>

    <div class="card-surface p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-red-500/80">Remaining vendor balance</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-red-500 sm:text-3xl">TZS {{ $money($budget['outstanding']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $budget['statusCounts']['unpaid'] }} unpaid &middot; {{ $budget['statusCounts']['partial'] }} partial</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-500"><i class="fas fa-hourglass-half text-xs"></i></span>
        </div>
    </div>

    <div class="card-surface p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] {{ $projectedBalance >= 0 ? 'text-green-600/70' : 'text-amber-600/80' }}">{{ $budgetHealth === 'surplus' ? 'Projected surplus' : 'Projected funding gap' }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight {{ $projectedBalance >= 0 ? 'text-green-600' : 'text-amber-600' }} sm:text-3xl">TZS {{ $money(abs($projectedBalance)) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $fundingCoverage }}% of budget covered by guest pledges</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $projectedBalance >= 0 ? 'bg-green-50 text-green-600' : 'bg-amber-50 text-amber-600' }}"><i class="fas fa-scale-balanced text-xs"></i></span>
        </div>
    </div>
</div>

<!-- Funding bridge -->
<div class="card-surface mb-5 overflow-hidden">
    <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-gold-400 to-amber-600 text-white shadow-soft"><i class="fas fa-arrow-trend-up text-sm"></i></div>
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Funding bridge</h2>
                <p class="text-[11px] text-surface-400">From guest pledges to vendor obligations</p>
            </div>
        </div>
        <span class="badge {{ $availableAfterVendors >= 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">{{ $availableAfterVendors >= 0 ? 'Positive cash position' : 'Cash shortfall' }}</span>
    </div>
    <div class="grid grid-cols-1 divide-y divide-surface-100 sm:grid-cols-5 sm:divide-x sm:divide-y-0">
        <div class="p-4">
            <p class="text-[9px] font-bold uppercase tracking-wider text-surface-400">Guest pledges</p>
            <p class="mt-1.5 text-lg font-bold text-surface-900">{{ $money($totalPledged) }}</p>
            <p class="mt-1 text-[10px] text-surface-400">Total promised</p>
        </div>
        <div class="p-4">
            <p class="text-[9px] font-bold uppercase tracking-wider text-green-600/70">Guest payments collected</p>
            <p class="mt-1.5 text-lg font-bold text-green-700">{{ $money($totalCollected) }}</p>
            <p class="mt-1 text-[10px] text-surface-400">Cash received</p>
        </div>
        <div class="p-4">
            <p class="text-[9px] font-bold uppercase tracking-wider text-surface-400">Vendor payments made</p>
            <p class="mt-1.5 text-lg font-bold text-surface-900">{{ $money($budget['totalPaid']) }}</p>
            <p class="mt-1 text-[10px] text-surface-400">Cash paid out</p>
        </div>
        <div class="p-4">
            <p class="text-[9px] font-bold uppercase tracking-wider text-red-500">Vendor balance</p>
            <p class="mt-1.5 text-lg font-bold text-red-500">{{ $money($budget['outstanding']) }}</p>
            <p class="mt-1 text-[10px] text-surface-400">Still owed</p>
        </div>
        <div class="p-4">
            <p class="text-[9px] font-bold uppercase tracking-wider text-gold-700">Cash after vendors</p>
            <p class="mt-1.5 text-lg font-bold {{ $availableAfterVendors >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ $money($availableAfterVendors) }}</p>
            <p class="mt-1 text-[10px] text-surface-400">Collected minus paid</p>
        </div>
    </div>
    <div class="space-y-3 border-t border-surface-100 bg-surface-50/40 px-5 py-4">
        <div>
            <div class="flex items-center justify-between text-[11px]"><span class="text-surface-500">Budget covered by guest pledges</span><span class="font-bold text-surface-800">{{ $fundingCoverage }}%</span></div>
            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-surface-200"><div class="h-full rounded-full bg-gradient-to-r from-gold-500 to-amber-300" style="width: {{ $fundingCoverage }}%;"></div></div>
        </div>
        <div>
            <div class="flex items-center justify-between text-[11px]"><span class="text-surface-500">Vendor obligations settled</span><span class="font-bold text-surface-800">{{ $budget['paidRate'] }}%</span></div>
            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-surface-200"><div class="h-full rounded-full bg-gradient-to-r from-green-500 to-emerald-300" style="width: {{ $budget['paidRate'] }}%;"></div></div>
        </div>
    </div>
</div>

<!-- Guest economics + category breakdown -->
<div class="mb-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
    <div class="card-surface overflow-hidden lg:col-span-1">
        <div class="flex items-center gap-3 border-b border-surface-100 px-5 py-4">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><i class="fas fa-user-group text-sm"></i></div>
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Guest economics</h2>
                <p class="text-[11px] text-surface-400">Cost and contribution per guest</p>
            </div>
        </div>
        <div class="space-y-4 p-5">
            <div class="flex items-end justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Expected guests</p>
                    <p class="mt-1 text-2xl font-bold text-surface-900">{{ $budget['estimatedGuests'] > 0 ? number_format($budget['estimatedGuests']) : 'Not set' }}</p>
                </div>
                <a href="{{ route('events.budget.index', $event) }}" class="text-[11px] font-semibold text-primary-600 hover:text-primary-700 print:hidden">Update</a>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="rounded-xl border border-surface-100 bg-surface-50/60 p-3">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-surface-400">Budget / guest</p>
                    <p class="mt-1 text-sm font-bold text-surface-900">{{ $budget['estimatedGuests'] > 0 ? $money($budget['costPerGuest']) : '—' }}</p>
                </div>
                <div class="rounded-xl border border-green-100 bg-green-50/60 p-3">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-green-600/70">Paid / guest</p>
                    <p class="mt-1 text-sm font-bold text-green-700">{{ $budget['estimatedGuests'] > 0 ? $money($budget['paidPerGuest']) : '—' }}</p>
                </div>
                <div class="rounded-xl border border-gold-100 bg-gold-50/60 p-3">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-gold-700">Pledged / guest</p>
                    <p class="mt-1 text-sm font-bold text-surface-900">{{ $budget['estimatedGuests'] > 0 ? $money($totalPledged / $budget['estimatedGuests']) : '—' }}</p>
                </div>
                <div class="rounded-xl border border-purple-100 bg-purple-50/60 p-3">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-purple-600/80">Vendors</p>
                    <p class="mt-1 text-sm font-bold text-purple-700">{{ number_format($budget['vendorCount']) }}</p>
                </div>
            </div>
            @if($budget['estimatedGuests'] <= 0)
            <p class="rounded-lg bg-blue-50 px-3 py-2 text-[10px] leading-relaxed text-blue-700">Set the expected guest count on the budget page to unlock per-guest planning figures.</p>
            @endif
        </div>
    </div>

    <div class="card-surface overflow-hidden lg:col-span-2">
        <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600"><i class="fas fa-layer-group text-sm"></i></div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Budget allocation by category</h2>
                    <p class="text-[11px] text-surface-400">Share of budget and settlement rate</p>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full responsive-table">
                <thead class="bg-surface-50/80">
                    <tr class="text-left text-[10px] font-bold uppercase tracking-[0.1em] text-surface-400">
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3 text-center">Items</th>
                        <th class="px-5 py-3 text-right">Budget</th>
                        <th class="px-5 py-3 text-right">Paid</th>
                        <th class="px-5 py-3 text-right">Balance</th>
                        <th class="px-5 py-3">Share</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100">
                    @forelse($budget['categoryBreakdown'] as $category)
                    <tr class="hover:bg-surface-50/40">
                        <td class="px-5 py-3 text-xs font-semibold text-surface-800" data-label="Category">{{ $category['category'] }}</td>
                        <td class="px-5 py-3 text-center text-xs text-surface-500" data-label="Items">{{ $category['items'] }}</td>
                        <td class="px-5 py-3 text-right text-xs font-semibold text-surface-700" data-label="Budget">{{ $money($category['total']) }}</td>
                        <td class="px-5 py-3 text-right text-xs font-semibold text-green-600" data-label="Paid">{{ $money($category['paid']) }}</td>
                        <td class="px-5 py-3 text-right text-xs font-semibold {{ $category['balance'] > 0 ? 'text-red-500' : 'text-surface-400' }}" data-label="Balance">{{ $money($category['balance']) }}</td>
                        <td class="px-5 py-3" data-label="Share">
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-full max-w-[120px] overflow-hidden rounded-full bg-surface-100">
                                    <div class="h-full rounded-full bg-gradient-to-r from-purple-500 to-fuchsia-400" style="width: {{ $budget['totalBudget'] > 0 ? round(($category['total'] / $budget['totalBudget']) * 100) : 0 }}%;"></div>
                                </div>
                                <span class="text-[10px] font-semibold text-surface-500">{{ $budget['totalBudget'] > 0 ? round(($category['total'] / $budget['totalBudget']) * 100) : 0 }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-surface-400">No budget services recorded yet.</td></tr>
                    @endforelse
                </tbody>
                @if($budget['categoryBreakdown']->isNotEmpty())
                <tfoot class="border-t border-surface-200 bg-surface-50/70">
                    <tr class="text-xs font-bold text-surface-800">
                        <td class="px-5 py-3">Total</td>
                        <td class="px-5 py-3 text-center">{{ $budget['itemCount'] }}</td>
                        <td class="px-5 py-3 text-right">{{ $money($budget['totalBudget']) }}</td>
                        <td class="px-5 py-3 text-right text-green-700">{{ $money($budget['totalPaid']) }}</td>
                        <td class="px-5 py-3 text-right text-red-600">{{ $money($budget['outstanding']) }}</td>
                        <td class="px-5 py-3">100%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<!-- Payment watchlist -->
@if($budget['overdueItems']->isNotEmpty() || $budget['upcomingItems']->isNotEmpty())
<div class="mb-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
    <div class="card-surface overflow-hidden">
        <div class="flex items-center justify-between border-b border-red-100 bg-red-50/50 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-100 text-red-600"><i class="fas fa-triangle-exclamation text-xs"></i></div>
                <div>
                    <h2 class="text-sm font-semibold text-red-800">Overdue payments</h2>
                    <p class="text-[10px] text-red-500/80">Past due date with an outstanding balance</p>
                </div>
            </div>
            <span class="badge bg-red-100 text-red-700">{{ $money($budget['overdueTotal']) }}</span>
        </div>
        <div class="divide-y divide-surface-100">
            @forelse($budget['overdueItems'] as $item)
            <div class="flex items-center justify-between gap-3 px-5 py-3">
                <div class="min-w-0">
                    <p class="truncate text-xs font-semibold text-surface-800">{{ $item->service_name }}</p>
                    <p class="text-[10px] text-surface-400">{{ $item->vendor_name ?: 'Vendor not set' }} &middot; due {{ $item->due_date->format('d M Y') }}</p>
                </div>
                <p class="shrink-0 text-xs font-bold text-red-600">{{ $money($item->balance) }}</p>
            </div>
            @empty
            <p class="px-5 py-8 text-center text-xs text-surface-400">No overdue vendor payments.</p>
            @endforelse
        </div>
    </div>

    <div class="card-surface overflow-hidden">
        <div class="flex items-center justify-between border-b border-amber-100 bg-amber-50/50 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-600"><i class="fas fa-calendar-clock text-xs"></i></div>
                <div>
                    <h2 class="text-sm font-semibold text-amber-800">Due in next 30 days</h2>
                    <p class="text-[10px] text-amber-600/80">Upcoming vendor payments to prepare for</p>
                </div>
            </div>
            <span class="badge bg-amber-100 text-amber-700">{{ $budget['upcomingItems']->count() }} items</span>
        </div>
        <div class="divide-y divide-surface-100">
            @forelse($budget['upcomingItems'] as $item)
            <div class="flex items-center justify-between gap-3 px-5 py-3">
                <div class="min-w-0">
                    <p class="truncate text-xs font-semibold text-surface-800">{{ $item->service_name }}</p>
                    <p class="text-[10px] text-surface-400">{{ $item->vendor_name ?: 'Vendor not set' }} &middot; due {{ $item->due_date->format('d M Y') }}</p>
                </div>
                <p class="shrink-0 text-xs font-bold text-amber-600">{{ $money($item->balance) }}</p>
            </div>
            @empty
            <p class="px-5 py-8 text-center text-xs text-surface-400">No payments due in the next 30 days.</p>
            @endforelse
        </div>
    </div>
</div>
@endif

<!-- Vendor obligations -->
<div class="card-surface mb-5 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-100 px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-surface-700 to-surface-900 text-white"><i class="fas fa-handshake text-sm"></i></div>
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Vendor obligations</h2>
                <p class="text-[11px] text-surface-400">Every service, payment and outstanding balance</p>
            </div>
        </div>
        <span class="badge bg-surface-100 text-surface-600">{{ $budget['itemCount'] }} services</span>
    </div>

    @if($budget['items']->isEmpty())
    <div class="px-6 py-12 text-center">
        <p class="text-sm text-surface-400">No budget services have been added yet.</p>
        <a href="{{ route('events.budget.index', $event) }}" class="btn btn-primary btn-sm mt-4 print:hidden"><i class="fas fa-plus"></i> Add services</a>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full responsive-table">
            <thead class="bg-surface-50/80">
                <tr class="text-left text-[10px] font-bold uppercase tracking-[0.1em] text-surface-400">
                    <th class="px-5 py-3">Service / vendor</th>
                    <th class="px-5 py-3">Contact</th>
                    <th class="px-5 py-3 text-right">Budget</th>
                    <th class="px-5 py-3 text-right">Paid</th>
                    <th class="px-5 py-3 text-right">Balance</th>
                    <th class="px-5 py-3">Due date</th>
                    <th class="px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @foreach($budget['items'] as $item)
                <tr class="hover:bg-surface-50/40">
                    <td class="px-5 py-3" data-label="Service / vendor">
                        <p class="text-xs font-semibold text-surface-800">{{ $item->service_name }}</p>
                        <p class="text-[10px] text-surface-400">{{ $item->vendor_name ?: 'Vendor not set' }} &middot; {{ $item->category_label }}</p>
                    </td>
                    <td class="px-5 py-3" data-label="Contact">
                        @if($item->vendor_contact_name)<p class="text-[11px] text-surface-600">{{ $item->vendor_contact_name }}</p>@endif
                        @if($item->vendor_phone)<p class="text-[10px] text-surface-400">{{ $item->vendor_phone }}</p>@endif
                        @if(! $item->vendor_contact_name && ! $item->vendor_phone)<span class="text-[10px] text-surface-300">&mdash;</span>@endif
                    </td>
                    <td class="px-5 py-3 text-right text-xs font-semibold text-surface-700" data-label="Budget">{{ $money($item->total_price) }}</td>
                    <td class="px-5 py-3 text-right text-xs font-semibold text-green-600" data-label="Paid">{{ $money($item->paid_amount) }}</td>
                    <td class="px-5 py-3 text-right text-xs font-bold {{ $item->balance > 0 ? 'text-red-500' : 'text-surface-400' }}" data-label="Balance">{{ $money($item->balance) }}</td>
                    <td class="px-5 py-3 text-[11px] {{ $item->is_overdue ? 'font-semibold text-red-500' : 'text-surface-500' }}" data-label="Due date">{{ $item->due_date ? $item->due_date->format('d M Y') : '—' }}</td>
                    <td class="px-5 py-3" data-label="Status"><span class="badge {{ $item->status_badge_class }}">{{ $item->status_label }}</span></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t border-surface-200 bg-surface-50/70">
                <tr class="text-xs font-bold text-surface-800">
                    <td class="px-5 py-3" colspan="2">Total</td>
                    <td class="px-5 py-3 text-right">{{ $money($budget['totalBudget']) }}</td>
                    <td class="px-5 py-3 text-right text-green-700">{{ $money($budget['totalPaid']) }}</td>
                    <td class="px-5 py-3 text-right text-red-600">{{ $money($budget['outstanding']) }}</td>
                    <td class="px-5 py-3" colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

<!-- Largest balances callout -->
@if($budget['largestBalances']->isNotEmpty())
<div class="card-surface mb-5 overflow-hidden">
    <div class="flex items-center gap-3 border-b border-surface-100 px-5 py-4">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-surface-100 text-surface-600"><i class="fas fa-list-ol text-xs"></i></div>
        <div>
            <h2 class="text-sm font-semibold text-surface-900">Largest outstanding balances</h2>
            <p class="text-[10px] text-surface-400">Prioritize these vendor payments</p>
        </div>
    </div>
    <div class="grid grid-cols-1 divide-y divide-surface-100 sm:grid-cols-2 sm:divide-x lg:grid-cols-5 lg:divide-y-0">
        @foreach($budget['largestBalances'] as $index => $item)
        <div class="p-4">
            <div class="flex items-center gap-2">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-surface-100 text-[9px] font-bold text-surface-500">{{ $index + 1 }}</span>
                <p class="truncate text-[11px] font-semibold text-surface-700">{{ $item->service_name }}</p>
            </div>
            <p class="mt-1.5 text-sm font-bold text-red-500">{{ $money($item->balance) }}</p>
            <p class="truncate text-[10px] text-surface-400">{{ $item->vendor_name ?: 'Vendor not set' }}</p>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="hidden border-t border-surface-200 pt-3 text-[10px] text-surface-400 print:block">
    {{ $event->name }} &middot; Budget report &middot; Prepared {{ now()->format('d M Y, H:i') }} &middot; All amounts in TZS
</div>

<style>
    @media print {
        @page { margin: 12mm; }
        body { background: #fff !important; }
        .card-surface { box-shadow: none !important; border: 1px solid #e5e7eb !important; break-inside: avoid; }
        table { font-size: 9px !important; }
        th, td { padding: 5px 7px !important; }
        .responsive-table td::before { display: none !important; }
    }
</style>
@endsection
