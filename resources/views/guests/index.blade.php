@extends('layouts.app')

@section('title', 'Guests - ' . $event->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('events.show', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $event->name }}
    </a>
    <div class="flex flex-wrap items-center justify-between gap-3 mt-2">
        <h1 class="font-display text-2xl font-bold text-surface-900">Guests</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('events.guests.export', $event) }}" class="btn btn-outline btn-sm"
                data-loading-text="Preparing guest export..." data-loading-hint="Your file will download automatically."
                data-loading-timeout="3000">
                <i class="fas fa-download"></i> Export
            </a>
            <a href="{{ route('events.guests.import', $event) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-import"></i> Import
            </a>
            <a href="{{ route('events.guests.create', $event) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Guest
            </a>
        </div>
    </div>
</div>

<!-- Search & Filter -->
<div class="card-surface p-4 mb-4">
    <form method="GET" action="{{ route('events.guests.index', $event) }}" class="flex flex-wrap items-center gap-3"
        data-live-filter data-loading-text="Filtering guests...">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone, ID..."
                class="input-field !py-2" autocomplete="off">
        </div>
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
        @if(request()->hasAny(['search', 'card_type', 'payment_status', 'rsvp', 'message_status']))
        <a href="{{ route('events.guests.index', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">Clear</a>
        @endif
    </form>
</div>

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
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-surface-50">
                <tr>
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
                <tr class="hover:bg-surface-50">
                    <td class="px-6 py-3 text-xs font-mono text-surface-500">{{ $guest->unique_id }}</td>
                    <td class="px-6 py-3">
                        <div class="font-medium text-surface-900">{{ $guest->full_name }}</div>
                        @if($guest->category)<div class="text-xs text-surface-400">{{ $guest->category }}</div>@endif
                    </td>
                    <td class="px-6 py-3 text-sm text-surface-600">{{ $guest->phone_number }}</td>
                    <td class="px-6 py-3">
                        <span class="badge {{ $guest->card_type === 'double' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($guest->card_type) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm">
                        <div class="font-medium text-surface-900">{{ number_format($guest->paid_amount) }} <span class="text-surface-400 font-normal">/ {{ number_format($guest->amount_contributed) }}</span></div>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="badge {{ $guest->payment_status_badge_class }}">{{ $guest->payment_status_label }}</span>
                            @if($guest->balance > 0)
                            <span class="text-xs text-red-500">Bal. {{ number_format($guest->balance) }}</span>
                            @elseif($guest->balance < 0)
                            <span class="text-xs text-blue-500">Extra {{ number_format(abs($guest->balance)) }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-3">
                        @if($guest->invitation)
                        <span class="badge
                            {{ $guest->invitation->rsvp_status === 'attending' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $guest->invitation->rsvp_status === 'not_attending' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $guest->invitation->rsvp_status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}">
                            {{ $guest->invitation->rsvp_status === 'attending' ? 'Nitafika' : ($guest->invitation->rsvp_status === 'not_attending' ? 'Sitafika' : 'Pending') }}
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-3">
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
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            <button type="button"
                                onclick="openPaymentModal(this)"
                                data-url="{{ route('events.guests.payments.store', [$event, $guest]) }}"
                                data-name="{{ $guest->full_name }}"
                                data-pledged="{{ number_format($guest->amount_contributed) }}"
                                data-paid="{{ number_format($guest->paid_amount) }}"
                                data-balance="{{ number_format($guest->balance) }}"
                                class="text-amber-600 hover:text-amber-700" title="Record Payment">
                                <i class="fas fa-hand-holding-dollar"></i>
                            </button>
                            <button type="button"
                                onclick="openWhatsAppModal('{{ $guest->id }}', '{{ $guest->full_name }}')"
                                class="text-green-600 hover:text-green-700" title="Send WhatsApp">
                                <i class="fab fa-whatsapp text-lg"></i>
                            </button>
                            <a href="{{ route('events.guests.edit', [$event, $guest]) }}" class="text-blue-600 hover:text-blue-700" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('events.guests.destroy', [$event, $guest]) }}"
                                data-loading-text="Removing guest..." data-loading-hint="Updating the guest list."
                                onsubmit="return confirm('Remove this guest?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700" title="Remove">
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
        {{ $guests->links() }}
    </div>
    @endif
</div>

<!-- WhatsApp Message Modal -->
<div id="whatsappModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="px-6 py-4 border-b border-surface-100 flex items-center justify-between">
            <h3 class="font-display font-bold text-lg text-surface-900">Send WhatsApp Message</h3>
            <button onclick="closeWhatsAppModal()" class="text-surface-400 hover:text-surface-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="whatsappForm" method="POST" action=""
            data-loading-text="Sending WhatsApp message..." data-loading-hint="Contacting WhatsApp, please wait.">
            @csrf
            <div class="p-6 space-y-4">
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
                    <label class="block text-sm font-medium text-surface-700 mb-1">WhatsApp Template Name</label>
                    <input type="text" name="template_name" id="templateName" required value="{{ config('services.whatsapp.template_name') }}"
                        placeholder="e.g. wedding_reminder" class="input-field">
                    <p class="text-[10px] text-surface-400 mt-1" id="templateHint">
                        Must match the template name in your Meta WhatsApp Business Manager.
                    </p>
                    <p id="reminderHint" class="text-[10px] text-surface-400 mt-1 hidden">
                        Reminder body: guest name, contribution amount, event name and the payment details configured in
                        <code>WHATSAPP_REMINDER_PAYMENT_DETAILS</code>.
                    </p>
                </div>
            </div>
            <div class="px-6 py-4 bg-surface-50 flex justify-end gap-3">
                <button type="button" onclick="closeWhatsAppModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fab fa-whatsapp mr-2"></i> Send Message
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="px-6 py-4 border-b border-surface-100 flex items-center justify-between">
            <h3 class="font-display font-bold text-lg text-surface-900">Record Payment</h3>
            <button onclick="closePaymentModal()" class="text-surface-400 hover:text-surface-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="paymentForm" method="POST" action=""
            data-loading-text="Recording payment..." data-loading-hint="Updating the guest balance.">
            @csrf
            <div class="p-6 space-y-4">
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
            <div class="px-6 py-4 bg-surface-50 flex justify-end gap-3">
                <button type="button" onclick="closePaymentModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-hand-holding-dollar mr-2"></i> Record Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const templateDefaults = {
        invitation: @json(config('services.whatsapp.template_name')),
        reminder: @json(config('services.whatsapp.reminder_template_name')),
        other: ''
    };

    function syncTemplateName() {
        const type = document.getElementById('messageType').value;
        const input = document.getElementById('templateName');
        const reminderHint = document.getElementById('reminderHint');
        const templateHint = document.getElementById('templateHint');

        if (type !== 'other') {
            input.value = templateDefaults[type] || '';
        }

        reminderHint.classList.toggle('hidden', type !== 'reminder');
        templateHint.classList.toggle('hidden', type === 'reminder');
    }

    function openWhatsAppModal(guestId, guestName) {
        const modal = document.getElementById('whatsappModal');
        const form = document.getElementById('whatsappForm');
        const nameSpan = document.getElementById('modalGuestName');

        nameSpan.textContent = guestName;
        form.action = `/events/{{ $event->id }}/guests/${guestId}/send-message`;

        syncTemplateName();

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeWhatsAppModal() {
        const modal = document.getElementById('whatsappModal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
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

    // Close on click outside
    window.onclick = function(event) {
        const whatsappModal = document.getElementById('whatsappModal');
        const paymentModal = document.getElementById('paymentModal');

        if (event.target == whatsappModal) {
            closeWhatsAppModal();
        }

        if (event.target == paymentModal) {
            closePaymentModal();
        }
    }
</script>
@endsection
