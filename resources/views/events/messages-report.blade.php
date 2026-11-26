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

    $guestTotal = $event->guests->count();
    $reachPct = $guestTotal > 0 ? min(100, (int) round(($stats['guestsReached'] / $guestTotal) * 100)) : 0;
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
        <span class="font-medium text-surface-600">Messages</span>
    </div>
</div>

<!-- Header -->
<div class="card-surface mb-6 overflow-hidden border-l-4 border-primary-500 print:hidden">

    <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge bg-surface-100 text-surface-600">Live tracking</span>
                <span class="badge gap-1.5 bg-green-100 text-green-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> WhatsApp connected
                </span>
            </div>
            <h1 class="mt-2.5 font-display text-2xl font-bold leading-tight text-surface-900 sm:text-3xl">Message Tracking Report</h1>
            <div class="mt-2.5 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm text-surface-600">
                <span><i class="fas fa-calendar mr-1.5 text-surface-400"></i>{{ $event->event_date->format('l, d M Y') }}</span>
                <span><i class="fas fa-location-dot mr-1.5 text-surface-400"></i>{{ $event->location }}</span>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <button type="button" onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    @include('events.partials.section-nav', ['active' => 'messages'])
</div>

<div class="mb-5 hidden print:block">
    <h1 class="text-xl font-bold text-surface-900">{{ $event->name }} - Message Tracking Report</h1>
    <p class="mt-1 text-sm text-surface-500">{{ $event->event_date->format('d M Y') }} &middot; {{ $event->location }}</p>
</div>

<!-- Message balance -->
<div class="card-surface mb-6 overflow-hidden print:hidden" data-ajax-region="message-balance">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
        <div>
            <h2 class="text-sm font-semibold text-surface-900">Message balance</h2>
            <p class="mt-0.5 text-xs text-surface-500">Each message sent for this event uses 1 from the matching balance. Sending stops when a balance reaches 0.</p>
        </div>
        <a href="{{ route('events.messages.buy', $event) }}" class="btn btn-primary btn-sm shrink-0">
            <i class="fas fa-cart-plus"></i> Buy messages
        </a>
    </div>

    <div class="grid grid-cols-1 divide-y divide-surface-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
        @foreach($balances as $balance)
        <div class="p-5 sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $balance['channel'] === 'whatsapp' ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600' }}">
                        <i class="{{ $balance['channel'] === 'whatsapp' ? 'fab fa-whatsapp text-lg' : 'fas fa-comment-sms' }}"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-surface-900">{{ $balance['label'] }} messages</p>
                        <p class="text-xs text-surface-500">{{ $balance['channel'] === 'whatsapp' ? 'Invitations, reminders and thank-you messages' : 'Normal SMS invitations and reminders' }}</p>
                    </div>
                </div>
                @if($balance['balance'] <= 0)
                <span class="badge shrink-0 bg-red-100 text-red-700">Sending stopped</span>
                @elseif($balance['balance'] <= 20)
                <span class="badge shrink-0 bg-amber-100 text-amber-700">Running low</span>
                @else
                <span class="badge shrink-0 bg-green-100 text-green-700">Active</span>
                @endif
            </div>
            <p class="mt-4 text-3xl font-semibold leading-none tracking-tight {{ $balance['balance'] <= 0 ? 'text-red-600' : 'text-surface-900' }}" id="balance-{{ $balance['channel'] }}">{{ number_format($balance['balance']) }}</p>
            <p class="mt-1.5 text-xs text-surface-400">messages left · {{ number_format($balance['used']) }} used · {{ number_format($balance['added']) }} added</p>
        </div>
        @endforeach
    </div>

    @if(auth()->user()->isAdmin())
    <form method="POST" action="{{ route('events.messages.balance.store', $event) }}" data-ajax class="border-t border-surface-100 px-5 py-4 sm:px-6"
        data-loading-text="Updating message balance...">
        @csrf
        <p class="text-sm font-semibold text-surface-900">Adjust balance <span class="ml-1 text-xs font-normal text-surface-500">Administrators only</span></p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="balance_channel" class="label-field">Message type</label>
                <select name="channel" id="balance_channel" class="input-field" required>
                    @foreach($balances as $balance)
                    <option value="{{ $balance['channel'] }}" @selected(old('channel') === $balance['channel'])>{{ $balance['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="balance_action" class="label-field">Action</label>
                <select name="action" id="balance_action" class="input-field" required>
                    <option value="add" @selected(old('action', 'add') === 'add')>Add messages</option>
                    <option value="remove" @selected(old('action') === 'remove')>Remove messages</option>
                </select>
            </div>
            <div>
                <label for="balance_quantity" class="label-field">Quantity</label>
                <input type="number" name="quantity" id="balance_quantity" value="{{ old('quantity') }}" min="1" max="1000000" required class="input-field" placeholder="e.g. 500">
            </div>
            <div class="lg:col-span-2">
                <label for="balance_note" class="label-field">Note (optional)</label>
                <div class="flex gap-2">
                    <input type="text" name="note" id="balance_note" value="{{ old('note') }}" maxlength="255" class="input-field" placeholder="e.g. Paid by M-Pesa, ref 12345">
                    <button type="submit" class="btn btn-outline shrink-0"><i class="fas fa-floppy-disk"></i> Save</button>
                </div>
            </div>
        </div>
        @foreach(['channel', 'action', 'quantity', 'note'] as $field)
        @error($field)<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        @endforeach
    </form>
    @endif

    @if($balanceHistory->isNotEmpty())
    <div class="border-t border-surface-100">
        <p class="px-5 pt-4 text-sm font-semibold text-surface-900 sm:px-6">Balance history</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-surface-100 text-left">
                        <th class="{{ $th }}">Date</th>
                        <th class="{{ $th }}">Message type</th>
                        <th class="{{ $th }}">Change</th>
                        <th class="{{ $th }} text-right">Balance after</th>
                        <th class="{{ $th }}">By</th>
                        <th class="{{ $th }}">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100">
                    @foreach($balanceHistory as $entry)
                    <tr>
                        <td class="{{ $td }} whitespace-nowrap text-surface-500">{{ $entry->created_at->format('d M Y, H:i') }}</td>
                        <td class="{{ $td }} text-surface-700">{{ $entry->channel_label }}</td>
                        <td class="{{ $td }} whitespace-nowrap font-semibold {{ $entry->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $entry->amount >= 0 ? '+' : '−' }}{{ number_format(abs($entry->amount)) }}</td>
                        <td class="{{ $td }} text-right text-surface-700">{{ number_format($entry->balance_after) }}</td>
                        <td class="{{ $td }} text-surface-500">{{ $entry->user?->name ?? 'System' }}</td>
                        <td class="{{ $td }} text-surface-500">{{ $entry->note ?: '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<!-- KPI cards -->
<div class="mb-6 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-surface-200 bg-surface-200 shadow-soft sm:grid-cols-3 xl:grid-cols-6">
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Messages sent</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($stats['totalMessages']) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ number_format($stats['guestsReached']) }} guests reached · {{ number_format($stats['pending']) }} pending</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Delivered</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($stats['delivered']) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ $deliveredRate }}% delivery rate</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Read</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($stats['read']) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ $readRate }}% of delivered</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Failed</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight {{ $stats['failed'] > 0 ? 'text-red-600' : 'text-surface-900' }}">{{ number_format($stats['failed']) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ $failureRate }}% of all messages</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Replies</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($stats['replyCount']) }}</p>
        <p class="mt-1 text-xs text-surface-400">{{ number_format($stats['uniqueRepliers']) }} unique guests · {{ $replyPct }}% reply rate</p>
    </div>
    <div class="bg-white p-4 sm:p-5">
        <p class="text-xs font-medium text-surface-500">Guests reached</p>
        <p class="mt-1.5 text-2xl font-semibold tracking-tight text-surface-900">{{ number_format($stats['guestsReached']) }}</p>
        <p class="mt-1 text-xs text-surface-400">of {{ number_format($guestTotal) }} event guests</p>
        <div class="mt-2 h-1 overflow-hidden rounded-full bg-surface-100">
            <div class="h-full rounded-full bg-gold-500" style="width: {{ $reachPct }}%;"></div>
        </div>
    </div>
</div>

<!-- Channels -->
<div class="card-surface mb-6 overflow-hidden">
    <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
        <div>
            <h2 class="text-sm font-semibold text-surface-900">Messaging channels</h2>
            <p class="mt-0.5 text-xs text-surface-500">Delivery performance by channel</p>
        </div>
        <span class="hidden text-xs text-surface-400 sm:block">WhatsApp vs normal messages</span>
    </div>

    <div class="grid grid-cols-1 divide-y divide-surface-100 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
        <!-- WhatsApp channel -->
        <div class="p-5 sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 text-green-600">
                        <i class="fab fa-whatsapp text-lg"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-surface-900">{{ $channels['whatsapp']['label'] }}</p>
                        <p class="text-xs text-surface-500">Primary channel</p>
                    </div>
                </div>
                <span class="badge bg-green-100 text-green-700">Active</span>
            </div>

            <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs text-surface-500">Messages sent</p>
                    <p class="mt-1 text-3xl font-semibold leading-none tracking-tight text-surface-900">{{ number_format($channels['whatsapp']['total']) }}</p>
                    <p class="mt-1.5 text-xs text-surface-400">{{ number_format($channels['whatsapp']['delivered']) }} delivered &middot; {{ number_format($channels['whatsapp']['read']) }} read</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-semibold tracking-tight text-surface-900">{{ $deliveredRate }}%</p>
                    <p class="text-xs text-surface-500">delivery rate</p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-3 gap-4 border-t border-surface-100 pt-4">
                <div>
                    <p class="flex items-center gap-1.5 text-xs text-surface-500"><span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>Delivered</p>
                    <p class="mt-0.5 text-base font-semibold text-surface-900">{{ number_format($channels['whatsapp']['delivered']) }}</p>
                </div>
                <div>
                    <p class="flex items-center gap-1.5 text-xs text-surface-500"><span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>Read</p>
                    <p class="mt-0.5 text-base font-semibold text-surface-900">{{ number_format($channels['whatsapp']['read']) }}</p>
                </div>
                <div>
                    <p class="flex items-center gap-1.5 text-xs text-surface-500"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Failed</p>
                    <p class="mt-0.5 text-base font-semibold text-surface-900">{{ number_format($channels['whatsapp']['failed']) }}</p>
                </div>
            </div>
        </div>

        <!-- Normal messages channel -->
        <div class="p-5 sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-surface-100 text-surface-500">
                        <i class="fas fa-comment-dots"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-surface-900">{{ $channels['normal']['label'] }}</p>
                        <p class="text-xs text-surface-500">SMS &amp; in-app</p>
                    </div>
                </div>
                <span class="badge bg-amber-100 text-amber-700">Coming soon</span>
            </div>

            <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs text-surface-500">Messages sent</p>
                    <p class="mt-1 text-3xl font-semibold leading-none tracking-tight text-surface-300">{{ number_format($channels['normal']['total']) }}</p>
                    <p class="mt-1.5 text-xs text-surface-400">Normal messages are not enabled yet</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-semibold tracking-tight text-surface-300">—</p>
                    <p class="text-xs text-surface-500">delivery rate</p>
                </div>
            </div>

            <p class="mt-5 border-t border-surface-100 pt-4 text-xs leading-relaxed text-surface-500">Normal messaging is being prepared. When it launches, SMS and in-app delivery will appear here alongside WhatsApp with the same tracking detail.</p>
        </div>
    </div>
</div>

<!-- Funnel + health -->
<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-5">
    <!-- Funnel -->
    <div class="card-surface overflow-hidden lg:col-span-3">
        <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Message journey</h2>
                <p class="mt-0.5 text-xs text-surface-500">From sent to replied</p>
            </div>
            <span class="badge bg-surface-100 text-surface-600">{{ number_format($stats['totalMessages']) }} total</span>
        </div>

        <div class="space-y-4 p-5 sm:p-6">
            @php
                $funnel = [
                    ['label' => 'Sent', 'value' => $stats['totalMessages'], 'pct' => $stats['totalMessages'] > 0 ? 100 : 0, 'color' => 'bg-primary-500', 'hint' => 'Attempts recorded'],
                    ['label' => 'Delivered', 'value' => $stats['delivered'], 'pct' => $deliveredRate, 'color' => 'bg-green-500', 'hint' => $deliveredRate.'% of messages'],
                    ['label' => 'Read', 'value' => $stats['read'], 'pct' => $total > 0 ? (int) round(($stats['read'] / $total) * 100) : 0, 'color' => 'bg-teal-500', 'hint' => $readRate.'% of delivered'],
                    ['label' => 'Replied', 'value' => $stats['replyCount'], 'pct' => $replyPct, 'color' => 'bg-purple-500', 'hint' => $stats['uniqueRepliers'].' unique guests'],
                ];
            @endphp

            @foreach($funnel as $step)
            <div>
                <div class="mb-1.5 flex items-end justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-surface-900">{{ $step['label'] }}</p>
                        <p class="text-xs text-surface-400">{{ $step['hint'] }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-base font-semibold text-surface-900">{{ number_format($step['value']) }}</span>
                        <span class="ml-1 text-xs text-surface-400">{{ $step['pct'] }}%</span>
                    </div>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-surface-100">
                    <div class="h-full rounded-full {{ $step['color'] }} transition-all duration-500" style="width: {{ $step['pct'] }}%;"></div>
                </div>
            </div>
            @endforeach

            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-surface-100 pt-4">
                <span class="text-xs text-surface-500"><i class="fas fa-circle-info mr-1 text-surface-400"></i>Read receipts are only available for WhatsApp messages.</span>
                <span class="text-xs font-semibold text-surface-700">{{ $readRate }}% read rate</span>
            </div>
        </div>
    </div>

    <!-- Delivery health -->
    <div class="card-surface overflow-hidden lg:col-span-2">
        <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Delivery health</h2>
                <p class="mt-0.5 text-xs text-surface-500">Current status mix</p>
            </div>
            <span class="badge {{ $failureRate > 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">{{ $failureRate > 0 ? $failureRate.'% failed' : 'Healthy' }}</span>
        </div>

        <div class="flex flex-col items-center p-5 sm:p-6">
            <div class="relative h-36 w-36 rounded-full"
                @if($stats['totalMessages'] > 0)
                style="background: conic-gradient(#14b8a6 0 {{ $readPct }}%, #22c55e {{ $readPct }}% {{ $readPct + $deliveredPct }}%, #f59e0b {{ $readPct + $deliveredPct }}% {{ $readPct + $deliveredPct + $pendingPct }}%, #ef4444 {{ $readPct + $deliveredPct + $pendingPct }}% 100%);"
                @else
                style="background: #f4f4f5;"
                @endif>
                <div class="absolute inset-[10px] flex flex-col items-center justify-center rounded-full bg-white">
                    <span class="text-3xl font-semibold tracking-tight text-surface-900">{{ $deliveredRate }}%</span>
                    <span class="text-xs text-surface-500">delivered</span>
                </div>
            </div>

            <div class="mt-6 grid w-full grid-cols-2 gap-x-6 border-t border-surface-100 pt-2">
                @foreach($statusStats->where('count', '>', 0) as $status)
                <div class="flex items-center justify-between py-2">
                    <span class="flex items-center gap-2 text-sm text-surface-600">
                        <span class="h-2 w-2 rounded-full {{ $status['tone'] }}"></span>{{ $status['label'] }}
                    </span>
                    <span class="text-sm font-semibold text-surface-900">{{ number_format($status['count']) }}</span>
                </div>
                @endforeach
                @if($statusStats->where('count', '>', 0)->isEmpty())
                <div class="col-span-2 py-3 text-center text-xs text-surface-500">No status updates yet.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Types + activity -->
<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <!-- Message types -->
    <div class="card-surface overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Message types</h2>
                <p class="mt-0.5 text-xs text-surface-500">Performance by message purpose</p>
            </div>
            <span class="badge bg-surface-100 text-surface-600">{{ $typeStats->count() }} types</span>
        </div>

        <div class="divide-y divide-surface-100">
            @forelse($typeStats as $type)
            @php
                $meta = $statusMeta[$type['type']] ?? ['icon' => 'fa-comment', 'tone' => 'bg-surface-100 text-surface-500'];
            @endphp
            <div class="px-5 py-3.5 sm:px-6">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-surface-100 text-surface-500"><i class="fas {{ $meta['icon'] }} text-xs"></i></span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-surface-900">{{ $type['label'] }}</p>
                            <p class="text-xs text-surface-400">{{ number_format($type['delivered']) }} delivered &middot; {{ number_format($type['read']) }} read</p>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-sm font-semibold text-surface-900">{{ number_format($type['count']) }}</p>
                        <p class="text-xs text-surface-400">{{ $type['delivery_rate'] }}% delivered</p>
                    </div>
                </div>
                <div class="mt-2.5 h-1 overflow-hidden rounded-full bg-surface-100">
                    <div class="h-full rounded-full {{ $type['failed'] > 0 ? 'bg-amber-400' : 'bg-green-500' }}" style="width: {{ $type['delivery_rate'] }}%;"></div>
                </div>
            </div>
            @empty
            <div class="px-6 py-12 text-center">
                <p class="text-sm font-medium text-surface-700">No messages sent yet</p>
                <p class="mt-1 text-xs text-surface-500">Invitations, reminders and payment thank-yous will appear here.</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- 7 day activity -->
    <div class="card-surface overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="text-sm font-semibold text-surface-900">Last 7 days</h2>
                <p class="mt-0.5 text-xs text-surface-500">Daily messaging activity</p>
            </div>
            <span class="text-xs text-surface-500">{{ number_format($dailyActivity->sum('count')) }} messages</span>
        </div>

        <div class="p-5 sm:p-6">
            <div class="flex h-44 items-end justify-between gap-2 sm:gap-4">
                @foreach($dailyActivity as $day)
                <div class="group flex h-full flex-1 flex-col items-center justify-end gap-2">
                    <div class="relative flex w-full max-w-9 flex-1 items-end justify-center">
                        <div class="w-full rounded-t-md bg-primary-500 transition-colors duration-200 group-hover:bg-primary-600" style="height: {{ max(4, (int) round(($day['count'] / $dailyMax) * 100)) }}%;" title="{{ $day['count'] }} messages">
                            <div class="absolute -top-6 left-1/2 hidden -translate-x-1/2 rounded bg-surface-900 px-1.5 py-0.5 text-[10px] font-semibold text-white group-hover:block">{{ $day['count'] }}</div>
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-medium text-surface-600">{{ $day['label'] }}</p>
                        <p class="text-[11px] text-surface-400">{{ $day['count'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-center gap-x-5 gap-y-1 border-t border-surface-100 pt-4">
                <span class="flex items-center gap-1.5 text-xs text-surface-500"><span class="h-2 w-2 rounded-full bg-primary-500"></span>Messages sent</span>
                <span class="flex items-center gap-1.5 text-xs text-surface-500"><span class="h-2 w-2 rounded-full bg-green-500"></span>{{ number_format($dailyActivity->sum('delivered')) }} delivered in period</span>
            </div>
        </div>
    </div>
</div>

<!-- Replies -->
<div class="card-surface mb-6 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
        <div>
            <h2 class="text-sm font-semibold text-surface-900">Guest replies</h2>
            <p class="mt-0.5 text-xs text-surface-500">Every incoming reply, matched to its guest</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="badge bg-surface-100 text-surface-600">{{ number_format($stats['replyCount']) }} replies</span>
            <span class="badge bg-surface-100 text-surface-600">{{ number_format($stats['uniqueRepliers']) }} guests</span>
        </div>
    </div>

    @if($replies->isEmpty())
    <div class="px-6 py-12 text-center">
        <p class="text-sm font-medium text-surface-700">No replies yet</p>
        <p class="mt-1 text-xs text-surface-500">When guests reply on WhatsApp, their exact message and RSVP outcome will appear here.</p>
    </div>
    @else
    <div class="divide-y divide-surface-100">
        @foreach($recentReplies as $reply)
        @php
            $replyGuest = $reply->guest;
            $rsvp = $replyGuest?->invitation?->rsvp_status;
        @endphp
        <div class="flex items-start gap-3 px-5 py-4 transition hover:bg-surface-50 sm:px-6">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-100 text-xs font-semibold text-surface-600">
                {{ strtoupper(substr($replyGuest?->full_name ?? '?', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-sm font-medium text-surface-900">{{ $replyGuest?->full_name ?? 'Unknown guest' }}</p>
                    <span class="badge bg-surface-100 text-surface-600">{{ $reply->reply_kind ?? 'Reply' }}</span>
                    @if($rsvp === 'attending')
                    <span class="badge bg-green-100 text-green-700">RSVP · Nitahudhuria</span>
                    @elseif($rsvp === 'not_attending')
                    <span class="badge bg-red-100 text-red-700">RSVP · Sitahudhuria</span>
                    @elseif($rsvp === 'pending')
                    <span class="badge bg-amber-100 text-amber-700">RSVP pending</span>
                    @endif
                </div>
                <p class="mt-1.5 text-sm leading-relaxed text-surface-700">“{{ $reply->reply_text }}”</p>
                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-surface-400">
                    <span><i class="fab fa-whatsapp mr-1"></i>{{ $reply->source_phone ?: $replyGuest?->phone_number }}</span>
                    <span>{{ $reply->created_at->format('d M Y, H:i') }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @if($replies->count() > $recentReplies->count())
    <div class="border-t border-surface-100 px-5 py-3 text-center text-xs text-surface-500">
        Showing the latest {{ $recentReplies->count() }} of {{ number_format($replies->count()) }} replies.
    </div>
    @endif
    @endif
</div>

<!-- Outbound messages -->
<div class="card-surface overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-100 px-5 py-4 sm:px-6">
        <div>
            <h2 class="text-sm font-semibold text-surface-900">Outbound message log</h2>
            <p class="mt-0.5 text-xs text-surface-500">Latest delivery events for this event</p>
        </div>
        <span class="badge bg-surface-100 text-surface-600">{{ number_format($stats['totalMessages']) }} total</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-surface-100 text-left">
                    <th class="{{ $th }}">Recipient</th>
                    <th class="{{ $th }}">Type</th>
                    <th class="{{ $th }}">Template</th>
                    <th class="{{ $th }}">Status</th>
                    <th class="{{ $th }}">Latest update</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($recentMessages as $message)
                <tr class="transition hover:bg-surface-50">
                    <td class="{{ $td }}">
                        <div class="font-medium text-surface-900">{{ $message->guest?->full_name ?? 'Unknown guest' }}</div>
                        <div class="mt-0.5 text-xs text-surface-400">{{ $message->to_phone }}</div>
                    </td>
                    <td class="{{ $td }}"><span class="badge bg-surface-100 text-surface-600">{{ \Illuminate\Support\Str::headline($message->message_type) }}</span></td>
                    <td class="{{ $td }} text-surface-500">{{ $message->template_name ?: '—' }}</td>
                    <td class="{{ $td }}"><span class="badge {{ $message->status_badge_class }}">{{ $message->status_label }}</span></td>
                    <td class="{{ $td }} whitespace-nowrap text-surface-500">{{ ($message->status_updated_at ?? $message->created_at)?->format('d M Y, H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center">
                        <p class="text-sm font-medium text-surface-700">No outbound messages yet</p>
                        <p class="mt-1 text-xs text-surface-500">Send invitations to start tracking delivery here.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($messages->count() > $recentMessages->count())
    <div class="border-t border-surface-100 px-5 py-3 text-center text-xs text-surface-500">
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
