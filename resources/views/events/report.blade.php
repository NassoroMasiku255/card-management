@extends('layouts.app')

@section('title', 'Payment Report - ' . $event->name)

@section('content')
@php
    $totalGuests = $guests->count();
    $statusTotal = max(array_sum($stats['statusCounts']), 1);
    $statusMeta = [
        'paid' => ['label' => 'Paid', 'color' => 'bg-green-500', 'text' => 'text-green-600'],
        'partial' => ['label' => 'Partial', 'color' => 'bg-amber-500', 'text' => 'text-amber-600'],
        'unpaid' => ['label' => 'Unpaid', 'color' => 'bg-surface-400', 'text' => 'text-surface-600'],
        'overpaid' => ['label' => 'Overpaid', 'color' => 'bg-blue-500', 'text' => 'text-blue-600'],
    ];
@endphp

<div class="mb-6 print:hidden">
    <a href="{{ route('events.show', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $event->name }}
    </a>
    <div class="flex flex-wrap items-center justify-between gap-3 mt-2">
        <div>
            <h1 class="font-display text-2xl font-bold text-surface-900">Payment &amp; Attendance Report</h1>
            <p class="text-sm text-surface-500">{{ $event->name }} &middot; {{ $event->event_date->format('d M Y') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('events.guests.index', $event) }}" class="btn btn-outline btn-sm">
                <i class="fas fa-users"></i> Guests
            </a>
            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
</div>

<div class="hidden print:block mb-5">
    <h1 class="text-xl font-bold text-surface-900">{{ $event->name }} - Payment Report</h1>
    <p class="text-sm text-surface-500">{{ $event->event_date->format('d M Y') }} &middot; {{ $event->location }}</p>
</div>

<!-- Summary cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card-surface p-5">
        <div class="flex items-center justify-between">
            <div class="text-xs uppercase tracking-wide text-surface-400">Total Guests</div>
            <i class="fas fa-users text-blue-400"></i>
        </div>
        <div class="text-2xl font-bold text-surface-900 mt-2">{{ number_format($totalGuests) }}</div>
        <div class="text-xs text-surface-500 mt-1">Estimated {{ number_format($stats['estimatedAttendance']) }} seats</div>
    </div>

    <div class="card-surface p-5">
        <div class="flex items-center justify-between">
            <div class="text-xs uppercase tracking-wide text-surface-400">Total Pledged</div>
            <i class="fas fa-file-invoice-dollar text-surface-400"></i>
        </div>
        <div class="text-2xl font-bold text-surface-900 mt-2">{{ number_format($stats['totalPledged']) }}</div>
        <div class="text-xs text-surface-500 mt-1">TZS committed</div>
    </div>

    <div class="card-surface p-5">
        <div class="flex items-center justify-between">
            <div class="text-xs uppercase tracking-wide text-surface-400">Total Paid</div>
            <i class="fas fa-circle-check text-green-500"></i>
        </div>
        <div class="text-2xl font-bold text-green-600 mt-2">{{ number_format($stats['totalPaid']) }}</div>
        <div class="text-xs text-surface-500 mt-1">{{ $stats['collectionRate'] }}% of pledged amount</div>
    </div>

    <div class="card-surface p-5">
        <div class="flex items-center justify-between">
            <div class="text-xs uppercase tracking-wide text-surface-400">Outstanding</div>
            <i class="fas fa-hourglass-half text-red-400"></i>
        </div>
        <div class="text-2xl font-bold text-red-500 mt-2">{{ number_format($stats['outstanding']) }}</div>
        <div class="text-xs text-surface-500 mt-1">
            {{ $stats['overpaid'] > 0 ? number_format($stats['overpaid']).' TZS overpaid' : 'Still to collect' }}
        </div>
    </div>
</div>

<div class="card-surface p-5 mb-6">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-sm font-semibold text-surface-800"><i class="fas fa-chart-line text-primary-500 mr-1.5"></i>Collection Progress</h2>
        <span class="text-sm font-bold text-surface-900">{{ $stats['collectionRate'] }}%</span>
    </div>
    <div class="w-full h-3 rounded-full bg-surface-100 overflow-hidden">
        <div class="h-full rounded-full bg-green-500" style="width: {{ $stats['collectionRate'] }}%;"></div>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-2 mt-3 text-xs text-surface-500">
        <span>Paid: <strong class="text-green-600">{{ number_format($stats['totalPaid']) }} TZS</strong></span>
        <span>Remaining: <strong class="text-red-500">{{ number_format($stats['outstanding']) }} TZS</strong></span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Payment status -->
    <div class="card-surface p-5">
        <h2 class="text-sm font-semibold text-surface-800 mb-4"><i class="fas fa-wallet text-amber-500 mr-1.5"></i>Payment Status</h2>
        <div class="w-full h-3 rounded-full overflow-hidden flex bg-surface-100 mb-5">
            @foreach($stats['statusCounts'] as $status => $count)
            <div class="{{ $statusMeta[$status]['color'] }}" style="width: {{ ($count / $statusTotal) * 100 }}%;"></div>
            @endforeach
        </div>
        <div class="space-y-3">
            @foreach($stats['statusCounts'] as $status => $count)
            <div class="flex items-center justify-between">
                <span class="text-sm text-surface-500 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full {{ $statusMeta[$status]['color'] }}"></span>
                    {{ $statusMeta[$status]['label'] }}
                </span>
                <span class="text-sm font-bold {{ $statusMeta[$status]['text'] }}">{{ number_format($count) }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Attendance estimate -->
    <div class="card-surface p-5">
        <h2 class="text-sm font-semibold text-surface-800 mb-4"><i class="fas fa-chair text-purple-500 mr-1.5"></i>Attendance Estimate</h2>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bg-blue-50 rounded-xl p-3">
                <div class="text-xs text-blue-500 uppercase tracking-wide">Single Cards</div>
                <div class="text-xl font-bold text-blue-700">{{ number_format($stats['singles']) }}</div>
                <div class="text-[11px] text-blue-500">1 seat each</div>
            </div>
            <div class="bg-purple-50 rounded-xl p-3">
                <div class="text-xs text-purple-500 uppercase tracking-wide">Double Cards</div>
                <div class="text-xl font-bold text-purple-700">{{ number_format($stats['doubles']) }}</div>
                <div class="text-[11px] text-purple-500">2 seats each</div>
            </div>
        </div>
        <div class="space-y-2.5">
            <div class="flex items-center justify-between">
                <span class="text-sm text-surface-500">Estimated total attendance</span>
                <span class="text-sm font-bold text-surface-900">{{ number_format($stats['estimatedAttendance']) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-surface-500">RSVP confirmed seats</span>
                <span class="text-sm font-bold text-green-600">{{ number_format($stats['confirmedAttendance']) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-surface-500">Pending RSVP seats</span>
                <span class="text-sm font-bold text-amber-600">{{ number_format($stats['pendingAttendance']) }}</span>
            </div>
        </div>
        <p class="text-[11px] text-surface-400 mt-4">Estimate assumes every single card brings 1 guest and every double card brings 2 guests.</p>
    </div>
</div>

<!-- Category breakdown -->
<div class="card-surface overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-surface-100">
        <h2 class="text-sm font-semibold text-surface-800"><i class="fas fa-layer-group text-primary-500 mr-1.5"></i>Breakdown by Category</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-50">
                <tr class="text-left text-xs text-surface-400 uppercase tracking-wide">
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3 text-right">Guests</th>
                    <th class="px-5 py-3 text-right">Pledged</th>
                    <th class="px-5 py-3 text-right">Paid</th>
                    <th class="px-5 py-3 text-right">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($categoryBreakdown as $category)
                <tr>
                    <td class="px-5 py-3 font-medium text-surface-800">{{ $category['category'] }}</td>
                    <td class="px-5 py-3 text-right text-surface-600">{{ number_format($category['guests']) }}</td>
                    <td class="px-5 py-3 text-right text-surface-600">{{ number_format($category['pledged']) }}</td>
                    <td class="px-5 py-3 text-right text-green-600 font-medium">{{ number_format($category['paid']) }}</td>
                    <td class="px-5 py-3 text-right {{ $category['balance'] > 0 ? 'text-red-500' : 'text-surface-400' }}">{{ number_format($category['balance']) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-8 text-center text-surface-400">No guest categories available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Outstanding guests -->
<div class="card-surface overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-surface-100 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-surface-800"><i class="fas fa-triangle-exclamation text-red-400 mr-1.5"></i>Largest Outstanding Balances</h2>
        <a href="{{ route('events.guests.index', [$event, 'payment_status' => 'partial']) }}" class="text-xs text-primary-600 hover:text-primary-700 print:hidden">View partial payments</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-50">
                <tr class="text-left text-xs text-surface-400 uppercase tracking-wide">
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
                <tr>
                    <td class="px-5 py-3">
                        <a href="{{ route('events.guests.edit', [$event, $guest]) }}" class="font-medium text-surface-900 hover:text-primary-600 print:text-surface-900">
                            {{ $guest->full_name }}
                        </a>
                        <div class="text-xs text-surface-400">{{ $guest->phone_number }}</div>
                    </td>
                    <td class="px-5 py-3 text-surface-500">{{ ucfirst($guest->card_type) }}</td>
                    <td class="px-5 py-3 text-right text-surface-600">{{ number_format($guest->pledged_amount) }}</td>
                    <td class="px-5 py-3 text-right text-green-600">{{ number_format($guest->paid_amount) }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-red-500">{{ number_format($guest->balance) }}</td>
                    <td class="px-5 py-3"><span class="badge {{ $guest->payment_status_badge_class }}">{{ $guest->payment_status_label }}</span></td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-green-600"><i class="fas fa-circle-check mr-1"></i>All guest balances are settled.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Recent payments -->
<div class="card-surface overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-surface-100">
        <h2 class="text-sm font-semibold text-surface-800"><i class="fas fa-receipt text-green-500 mr-1.5"></i>Recent Payments</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-50">
                <tr class="text-left text-xs text-surface-400 uppercase tracking-wide">
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Guest</th>
                    <th class="px-5 py-3">Method</th>
                    <th class="px-5 py-3">Reference</th>
                    <th class="px-5 py-3">Recorded By</th>
                    <th class="px-5 py-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($recentPayments as $payment)
                <tr>
                    <td class="px-5 py-3 text-surface-500">{{ $payment->paid_at->format('d M Y') }}</td>
                    <td class="px-5 py-3 font-medium text-surface-800">{{ $payment->guest?->full_name ?? 'Deleted guest' }}</td>
                    <td class="px-5 py-3 text-surface-500">{{ $payment->method_label }}</td>
                    <td class="px-5 py-3 text-surface-500">{{ $payment->reference ?: '—' }}</td>
                    <td class="px-5 py-3 text-surface-500">{{ $payment->user?->name ?? 'System' }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-green-600">{{ number_format($payment->amount) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-surface-400">No payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Full payment register -->
<div class="card-surface overflow-hidden">
    <div class="px-5 py-4 border-b border-surface-100">
        <h2 class="text-sm font-semibold text-surface-800"><i class="fas fa-list-check text-blue-500 mr-1.5"></i>Guest Payment Register</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-50">
                <tr class="text-left text-xs text-surface-400 uppercase tracking-wide">
                    <th class="px-5 py-3">Guest</th>
                    <th class="px-5 py-3">Card</th>
                    <th class="px-5 py-3">Seats</th>
                    <th class="px-5 py-3 text-right">Pledged</th>
                    <th class="px-5 py-3 text-right">Paid</th>
                    <th class="px-5 py-3 text-right">Balance</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Last Payment</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($guests as $guest)
                <tr>
                    <td class="px-5 py-3">
                        <div class="font-medium text-surface-800">{{ $guest->full_name }}</div>
                        <div class="text-xs text-surface-400">{{ $guest->unique_id }} &middot; {{ $guest->phone_number }}</div>
                    </td>
                    <td class="px-5 py-3 text-surface-500">{{ ucfirst($guest->card_type) }}</td>
                    <td class="px-5 py-3 text-surface-500">{{ $guest->estimated_attendees }}</td>
                    <td class="px-5 py-3 text-right text-surface-600">{{ number_format($guest->pledged_amount) }}</td>
                    <td class="px-5 py-3 text-right text-green-600">{{ number_format($guest->paid_amount) }}</td>
                    <td class="px-5 py-3 text-right {{ $guest->balance > 0 ? 'text-red-500' : 'text-surface-400' }}">{{ number_format($guest->balance) }}</td>
                    <td class="px-5 py-3"><span class="badge {{ $guest->payment_status_badge_class }}">{{ $guest->payment_status_label }}</span></td>
                    <td class="px-5 py-3 text-surface-500">{{ $guest->last_payment_at?->format('d M Y') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-5 py-8 text-center text-surface-400">No guests available.</td></tr>
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
    }
</style>
@endsection
