@extends('layouts.app')

@section('title', 'Guests - ' . $event->name)

@section('content')
@php
    $canSendWhatsApp = auth()->user()->canSendWhatsApp();
    $accent = $event->card_accent_color ?: '#e11d48';
@endphp

<!-- Event header -->
<div class="relative mb-5 overflow-hidden rounded-2xl border border-surface-200 bg-white p-5 shadow-soft sm:p-6">
    <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full opacity-[0.12] blur-3xl" style="background: {{ $accent }};"></div>
    <div class="pointer-events-none absolute -bottom-24 right-1/3 h-40 w-40 rounded-full bg-gold-300/20 blur-3xl"></div>

    <div class="relative">
        <a href="{{ route('events.show', $event) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-surface-400 transition hover:text-surface-700">
            <i class="fas fa-arrow-left text-[10px]"></i> Back to {{ $event->name }}
        </a>

        <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3.5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-soft" style="background: {{ $accent }}14; color: {{ $accent }};">
                    <i class="fas fa-users text-lg"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-primary-600">Guest directory</p>
                    <h1 class="mt-1 font-display text-2xl font-bold text-surface-900 sm:text-3xl">Guests</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-surface-500">
                        <span><i class="fas fa-calendar-days mr-1.5 text-surface-400"></i>{{ $event->event_date->format('d M Y') }}</span>
                        <span><i class="fas fa-location-dot mr-1.5 text-surface-400"></i>{{ $event->location }}</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-50 px-2.5 py-1 font-semibold text-surface-600">
                            <i class="fas fa-arrow-down-a-z text-[9px] text-surface-400"></i>Sorted A–Z
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:items-center sm:gap-2.5">
                <a href="{{ route('events.guests.create', $event) }}" class="btn btn-primary btn-sm col-span-2 sm:col-span-1">
                    <i class="fas fa-plus"></i> Add Guest
                </a>
                <a href="{{ route('events.guests.export', $event) }}" class="btn btn-outline btn-sm"
                    data-loading-text="Preparing guest export..." data-loading-hint="Your file will download automatically."
                    data-loading-timeout="3000">
                    <i class="fas fa-download"></i> Export
                </a>
                <a href="{{ route('events.guests.import', $event) }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-import"></i> Import
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Guest summary -->
<div class="mb-5 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-surface-400">Total guests</p>
                <p class="mt-2 text-2xl font-bold text-surface-900 sm:text-3xl">{{ number_format($summary['total']) }}</p>
                <p class="mt-1 text-[11px] text-surface-400">On the guest list</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600"><i class="fas fa-users"></i></span>
        </div>
    </div>
    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-green-600/70">Invited</p>
                <p class="mt-2 text-2xl font-bold text-green-600 sm:text-3xl">{{ number_format($summary['invited']) }}</p>
                <p class="mt-1 text-[11px] text-surface-400">Invitations delivered</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 text-green-600"><i class="fas fa-paper-plane"></i></span>
        </div>
    </div>
    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-blue-600/70">Attending</p>
                <p class="mt-2 text-2xl font-bold text-blue-600 sm:text-3xl">{{ number_format($summary['attending']) }}</p>
                <p class="mt-1 text-[11px] text-surface-400">Confirmed via RSVP</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><i class="fas fa-envelope-circle-check"></i></span>
        </div>
    </div>
    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-gold-600/80">Checked in</p>
                <p class="mt-2 text-2xl font-bold text-gold-600 sm:text-3xl">{{ number_format($summary['checked_in']) }}</p>
                <p class="mt-1 text-[11px] text-surface-400">Arrived at the event</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gold-50 text-gold-600"><i class="fas fa-qrcode"></i></span>
        </div>
    </div>
</div>

<!-- Search & Filter -->
<div class="card-surface mb-4 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-surface-100 px-4 py-3 sm:px-5">
        <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-100 text-surface-500"><i class="fas fa-sliders text-xs"></i></div>
            <div>
                <h2 class="text-xs font-semibold text-surface-800">Find a guest</h2>
                <p class="text-[10px] text-surface-400">Search and narrow down the guest list</p>
            </div>
        </div>
        @if(request()->hasAny(['search', 'per_page', 'card_type', 'payment_status', 'rsvp', 'message_status']))
        <span class="rounded-full bg-primary-50 px-2.5 py-1 text-[10px] font-semibold text-primary-600">Filters active</span>
        @endif
    </div>
    <div class="p-4">
    <form method="GET" action="{{ route('events.guests.index', $event) }}" class="filter-bar"
        data-live-filter data-loading-text="Filtering guests...">
        <div class="filter-search">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone, ID or category..."
                class="input-field !py-2" autocomplete="off">
        </div>
        <select name="per_page" class="input-field !py-2 !w-auto">
            <option value="25" {{ (string) $perPage === '25' ? 'selected' : '' }}>25 per page</option>
            <option value="50" {{ (string) $perPage === '50' ? 'selected' : '' }}>50 per page</option>
            <option value="100" {{ (string) $perPage === '100' ? 'selected' : '' }}>100 per page</option>
            <option value="250" {{ (string) $perPage === '250' ? 'selected' : '' }}>250 per page</option>
            <option value="all" {{ (string) $perPage === 'all' ? 'selected' : '' }}>Show all guests</option>
        </select>
        <select name="card_type" class="input-field !py-2 !w-auto">
            <option value="">All Card Types</option>
            <option value="single" {{ request('card_type') === 'single' ? 'selected' : '' }}>Single</option>
            <option value="double" {{ request('card_type') === 'double' ? 'selected' : '' }}>Double</option>
        </select>
        <select name="payment_status" class="input-field !py-2 !w-auto">
            <option value="">All Payment Status</option>
            @foreach(['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid', 'overpaid' => 'Overpaid'] as $value => $label)
            <option value="{{ $value }}" {{ request('payment_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="rsvp" class="input-field !py-2 !w-auto">
            <option value="">All RSVP</option>
            <option value="attending" {{ request('rsvp') === 'attending' ? 'selected' : '' }}>Nitahudhuria</option>
            <option value="not_attending" {{ request('rsvp') === 'not_attending' ? 'selected' : '' }}>Sitahudhuria</option>
            <option value="pending" {{ request('rsvp') === 'pending' ? 'selected' : '' }}>Pending</option>
        </select>
        <select name="message_status" class="input-field !py-2 !w-auto">
            <option value="">All Message Status</option>
            @foreach(['queued' => 'Queued', 'accepted' => 'Accepted', 'sent' => 'Sent', 'delivered' => 'Delivered', 'read' => 'Read', 'failed' => 'Failed'] as $value => $label)
            <option value="{{ $value }}" {{ request('message_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-magnifying-glass"></i> Filter
        </button>
        @if(request()->hasAny(['search', 'per_page', 'card_type', 'payment_status', 'rsvp', 'message_status']))
        <a href="{{ route('events.guests.index', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">Clear</a>
        @endif
    </form>
    </div>
</div>

@if($canSendWhatsApp)
<div id="guestBulkBar" class="hidden card-surface mb-4 border-l-4 border-green-500 px-4 py-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="flex items-center gap-2 text-sm text-surface-700">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-green-100 text-xs font-bold text-green-700"><span id="guestSelectedCount">0</span></span>
            guests selected
        </p>
        <div class="flex items-center gap-3">
            <button type="button" id="bulkWhatsAppBtn" class="btn btn-success btn-sm">
                <i class="fab fa-whatsapp"></i> Send WhatsApp to selected
            </button>
            <button type="button" id="clearGuestSelection" class="text-sm text-surface-500 hover:text-surface-700">Clear</button>
        </div>
    </div>
</div>
@endif

<div class="mb-3 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="font-display text-lg font-bold text-surface-900">Guest list</h2>
        <p class="mt-0.5 text-xs text-surface-400">Payments, RSVP replies and message delivery at a glance</p>
    </div>
    <span class="rounded-full bg-surface-100 px-3 py-1 text-[11px] font-semibold text-surface-500">
        {{ $guests instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? number_format($guests->total()) : number_format($guests->count()) }} guests
    </span>
</div>

<div class="card-surface overflow-hidden">
    @if($guests->isEmpty())
    <div class="px-6 py-16 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-50 to-gold-50 text-primary-400">
            <i class="fas fa-user-plus text-xl"></i>
        </div>
        <h3 class="mt-4 font-display text-lg font-bold text-surface-800">No guests found</h3>
        <p class="mt-1 text-sm text-surface-400">Add guests manually or import them from Excel to get started.</p>
        <div class="mt-5 flex flex-wrap justify-center gap-2">
            <a href="{{ route('events.guests.create', $event) }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add guest</a>
            <a href="{{ route('events.guests.import', $event) }}" class="btn btn-outline btn-sm"><i class="fas fa-file-import"></i> Import list</a>
        </div>
    </div>
    @else
    <div class="overflow-x-auto responsive-table-wrap">
        <table class="w-full responsive-table">
            <thead class="bg-surface-50/80">
                <tr>
                    @if($canSendWhatsApp)
                    <th class="px-4 py-3.5 text-left">
                        <input type="checkbox" id="selectAllGuests" class="h-4 w-4 rounded border-surface-300 text-green-600 focus:ring-green-500" title="Select all guests shown">
                    </th>
                    @endif
                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-surface-400">Guest ID</th>
                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-surface-400">Guest</th>
                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-surface-400">Phone</th>
                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-surface-400">Card</th>
                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-surface-400">Payment</th>
                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-surface-400">RSVP</th>
                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-surface-400">Message</th>
                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-surface-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @foreach($guests as $guest)
                @php
                    $initials = collect(preg_split('/\s+/', trim($guest->full_name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                    $pledgedAmount = (float) $guest->amount_contributed;
                    $paidAmount = (float) $guest->paid_amount;
                    $paymentRate = $pledgedAmount > 0 ? (int) min(100, round(($paidAmount / $pledgedAmount) * 100)) : ($paidAmount > 0 ? 100 : 0);
                    $progressTone = $guest->balance > 0 ? 'bg-amber-500' : 'bg-green-500';
                    $pledgedCardType = $event->cardTypeForAmount($pledgedAmount);
                @endphp
                <tr class="group transition hover:bg-primary-50/30" id="guest-row-{{ $guest->id }}">
                    @if($canSendWhatsApp)
                    <td class="px-4 py-3.5 mobile-select" data-label="">
                        <input type="checkbox" class="guest-checkbox h-4 w-4 rounded border-surface-300 text-green-600 focus:ring-green-500"
                            value="{{ $guest->id }}" aria-label="Select {{ $guest->full_name }}">
                    </td>
                    @endif
                    <td class="px-5 py-3.5" data-label="Guest ID">
                        <span class="inline-flex rounded-md border border-surface-100 bg-surface-50 px-2 py-1 font-mono text-[10px] text-surface-500">{{ $guest->unique_id }}</span>
                    </td>
                    <td class="px-5 py-3.5" data-label="Guest">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-100 to-gold-100 text-[11px] font-bold text-primary-700">{{ $initials }}</div>
                            <div class="min-w-0">
                                <div class="font-semibold text-surface-900">{{ $guest->full_name }}</div>
                                <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-surface-400">
                                    @if($guest->category)<span><i class="fas fa-tag mr-1 text-[9px]"></i>{{ $guest->category }}</span>@endif
                                    @if($guest->table_number)<span><i class="fas fa-chair mr-1 text-[9px]"></i>Table {{ $guest->table_number }}</span>@endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-sm text-surface-600" data-label="Phone">
                        @if($guest->phone_number)
                        <a href="tel:{{ $guest->phone_number }}" class="inline-flex items-center gap-1.5 transition hover:text-primary-600">
                            <i class="fas fa-phone text-[10px] text-surface-300"></i>{{ $guest->phone_number }}
                        </a>
                        @else
                        <span class="text-surface-300">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5" data-label="Card">
                        <span class="badge gap-1.5 {{ $pledgedCardType === 'double' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            <i class="fas {{ $pledgedCardType === 'double' ? 'fa-user-group' : 'fa-user' }} text-[9px]"></i>{{ ucfirst($pledgedCardType) }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5" data-label="Payment">
                        <div class="min-w-[150px]">
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="font-semibold text-surface-900">{{ number_format($paidAmount) }}</span>
                                <span class="text-surface-400">/ {{ number_format($pledgedAmount) }} TZS</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-surface-100">
                                <div class="h-full rounded-full {{ $progressTone }}" style="width: {{ $paymentRate }}%;"></div>
                            </div>
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                <span class="badge {{ $guest->payment_status_badge_class }}">{{ $guest->payment_status_label }}</span>
                                @if($guest->balance > 0)
                                <span class="text-[10px] font-medium text-red-500">Bal. {{ number_format($guest->balance) }}</span>
                                @elseif($guest->balance < 0)
                                <span class="text-[10px] font-medium text-blue-500">Extra {{ number_format(abs($guest->balance)) }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5" data-label="RSVP">
                        @if($guest->invitation)
                        <span class="badge gap-1.5
                            {{ $guest->invitation->rsvp_status === 'attending' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $guest->invitation->rsvp_status === 'not_attending' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $guest->invitation->rsvp_status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $guest->invitation->rsvp_status === 'attending' ? 'bg-green-500' : ($guest->invitation->rsvp_status === 'not_attending' ? 'bg-red-500' : 'bg-amber-500') }}"></span>
                            {{ $guest->invitation->rsvp_status === 'attending' ? 'Nitahudhuria' : ($guest->invitation->rsvp_status === 'not_attending' ? 'Sitahudhuria' : 'Pending') }}
                        </span>
                        @else
                        <span class="text-xs text-surface-300">No invitation</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5" id="message-cell-{{ $guest->id }}" data-label="Message">
                        <div class="min-w-0">
                            @php($latestMessage = $guest->latestWhatsAppMessage)
                            @if($latestMessage)
                            <span class="badge {{ $latestMessage->status_badge_class }}">{{ $latestMessage->status_label }}</span>
                            <div class="mt-1 text-[10px] text-surface-400">
                                {{ ucfirst($latestMessage->message_type) }}
                                @if($latestMessage->status_updated_at)
                                &middot; {{ $latestMessage->status_updated_at->diffForHumans() }}
                                @endif
                            </div>
                            @if($latestMessage->status === 'failed' && $latestMessage->error_message)
                            <div class="mt-1 max-w-[190px] truncate text-[10px] text-red-500" title="{{ $latestMessage->error_message }}">
                                {{ $latestMessage->error_message }}
                            </div>
                            @endif
                            @else
                            <span class="text-xs text-surface-300">Not sent</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-3.5 mobile-actions" data-label="">
                        <div class="flex items-center gap-1 rounded-xl border border-surface-100 bg-surface-50/60 p-1">
                            <button type="button"
                                onclick="openPaymentModal(this)"
                                data-url="{{ route('events.guests.payments.store', [$event, $guest]) }}"
                                data-name="{{ $guest->full_name }}"
                                data-pledged="{{ number_format($guest->amount_contributed) }}"
                                data-paid="{{ number_format($guest->paid_amount) }}"
                                data-balance="{{ number_format($guest->balance) }}"
                                class="icon-action text-amber-600 hover:bg-amber-50 hover:text-amber-700" title="Record Payment">
                                <i class="fas fa-hand-holding-dollar"></i>
                            </button>
                            @if($canSendWhatsApp)
                            <button type="button"
                                onclick="openWhatsAppModal('{{ $guest->id }}', @js($guest->full_name))"
                                data-guest-id="{{ $guest->id }}"
                                data-guest-name="{{ $guest->full_name }}"
                                class="icon-action text-green-600 hover:bg-green-50 hover:text-green-700" title="Send WhatsApp">
                                <i class="fab fa-whatsapp text-lg"></i>
                            </button>
                            @endif
                            <a href="{{ route('events.guests.edit', [$event, $guest]) }}" class="icon-action text-blue-600 hover:bg-blue-50 hover:text-blue-700" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('events.guests.destroy', [$event, $guest]) }}"
                                data-loading-text="Removing guest..." data-loading-hint="Updating the guest list."
                                onsubmit="return confirm('Remove this guest?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="icon-action is-danger text-red-500 hover:bg-red-50 hover:text-red-700" title="Remove">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="border-t border-surface-100 px-5 py-4">
        @if($guests instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-surface-400">
                Showing {{ $guests->firstItem() }}–{{ $guests->lastItem() }} of {{ $guests->total() }} guests
            </p>
            {{ $guests->links() }}
        </div>
        @else
        <p class="text-xs text-surface-400">Showing all {{ $guests->count() }} guests</p>
        @endif
    </div>
    @endif
</div>

@if($canSendWhatsApp)
<!-- WhatsApp Message Modal -->
<div id="whatsappModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="bg-white rounded-t-2xl sm:rounded-xl shadow-xl w-full max-w-md max-h-[92dvh] flex flex-col overflow-hidden animate-fade-in">
        <div class="px-6 py-4 border-b border-surface-100 flex shrink-0 items-center justify-between">
            <h3 id="whatsappModalTitle" class="font-display font-bold text-lg text-surface-900">Send WhatsApp Message</h3>
            <button type="button" onclick="cancelWhatsAppModal()" class="text-surface-400 hover:text-surface-600" title="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="whatsappForm" method="POST" action="" data-no-loading class="flex min-h-0 flex-col"
            data-loading-text="Sending WhatsApp message..." data-loading-hint="Contacting WhatsApp, please wait.">
            @csrf
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <p class="text-sm text-surface-600 mb-4">
                        Sending message to: <span id="modalGuestName" class="font-bold"></span>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-surface-700 mb-1">Message Type</label>
                    <select name="message_type" id="messageType" required class="input-field" onchange="syncTemplateName()">
                        <option value="invitation">Invitation Message</option>
                        <option value="reminder">Reminder Message</option>
                        <option value="other">Other Message</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-surface-700 mb-1">WhatsApp Template</label>
                    @if(!empty($whatsappTemplates) && $whatsappTemplates->isNotEmpty())
                    <select name="template_name" id="templateName" required class="input-field" onchange="syncTemplateLanguage()">
                        @foreach($whatsappTemplates as $tmpl)
                        <option value="{{ $tmpl->name }}" data-language="{{ $tmpl->language }}" data-category="{{ $tmpl->category }}" {{ ($tmpl->name === config('services.whatsapp.template_name')) ? 'selected' : '' }}>
                            {{ $tmpl->name }} ({{ $tmpl->language }}) @if($tmpl->status !== 'APPROVED') [{{ $tmpl->status }}] @endif
                        </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="template_language" id="templateLanguage" value="{{ $whatsappTemplates->firstWhere('name', config('services.whatsapp.template_name'))?->language ?? $whatsappTemplates->first()?->language ?? config('services.whatsapp.template_language') }}">
                    @else
                    <input type="text" name="template_name" id="templateName" required value="{{ config('services.whatsapp.template_name') }}"
                        placeholder="e.g. wedding_reminder" class="input-field">
                    <input type="hidden" name="template_language" id="templateLanguage" value="{{ config('services.whatsapp.template_language') }}">
                    @endif
                    <p class="text-[10px] text-surface-400 mt-1" id="templateHint">
                        Invitation templates need 5 body parameters: guest name, event name, event date, hall & location and time, plus a URL button using the guest invitation code.
                    </p>
                    <p id="reminderHint" class="text-[10px] text-surface-400 mt-1 hidden">
                        Reminder body: guest name, contribution amount, event name and the payment details configured in
                        <code>WHATSAPP_REMINDER_PAYMENT_DETAILS</code>.
                    </p>
                </div>
                <div id="whatsappSendProgress" class="hidden rounded-lg bg-surface-50 p-3">
                    <div class="flex items-center justify-between gap-3 text-xs text-surface-500">
                        <span id="whatsappProgressText">Preparing...</span>
                        <span>Keep this page open</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-surface-200">
                        <div id="whatsappProgressBar" class="h-full rounded-full bg-green-500 transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <div class="px-5 sm:px-6 py-4 bg-surface-50 border-t border-surface-100 flex shrink-0 justify-end gap-3">
                <button type="button" id="whatsappCancelBtn" onclick="cancelWhatsAppModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" id="whatsappSubmitBtn" class="btn btn-primary">
                    <i class="fab fa-whatsapp mr-2"></i> Send Message
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="bg-white rounded-t-2xl sm:rounded-xl shadow-xl w-full max-w-md max-h-[92dvh] flex flex-col overflow-hidden animate-fade-in">
        <div class="px-6 py-4 border-b border-surface-100 flex shrink-0 items-center justify-between">
            <h3 class="font-display font-bold text-lg text-surface-900">Record Payment</h3>
            <button onclick="closePaymentModal()" class="text-surface-400 hover:text-surface-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="paymentForm" method="POST" action="" class="flex min-h-0 flex-col"
            data-loading-text="Recording payment..." data-loading-hint="Updating the guest balance.">
            @csrf
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <p class="text-sm text-surface-600">
                        Recording payment for: <span id="paymentGuestName" class="font-bold text-surface-900"></span>
                    </p>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                        <div class="bg-surface-50 rounded-lg p-2">
                            <div class="text-[10px] uppercase tracking-wide text-surface-400">Pledged</div>
                            <div id="paymentPledged" class="text-sm font-bold text-surface-900"></div>
                        </div>
                        <div class="bg-surface-50 rounded-lg p-2">
                            <div class="text-[10px] uppercase tracking-wide text-surface-400">Paid</div>
                            <div id="paymentPaid" class="text-sm font-bold text-green-600"></div>
                        </div>
                        <div class="bg-surface-50 rounded-lg p-2">
                            <div class="text-[10px] uppercase tracking-wide text-surface-400">Balance</div>
                            <div id="paymentBalance" class="text-sm font-bold text-red-500"></div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-surface-700 mb-1">Amount (TZS) *</label>
                        <input type="number" name="amount" min="1" step="0.01" required class="input-field" placeholder="e.g. 50000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-surface-700 mb-1">Method *</label>
                        <select name="method" required class="input-field">
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-surface-700 mb-1">Payment Date *</label>
                        <input type="date" name="paid_at" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-surface-700 mb-1">Reference</label>
                        <input type="text" name="reference" class="input-field" placeholder="Receipt no.">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-surface-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="input-field" placeholder="Optional payment note"></textarea>
                </div>
            </div>
            <div class="px-5 sm:px-6 py-4 bg-surface-50 border-t border-surface-100 flex shrink-0 justify-end gap-3">
                <button type="button" onclick="closePaymentModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-hand-holding-dollar mr-2"></i> Record Payment
                </button>
            </div>
        </form>
    </div>
</div>

<div id="guestToast" class="hidden fixed bottom-24 right-4 z-[70] max-w-sm rounded-xl px-5 py-3 text-sm font-medium text-white shadow-2xl md:bottom-5 md:right-5"></div>

<script>
    const sendMessageUrlBase = @json(url('/events/'.$event->id.'/guests'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let pendingGuestIds = [];
    let sendingMessages = false;
    let stopMessageSend = false;
    let guestToastTimer = null;

    const templateDefaults = {
        invitation: @json(config('services.whatsapp.template_name')),
        reminder: @json(config('services.whatsapp.reminder_template_name')),
        other: ''
    };

    function syncTemplateLanguage() {
        const select = document.getElementById('templateName');
        const langInput = document.getElementById('templateLanguage');
        if (select && select.tagName === 'SELECT' && langInput) {
            const opt = select.options[select.selectedIndex];
            langInput.value = opt ? (opt.dataset.language || '') : '';
        }
    }

    function syncTemplateName() {
        const type = document.getElementById('messageType').value;
        const input = document.getElementById('templateName');
        const reminderHint = document.getElementById('reminderHint');
        const templateHint = document.getElementById('templateHint');

        if (input.tagName === 'SELECT') {
            const targetName = templateDefaults[type];
            let matched = false;
            if (targetName) {
                for (let i = 0; i < input.options.length; i++) {
                    if (input.options[i].value === targetName) {
                        input.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
            }
            if (!matched && type === 'reminder') {
                for (let i = 0; i < input.options.length; i++) {
                    if (input.options[i].value.toLowerCase().includes('reminder')) {
                        input.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
            }
            syncTemplateLanguage();
        } else {
            if (type !== 'other') {
                input.value = templateDefaults[type] || '';
            }
        }

        reminderHint.classList.toggle('hidden', type !== 'reminder');
        templateHint.classList.toggle('hidden', type === 'reminder');
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function renderMessageStatus(status) {
        if (!status) {
            return '<span class="text-xs text-surface-400">Not sent</span>';
        }

        const details = [status.message_type, status.updated_at].filter(Boolean).map(escapeHtml).join(' &middot; ');
        let html = '<span class="badge ' + escapeHtml(status.badge_class || 'bg-surface-100 text-surface-600') + '">' +
            escapeHtml(status.label || 'Unknown') + '</span>';

        if (details) {
            html += '<div class="text-xs text-surface-400 mt-1">' + details + '</div>';
        }

        if (status.state === 'failed' && status.error_message) {
            const error = escapeHtml(status.error_message);
            html += '<div class="text-xs text-red-500 mt-1 max-w-[190px] truncate" title="' + error + '">' + error + '</div>';
        }

        return html;
    }

    function showGuestToast(message, type) {
        const toast = document.getElementById('guestToast');
        if (!toast) return;

        clearTimeout(guestToastTimer);
        toast.className = 'fixed bottom-24 right-4 z-[70] max-w-sm rounded-xl px-5 py-3 text-sm font-medium text-white shadow-2xl md:bottom-5 md:right-5 ' +
            (type === 'warning' ? 'bg-amber-600' : 'bg-green-600');
        toast.textContent = message;
        toast.classList.remove('hidden');
        guestToastTimer = setTimeout(() => toast.classList.add('hidden'), 9000);
    }

    function resetWhatsAppProgress() {
        document.getElementById('whatsappSendProgress').classList.add('hidden');
        document.getElementById('whatsappProgressBar').style.width = '0%';
        document.getElementById('whatsappProgressText').textContent = 'Preparing...';
        document.getElementById('whatsappCancelBtn').textContent = 'Cancel';
    }

    function openWhatsAppModal(guestId, guestName) {
        const modal = document.getElementById('whatsappModal');
        const nameSpan = document.getElementById('modalGuestName');

        pendingGuestIds = [String(guestId)];
        document.getElementById('whatsappModalTitle').textContent = 'Send WhatsApp Message';
        nameSpan.textContent = guestName;
        resetWhatsAppProgress();
        syncTemplateName();

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function openBulkWhatsAppModal() {
        const ids = Array.from(document.querySelectorAll('.guest-checkbox:checked')).map(checkbox => checkbox.value);
        if (!ids.length) return;

        pendingGuestIds = ids;
        document.getElementById('whatsappModalTitle').textContent = 'Send WhatsApp to ' + ids.length + ' guests';
        document.getElementById('modalGuestName').textContent = ids.length + ' selected guests';
        resetWhatsAppProgress();
        syncTemplateName();

        document.getElementById('whatsappModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeWhatsAppModal() {
        const modal = document.getElementById('whatsappModal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function cancelWhatsAppModal() {
        if (sendingMessages) {
            stopMessageSend = true;
            document.getElementById('whatsappCancelBtn').textContent = 'Stopping after current...';
            return;
        }

        closeWhatsAppModal();
    }

    function syncGuestSelection() {
        const checkboxes = Array.from(document.querySelectorAll('.guest-checkbox'));
        const selected = checkboxes.filter(checkbox => checkbox.checked);
        const selectAll = document.getElementById('selectAllGuests');
        const bulkBar = document.getElementById('guestBulkBar');
        const count = document.getElementById('guestSelectedCount');

        if (count) {
            count.textContent = selected.length;
        }

        if (bulkBar) {
            bulkBar.classList.toggle('hidden', selected.length === 0);
        }

        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && selected.length === checkboxes.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
        }
    }

    function clearGuestSelection() {
        document.querySelectorAll('.guest-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        syncGuestSelection();
    }

    function openPaymentModal(button) {
        const modal = document.getElementById('paymentModal');
        const form = document.getElementById('paymentForm');

        document.getElementById('paymentGuestName').textContent = button.dataset.name;
        document.getElementById('paymentPledged').textContent = button.dataset.pledged;
        document.getElementById('paymentPaid').textContent = button.dataset.paid;
        document.getElementById('paymentBalance').textContent = button.dataset.balance;
        form.action = button.dataset.url;

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    document.getElementById('selectAllGuests')?.addEventListener('change', function (event) {
        document.querySelectorAll('.guest-checkbox').forEach(checkbox => {
            checkbox.checked = event.target.checked;
        });
        syncGuestSelection();
    });

    document.querySelectorAll('.guest-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', syncGuestSelection);
    });

    document.getElementById('bulkWhatsAppBtn')?.addEventListener('click', openBulkWhatsAppModal);
    document.getElementById('clearGuestSelection')?.addEventListener('click', clearGuestSelection);

    document.getElementById('whatsappForm')?.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (sendingMessages) return;

        const ids = pendingGuestIds.slice();
        if (!ids.length) return;

        const messageType = document.getElementById('messageType').value;
        const templateName = document.getElementById('templateName').value.trim();
        const templateLanguage = document.getElementById('templateLanguage')?.value || '';
        if (!templateName) {
            document.getElementById('templateName').focus();
            return;
        }

        const submitButton = document.getElementById('whatsappSubmitBtn');
        const originalButtonHtml = submitButton.innerHTML;
        const progress = document.getElementById('whatsappSendProgress');
        const progressText = document.getElementById('whatsappProgressText');
        const progressBar = document.getElementById('whatsappProgressBar');
        let sent = 0;
        let failed = 0;

        sendingMessages = true;
        stopMessageSend = false;
        submitButton.disabled = true;
        submitButton.classList.add('opacity-70', 'cursor-not-allowed');
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
        progress.classList.remove('hidden');
        progressBar.style.width = '0%';
        progressText.textContent = '0 of ' + ids.length + ' processed';

        for (let index = 0; index < ids.length; index++) {
            if (stopMessageSend) break;

            const guestId = ids[index];
            const statusCell = document.getElementById('message-cell-' + guestId);

            if (statusCell) {
                statusCell.innerHTML = '<span class="text-xs text-surface-400"><i class="fas fa-spinner fa-spin mr-1"></i>Sending...</span>';
            }

            progressText.textContent = 'Sending ' + (index + 1) + ' of ' + ids.length + '...';

            try {
                const response = await fetch(sendMessageUrlBase + '/' + encodeURIComponent(guestId) + '/send-message', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message_type: messageType, template_name: templateName, template_language: templateLanguage }),
                });

                let data = {};
                try {
                    data = await response.json();
                } catch (error) {
                    data = {};
                }

                if (statusCell) {
                    statusCell.innerHTML = renderMessageStatus(data.status);
                }

                if (response.ok && data.success) {
                    sent++;
                } else {
                    failed++;
                }
            } catch (error) {
                failed++;
                if (statusCell) {
                    statusCell.innerHTML = '<span class="badge bg-red-100 text-red-700">Failed</span>';
                }
            }

            progressBar.style.width = Math.round(((index + 1) / ids.length) * 100) + '%';
            progressText.textContent = (index + 1) + ' of ' + ids.length + ' processed';

            if (index < ids.length - 1) {
                await new Promise(resolve => setTimeout(resolve, 250));
            }
        }

        const stopped = stopMessageSend;
        sendingMessages = false;
        stopMessageSend = false;
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonHtml;
        submitButton.classList.remove('opacity-70', 'cursor-not-allowed');
        document.getElementById('whatsappCancelBtn').textContent = 'Cancel';
        closeWhatsAppModal();
        clearGuestSelection();

        let summary = sent + ' message' + (sent === 1 ? '' : 's') + ' accepted' +
            (failed ? ', ' + failed + ' failed' : '');
        summary = (stopped ? 'Stopped. ' : '') + summary + '. Refresh later for delivery and RSVP updates.';
        showGuestToast(summary, failed ? 'warning' : 'success');
    });

    syncGuestSelection();

    // Close on click outside
    window.onclick = function(event) {
        const whatsappModal = document.getElementById('whatsappModal');
        const paymentModal = document.getElementById('paymentModal');

        if (event.target == whatsappModal) {
            cancelWhatsAppModal();
        }

        if (event.target == paymentModal) {
            closePaymentModal();
        }
    }
</script>
@endsection
