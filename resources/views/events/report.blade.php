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

    $cardLink = 'inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700';
    $th = 'whitespace-nowrap px-4 py-2.5 text-xs font-medium text-surface-500 first:pl-5 last:pr-5 sm:first:pl-6 sm:last:pr-6';
    $td = 'px-4 py-3 first:pl-5 last:pr-5 sm:first:pl-6 sm:last:pr-6';
@endphp

<!-- Breadcrumb -->
<div class="mb-4 flex flex-wrap items-center justify-between gap-2 print:hidden">
    <a href="{{ route('events.show', $event) }}" class="text-sm font-medium text-surface-500 transition hover:text-surface-800">
        <i class="fas fa-arrow-left mr-1.5"></i> Back to {{ $event->name }}
    </a>
    <div class="flex items-center gap-2 text-xs text-surface-400">
        <span class="max-w-[160px] truncate">{{ $event->name }}</span>
        <i class="fas fa-chevron-right text-[8px]"></i>
        <span class="font-medium text-surface-600">Report</span>
    </div>
</div>

<!-- Header -->
<div class="card-surface mb-6 overflow-hidden border-l-4 border-primary-500 print:hidden">

    <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <span class="badge bg-surface-100 text-surface-600">{{ $collectionRate }}% collected</span>
            <h1 class="mt-2.5 font-display text-2xl font-bold leading-tight text-surface-900 sm:text-3xl">Payment &amp; Attendance Report</h1>
            <div class="mt-2.5 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm text-surface-600">
                <span><i class="fas fa-calendar mr-1.5 text-surface-400"></i>{{ $event->event_date->format('l, d M Y') }}</span>
                <span><i class="fas fa-location-dot mr-1.5 text-surface-400"></i>{{ $event->location }}</span>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <button type="button" onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print report
            </button>
        </div>
    </div>

    @include('events.partials.section-nav', ['active' => 'report'])
</div>

<div class="mb-5 hidden print:block">
    <h1 class="text-xl font-bold text-surface-900">{{ $event->name }} - Payment Report</h1>
    <p class="mt-1 text-sm text-surface-500">{{ $event->event_date->format('d M Y') }} &middot; {{ $event->location }}</p>
</div>

<!-- KPI cards -->
<div class="mb-6 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-surface-200 bg-surface-200 shadow-soft lg:grid-cols-4">
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Total guests</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($totalGuests) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ number_format($stats['estimatedAttendance']) }} estimated seats</p>
        <p class="mt-0.5 text-xs text-surface-400">{{ number_format($stats['singles']) }} single · {{ number_format($stats['doubles']) }} double</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Total Pledged</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($stats['totalPledged']) }}</p>
        <p class="mt-1 text-xs text-surface-400">TZS committed by guests</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Total paid</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-green-600">{{ number_format($stats['totalPaid']) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ $collectionRate }}% of pledged amount</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Outstanding</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-red-600">{{ number_format($stats['outstanding']) }}</p>
        <p class="mt-1 text-xs text-surface-400">
            {{ $stats['overpaid'] > 0 ? number_format($stats['overpaid']).' TZS overpaid' : 'Still to collect' }}
        </p>
        <p class="mt-0.5 text-xs text-surface-400">{{ number_format($stats['statusCounts']['partial']) }} partial · {{ number_format($stats['statusCounts']['unpaid']) }} unpaid</p>
    </div>
</div>

<!-- Budget & vendor position -->
<div class="card-surface mb-6 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
        <div>
            <h2 class="text-sm font-semibold text-surface-900">Budget &amp; vendor position</h2>
            <p class="mt-0.5 text-xs text-surface-500">How event costs relate to guest contributions</p>
        </div>
        <div class="flex items-center gap-4 print:hidden">
            <a href="{{ route('events.budget.report', $event) }}" class="{{ $cardLink }}">Budget report</a>
            <a href="{{ route('events.budget.index', $event) }}" class="{{ $cardLink }}">Manage budget <i class="fas fa-arrow-right text-[9px]"></i></a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-px bg-surface-100 sm:grid-cols-3 xl:grid-cols-6">
        <div class="bg-white px-5 py-4 sm:px-6 xl:px-5">
            <p class="text-xs text-surface-500">Event budget</p>
            <p class="mt-1 text-lg font-semibold tracking-tight text-surface-900">{{ number_format($budgetPosition['totalBudget']) }}</p>
            <p class="mt-0.5 text-xs text-surface-400">{{ $budget['itemCount'] }} services</p>
        </div>
        <div class="bg-white px-5 py-4 sm:px-6 xl:px-5">
            <p class="text-xs text-surface-500">Paid to vendors</p>
            <p class="mt-1 text-lg font-semibold tracking-tight text-green-600">{{ number_format($budgetPosition['vendorPaid']) }}</p>
            <p class="mt-0.5 text-xs text-surface-400">{{ $budgetPosition['paidRate'] }}% of budget</p>
        </div>
        <div class="bg-white px-5 py-4 sm:px-6 xl:px-5">
            <p class="text-xs text-surface-500">Vendor balance</p>
            <p class="mt-1 text-lg font-semibold tracking-tight text-red-600">{{ number_format($budgetPosition['vendorOutstanding']) }}</p>
            <p class="mt-0.5 text-xs text-surface-400">still to pay</p>
        </div>
        <div class="bg-white px-5 py-4 sm:px-6 xl:px-5">
            <p class="text-xs text-surface-500">Guest pledges</p>
            <p class="mt-1 text-lg font-semibold tracking-tight text-surface-900">{{ number_format($stats['totalPledged']) }}</p>
            <p class="mt-0.5 text-xs text-surface-400">{{ $budgetPosition['fundingCoverage'] }}% of budget</p>
        </div>
        <div class="bg-white px-5 py-4 sm:px-6 xl:px-5">
            <p class="text-xs text-surface-500">{{ $budgetPosition['projectedBalance'] >= 0 ? 'Projected surplus' : 'Funding gap' }}</p>
            <p class="mt-1 text-lg font-semibold tracking-tight {{ $budgetPosition['projectedBalance'] >= 0 ? 'text-green-600' : 'text-amber-600' }}">{{ number_format(abs($budgetPosition['projectedBalance'])) }}</p>
            <p class="mt-0.5 text-xs text-surface-400">pledges vs budget</p>
        </div>
        <div class="bg-white px-5 py-4 sm:px-6 xl:px-5">
            <p class="text-xs text-surface-500">Cost per guest</p>
            <p class="mt-1 text-lg font-semibold tracking-tight text-surface-900">{{ $budgetPosition['estimatedGuests'] > 0 ? number_format($budgetPosition['costPerGuest']) : '—' }}</p>
            <p class="mt-0.5 text-xs text-surface-400">{{ $budgetPosition['estimatedGuests'] > 0 ? number_format($budgetPosition['estimatedGuests']).' expected guests' : 'estimated guests not set' }}</p>
        </div>
    </div>

    <div class="grid gap-x-8 gap-y-4 border-t border-surface-100 px-5 py-4 sm:grid-cols-2 sm:px-6">
        <div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-surface-500">Guest funding covers budget</span>
                <span class="font-semibold text-surface-900">{{ $budgetPosition['fundingCoverage'] }}%</span>
            </div>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-100">
                <div class="h-full rounded-full bg-gold-500" style="width: {{ $budgetPosition['fundingCoverage'] }}%;"></div>
            </div>
        </div>
        <div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-surface-500">Vendor payments made</span>
                <span class="font-semibold text-surface-900">{{ $budgetPosition['paidRate'] }}%</span>
            </div>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-100">
                <div class="h-full rounded-full bg-green-500" style="width: {{ $budgetPosition['paidRate'] }}%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Collection + status -->
<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-5">
    <div class="card-surface flex flex-col overflow-hidden lg:col-span-2">
        <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Collection progress</h2>
                <p class="mt-0.5 text-xs text-surface-500">Paid against pledged</p>
            </div>
            <span class="text-sm font-semibold text-surface-900">{{ $collectionRate }}%</span>
        </div>

        <div class="flex flex-1 flex-col items-center justify-center p-5 sm:p-6">
            <div class="relative h-36 w-36 rounded-full"
                style="background: conic-gradient(#d4af37 0 {{ $collectionRate }}%, #f4f4f5 {{ $collectionRate }}% 100%);">
                <div class="absolute inset-[10px] flex flex-col items-center justify-center rounded-full bg-white">
                    <span class="text-3xl font-semibold tracking-tight text-surface-900">{{ $collectionRate }}%</span>
                    <span class="text-xs text-surface-500">collected</span>
                </div>
            </div>

            <div class="mt-6 grid w-full grid-cols-2 gap-4 border-t border-surface-100 pt-4">
                <div>
                    <p class="text-xs text-surface-500">Paid</p>
                    <p class="mt-0.5 text-base font-semibold text-green-600">{{ number_format($stats['totalPaid']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-surface-500">Remaining</p>
                    <p class="mt-0.5 text-base font-semibold text-red-600">{{ number_format($stats['outstanding']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card-surface flex flex-col overflow-hidden lg:col-span-3">
        <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Payment status</h2>
                <p class="mt-0.5 text-xs text-surface-500">How guest balances are distributed</p>
            </div>
            <span class="text-xs text-surface-500">{{ number_format($totalGuests) }} guests</span>
        </div>

        <div class="flex-1 p-5 sm:p-6">
            <div class="flex h-1.5 w-full overflow-hidden rounded-full bg-surface-100">
                @foreach($stats['statusCounts'] as $status => $count)
                <div class="{{ $statusMeta[$status]['color'] }}" style="width: {{ ($count / $statusTotal) * 100 }}%;" title="{{ $statusMeta[$status]['label'] }}: {{ $count }}"></div>
                @endforeach
            </div>

            <div class="mt-4 divide-y divide-surface-100">
                @foreach($stats['statusCounts'] as $status => $count)
                <div class="flex items-center justify-between py-2.5">
                    <span class="flex items-center gap-2 text-sm text-surface-600">
                        <span class="h-2 w-2 rounded-full {{ $statusMeta[$status]['color'] }}"></span>
                        {{ $statusMeta[$status]['label'] }}
                    </span>
                    <span class="flex items-center gap-3">
                        <span class="text-xs text-surface-400">{{ (int) round(($count / $statusTotal) * 100) }}%</span>
                        <span class="min-w-[2rem] text-right text-sm font-semibold text-surface-900">{{ number_format($count) }}</span>
                    </span>
                </div>
                @endforeach
            </div>

            <div class="mt-3 border-t border-surface-100 pt-4">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-surface-500">Collection rate</span>
                    <span class="font-semibold text-surface-900">{{ $collectionRate }}%</span>
                </div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-100">
                    <div class="h-full rounded-full bg-gold-500" style="width: {{ $collectionRate }}%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance -->
<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="card-surface flex flex-col overflow-hidden">
        <div class="border-b border-surface-100 px-5 py-4 sm:px-6">
            <h2 class="text-sm font-semibold text-surface-900">Attendance estimate</h2>
            <p class="mt-0.5 text-xs text-surface-500">Expected seats and RSVP responses</p>
        </div>

        <div class="flex-1 p-5 sm:p-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-surface-500">Single cards</p>
                    <p class="mt-1 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($stats['singles']) }}</p>
                    <p class="mt-0.5 text-xs text-surface-400">1 seat each</p>
                </div>
                <div>
                    <p class="text-xs text-surface-500">Double cards</p>
                    <p class="mt-1 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($stats['doubles']) }}</p>
                    <p class="mt-0.5 text-xs text-surface-400">2 seats each</p>
                </div>
            </div>

            <div class="mt-4 divide-y divide-surface-100 border-t border-surface-100">
                <div class="flex items-center justify-between py-2.5">
                    <span class="text-sm text-surface-600">Estimated total attendance</span>
                    <span class="text-sm font-semibold text-surface-900">{{ number_format($stats['estimatedAttendance']) }}</span>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <span class="flex items-center gap-2 text-sm text-surface-600"><span class="h-2 w-2 rounded-full bg-green-500"></span>RSVP confirmed seats</span>
                    <span class="text-sm font-semibold text-surface-900">{{ number_format($stats['confirmedAttendance']) }}</span>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <span class="flex items-center gap-2 text-sm text-surface-600"><span class="h-2 w-2 rounded-full bg-amber-400"></span>Pending RSVP seats</span>
                    <span class="text-sm font-semibold text-surface-900">{{ number_format($stats['pendingAttendance']) }}</span>
                </div>
            </div>
            <p class="mt-3 text-xs text-surface-400">Estimate assumes every single card brings 1 guest and every double card brings 2 guests.</p>
        </div>
    </div>

    <div class="card-surface flex flex-col overflow-hidden">
        <div class="border-b border-surface-100 px-5 py-4 sm:px-6">
            <h2 class="text-sm font-semibold text-surface-900">Breakdown by category</h2>
            <p class="mt-0.5 text-xs text-surface-500">Guest groups and their contribution totals</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-surface-100 text-left">
                        <th class="{{ $th }}">Category</th>
                        <th class="{{ $th }} text-right">Guests</th>
                        <th class="{{ $th }} text-right">Pledged</th>
                        <th class="{{ $th }} text-right">Paid</th>
                        <th class="{{ $th }} text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100">
                    @forelse($categoryBreakdown as $category)
                    <tr class="transition hover:bg-surface-50">
                        <td class="{{ $td }} font-medium text-surface-900">{{ $category['category'] }}</td>
                        <td class="{{ $td }} text-right text-surface-600">{{ number_format($category['guests']) }}</td>
                        <td class="{{ $td }} text-right text-surface-600">{{ number_format($category['pledged']) }}</td>
                        <td class="{{ $td }} text-right font-medium text-green-600">{{ number_format($category['paid']) }}</td>
                        <td class="{{ $td }} text-right {{ $category['balance'] > 0 ? 'text-red-600' : 'text-surface-400' }}">{{ number_format($category['balance']) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-surface-500">No guest categories available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Outstanding guests -->
<div class="card-surface mb-6 overflow-hidden">
    <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
        <div>
            <h2 class="text-sm font-semibold text-surface-900">Largest outstanding balances</h2>
            <p class="mt-0.5 text-xs text-surface-500">Guests with the highest unpaid amounts</p>
        </div>
        <a href="{{ route('events.guests.index', [$event, 'payment_status' => 'partial']) }}" class="{{ $cardLink }} shrink-0 print:hidden">View partial payments <i class="fas fa-arrow-right text-[9px]"></i></a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-surface-100 text-left">
                    <th class="{{ $th }}">Guest</th>
                    <th class="{{ $th }}">Card</th>
                    <th class="{{ $th }} text-right">Pledged</th>
                    <th class="{{ $th }} text-right">Paid</th>
                    <th class="{{ $th }} text-right">Balance</th>
                    <th class="{{ $th }}">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($outstandingGuests as $guest)
                <tr class="transition hover:bg-surface-50">
                    <td class="{{ $td }}">
                        <a href="{{ route('events.guests.edit', [$event, $guest]) }}" class="font-medium text-surface-900 hover:text-primary-600 print:text-surface-900">
                            {{ $guest->full_name }}
                        </a>
                        <div class="mt-0.5 text-xs text-surface-400">{{ $guest->phone_number }}</div>
                    </td>
                    <td class="{{ $td }}"><span class="badge bg-surface-100 text-surface-600">{{ ucfirst($guest->card_type) }}</span></td>
                    <td class="{{ $td }} text-right text-surface-600">{{ number_format($guest->pledged_amount) }}</td>
                    <td class="{{ $td }} text-right font-medium text-green-600">{{ number_format($guest->paid_amount) }}</td>
                    <td class="{{ $td }} text-right font-semibold text-red-600">{{ number_format($guest->balance) }}</td>
                    <td class="{{ $td }}"><span class="badge {{ $guest->payment_status_badge_class }}">{{ $guest->payment_status_label }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-sm text-surface-500">All guest balances are settled.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Recent payments -->
<div class="card-surface mb-6 overflow-hidden">
    <div class="border-b border-surface-100 px-5 py-4 sm:px-6">
        <h2 class="text-sm font-semibold text-surface-900">Recent payments</h2>
        <p class="mt-0.5 text-xs text-surface-500">Latest contributions recorded for this event</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-surface-100 text-left">
                    <th class="{{ $th }}">Date</th>
                    <th class="{{ $th }}">Guest</th>
                    <th class="{{ $th }}">Method</th>
                    <th class="{{ $th }}">Reference</th>
                    <th class="{{ $th }}">Recorded by</th>
                    <th class="{{ $th }} text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($recentPayments as $payment)
                <tr class="transition hover:bg-surface-50">
                    <td class="{{ $td }} whitespace-nowrap text-surface-500">{{ $payment->paid_at->format('d M Y') }}</td>
                    <td class="{{ $td }} font-medium text-surface-900">{{ $payment->guest?->full_name ?? 'Deleted guest' }}</td>
                    <td class="{{ $td }}"><span class="badge bg-surface-100 text-surface-600">{{ $payment->method_label }}</span></td>
                    <td class="{{ $td }} text-surface-500">{{ $payment->reference ?: '—' }}</td>
                    <td class="{{ $td }} text-surface-500">{{ $payment->user?->name ?? 'System' }}</td>
                    <td class="{{ $td }} text-right font-semibold text-green-600">{{ number_format($payment->amount) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-surface-500">No payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Full payment register -->
<div class="card-surface overflow-hidden">
    <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
        <div>
            <h2 class="text-sm font-semibold text-surface-900">Guest payment register</h2>
            <p class="mt-0.5 text-xs text-surface-500">Complete record of guest contributions</p>
        </div>
        <span class="badge bg-surface-100 text-surface-600">{{ number_format($totalGuests) }} guests</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-surface-100 text-left">
                    <th class="{{ $th }}">Guest</th>
                    <th class="{{ $th }}">Card</th>
                    <th class="{{ $th }}">Seats</th>
                    <th class="{{ $th }} text-right">Pledged</th>
                    <th class="{{ $th }} text-right">Paid</th>
                    <th class="{{ $th }} text-right">Balance</th>
                    <th class="{{ $th }}">Status</th>
                    <th class="{{ $th }}">Last payment</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($guests as $guest)
                <tr class="transition hover:bg-surface-50">
                    <td class="{{ $td }}">
                        <div class="font-medium text-surface-900">{{ $guest->full_name }}</div>
                        <div class="mt-0.5 text-xs text-surface-400">{{ $guest->unique_id }} &middot; {{ $guest->phone_number }}</div>
                    </td>
                    <td class="{{ $td }}"><span class="badge bg-surface-100 text-surface-600">{{ ucfirst($guest->card_type) }}</span></td>
                    <td class="{{ $td }} text-surface-500">{{ $guest->estimated_attendees }}</td>
                    <td class="{{ $td }} text-right text-surface-600">{{ number_format($guest->pledged_amount) }}</td>
                    <td class="{{ $td }} text-right font-medium text-green-600">{{ number_format($guest->paid_amount) }}</td>
                    <td class="{{ $td }} text-right {{ $guest->balance > 0 ? 'font-semibold text-red-600' : 'text-surface-400' }}">{{ number_format($guest->balance) }}</td>
                    <td class="{{ $td }}"><span class="badge {{ $guest->payment_status_badge_class }}">{{ $guest->payment_status_label }}</span></td>
                    <td class="{{ $td }} whitespace-nowrap text-surface-500">{{ $guest->last_payment_at?->format('d M Y') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-5 py-10 text-center text-sm text-surface-500">No guests available.</td></tr>
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
