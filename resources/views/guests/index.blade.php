@extends('layouts.app')

@section('title', 'Guests - ' . $event->name)

@section('content')
@php($canSendWhatsApp = auth()->user()->canSendWhatsApp())
<div class="mb-6">
    <a href="{{ route('events.show', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $event->name }}
    </a>
    <div class="flex flex-wrap items-center justify-between gap-3 mt-2">
        <div class="flex items-baseline gap-2">
            <h1 class="font-display text-2xl font-bold text-surface-900">Guests</h1>
            <span class="text-xs text-surface-400">A–Z</span>
        </div>
        <div class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:items-center sm:gap-3">
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

@unless($canSendWhatsApp)
<div class="mb-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    <i class="fas fa-lock mt-0.5"></i>
    <p>WhatsApp sending is <strong>view-only</strong> for your account. You can still add guests, record payments, edit details and see delivery statuses. Ask an administrator to grant sending permission.</p>
</div>
@endunless

<!-- Search & Filter -->
<div class="card-surface p-4 mb-4">
    <form method="GET" action="{{ route('events.guests.index', $event) }}" class="filter-bar"
        data-live-filter data-loading-text="Filtering guests...">
        <div class="filter-search">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone, ID..."
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
            <option value="attending" {{ request('rsvp') === 'attending' ? 'selected' : '' }}>Nitafika</option>
            <option value="not_attending" {{ request('rsvp') === 'not_attending' ? 'selected' : '' }}>Sitafika</option>
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

@if($canSendWhatsApp)
<div id="guestBulkBar" class="hidden card-surface px-4 py-3 mb-4 border-l-4 border-green-500">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-surface-700">
            <span id="guestSelectedCount" class="font-bold text-green-700">0</span> guests selected
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

<div class="card-surface overflow-hidden">
    @if($guests->isEmpty())
    <div class="px-6 py-12 text-center">
        <div class="w-16 h-16 bg-surface-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-user-plus text-surface-400 text-xl"></i>
        </div>
        <h3 class="text-surface-700 font-medium">No guests yet</h3>
        <p class="text-surface-400 text-sm mt-1">Add guests manually or import from Excel</p>
    </div>
    @else
    <div class="overflow-x-auto responsive-table-wrap">
        <table class="w-full responsive-table">
            <thead class="bg-surface-50">
                <tr>
                    @if($canSendWhatsApp)
                    <th class="px-4 py-3 text-left">
                        <input type="checkbox" id="selectAllGuests" class="h-4 w-4 rounded border-surface-300 text-green-600 focus:ring-green-500" title="Select all guests shown">
                    </th>
                    @endif
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Card</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Payment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">RSVP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Message</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @foreach($guests as $guest)
                <tr class="hover:bg-surface-50" id="guest-row-{{ $guest->id }}">
                    @if($canSendWhatsApp)
                    <td class="px-4 py-3 mobile-select" data-label="">
                        <input type="checkbox" class="guest-checkbox h-4 w-4 rounded border-surface-300 text-green-600 focus:ring-green-500"
                            value="{{ $guest->id }}" aria-label="Select {{ $guest->full_name }}">
                    </td>
                    @endif
                    <td class="px-6 py-3 text-xs font-mono text-surface-500" data-label="Guest ID">{{ $guest->unique_id }}</td>
                    <td class="px-6 py-3" data-label="Guest">
                        <div class="min-w-0">
                            <div class="font-medium text-surface-900">{{ $guest->full_name }}</div>
                            @if($guest->category)<div class="text-xs text-surface-400">{{ $guest->category }}</div>@endif
                        </div>
                    </td>
                    <td class="px-6 py-3 text-sm text-surface-600" data-label="Phone">{{ $guest->phone_number }}</td>
                    <td class="px-6 py-3" data-label="Card">
                        @php($pledgedCardType = $event->cardTypeForAmount((float) $guest->amount_contributed))
                        <span class="badge {{ $pledgedCardType === 'double' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($pledgedCardType) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm" data-label="Payment">
                        <div class="min-w-0">
                            <div class="font-medium text-surface-900">{{ number_format($guest->paid_amount) }} <span class="text-surface-400 font-normal">/ {{ number_format($guest->amount_contributed) }}</span></div>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <span class="badge {{ $guest->payment_status_badge_class }}">{{ $guest->payment_status_label }}</span>
                                @if($guest->balance > 0)
                                <span class="text-xs text-red-500">Bal. {{ number_format($guest->balance) }}</span>
                                @elseif($guest->balance < 0)
                                <span class="text-xs text-blue-500">Extra {{ number_format(abs($guest->balance)) }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-3" data-label="RSVP">
                        @if($guest->invitation)
                        <span class="badge
                            {{ $guest->invitation->rsvp_status === 'attending' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $guest->invitation->rsvp_status === 'not_attending' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $guest->invitation->rsvp_status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}">
                            {{ $guest->invitation->rsvp_status === 'attending' ? 'Nitafika' : ($guest->invitation->rsvp_status === 'not_attending' ? 'Sitafika' : 'Pending') }}
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-3" id="message-cell-{{ $guest->id }}" data-label="Message">
                        <div class="min-w-0">
                            @php($latestMessage = $guest->latestWhatsAppMessage)
                            @if($latestMessage)
                            <span class="badge {{ $latestMessage->status_badge_class }}">{{ $latestMessage->status_label }}</span>
                            <div class="text-xs text-surface-400 mt-1">
                                {{ ucfirst($latestMessage->message_type) }}
                                @if($latestMessage->status_updated_at)
                                &middot; {{ $latestMessage->status_updated_at->diffForHumans() }}
                                @endif
                            </div>
                            @if($latestMessage->status === 'failed' && $latestMessage->error_message)
                            <div class="text-xs text-red-500 mt-1 max-w-[190px] truncate" title="{{ $latestMessage->error_message }}">
                                {{ $latestMessage->error_message }}
                            </div>
                            @endif
                            @else
                            <span class="text-xs text-surface-400">Not sent</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-3 mobile-actions" data-label="">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <button type="button"
                                onclick="openPaymentModal(this)"
                                data-url="{{ route('events.guests.payments.store', [$event, $guest]) }}"
                                data-name="{{ $guest->full_name }}"
                                data-pledged="{{ number_format($guest->amount_contributed) }}"
                                data-paid="{{ number_format($guest->paid_amount) }}"
                                data-balance="{{ number_format($guest->balance) }}"
                                class="icon-action text-amber-600 hover:text-amber-700" title="Record Payment">
                                <i class="fas fa-hand-holding-dollar"></i>
                            </button>
                            @if($canSendWhatsApp)
                            <button type="button"
                                onclick="openWhatsAppModal('{{ $guest->id }}', @js($guest->full_name))"
                                data-guest-id="{{ $guest->id }}"
                                data-guest-name="{{ $guest->full_name }}"
                                class="icon-action text-green-600 hover:text-green-700" title="Send WhatsApp">
                                <i class="fab fa-whatsapp text-lg"></i>
                            </button>
                            @endif
                            <a href="{{ route('events.guests.edit', [$event, $guest]) }}" class="icon-action text-blue-600 hover:text-blue-700" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('events.guests.destroy', [$event, $guest]) }}"
                                data-loading-text="Removing guest..." data-loading-hint="Updating the guest list."
                                onsubmit="return confirm('Remove this guest?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="icon-action is-danger text-red-500 hover:text-red-700" title="Remove">
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

    <div class="px-6 py-4 border-t border-surface-100">
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
                        Invitation templates need 4 body parameters: guest name, guest ID, event date and public invitation link.
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
