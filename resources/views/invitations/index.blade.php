@extends('layouts.app')

@section('title', 'Invitations - ' . $event->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('events.show', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $event->name }}
    </a>
    <div class="flex flex-wrap items-center justify-between gap-3 mt-2">
        <h1 class="font-display text-2xl font-bold text-surface-900">Invitations</h1>
        <button type="button" id="sendAllPendingBtn" class="btn btn-success">
            <i class="fab fa-whatsapp"></i> Send All Pending
        </button>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="card-surface p-4 text-center">
        <div class="text-2xl font-bold text-surface-900" id="stat-total">{{ $stats['total'] }}</div>
        <div class="text-xs text-surface-500">Total</div>
    </div>
    <div class="card-surface p-4 text-center">
        <div class="text-2xl font-bold text-green-600" id="stat-sent">{{ $stats['sent'] }}</div>
        <div class="text-xs text-surface-500">Sent</div>
    </div>
    <div class="card-surface p-4 text-center">
        <div class="text-2xl font-bold text-yellow-600" id="stat-pending">{{ $stats['pending'] }}</div>
        <div class="text-xs text-surface-500">Pending</div>
    </div>
    <div class="card-surface p-4 text-center">
        <div class="text-2xl font-bold text-red-600" id="stat-failed">{{ $stats['failed'] }}</div>
        <div class="text-xs text-surface-500">Failed</div>
    </div>
</div>

<div class="card-surface p-4 mb-4">
    <form method="GET" action="{{ route('events.invitations.index', $event) }}" class="flex flex-wrap items-center gap-3"
        data-live-filter data-loading-text="Filtering invitations...">
        <div class="flex-1 min-w-[220px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone, ID..."
                class="input-field !py-2" autocomplete="off">
        </div>
        <select name="send_status" class="input-field !py-2 !w-auto">
            <option value="">All Send Status</option>
            @foreach(['pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed', 'delivered' => 'Delivered'] as $value => $label)
            <option value="{{ $value }}" {{ request('send_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="rsvp" class="input-field !py-2 !w-auto">
            <option value="">All RSVP</option>
            <option value="attending" {{ request('rsvp') === 'attending' ? 'selected' : '' }}>Nitafika</option>
            <option value="not_attending" {{ request('rsvp') === 'not_attending' ? 'selected' : '' }}>Sitafika</option>
            <option value="pending" {{ request('rsvp') === 'pending' ? 'selected' : '' }}>Pending</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-magnifying-glass"></i> Filter
        </button>
        @if(request()->hasAny(['search', 'send_status', 'rsvp']))
        <a href="{{ route('events.invitations.index', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">Clear</a>
        @endif
    </form>
    <p class="mt-2 text-[11px] text-surface-400">
        Guests appear here after they have paid at least {{ number_format($event->single_card_minimum) }} TZS.
        Paid amounts of at least {{ number_format($event->double_card_minimum) }} TZS are shown as Double.
    </p>
</div>

<div id="bulkInvitationProgress" class="hidden card-surface p-4 mb-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p id="bulkInvitationProgressText" class="text-sm font-medium text-surface-800">Preparing invitations...</p>
            <p class="text-xs text-surface-400 mt-0.5">Keep this page open while messages are sent one by one.</p>
        </div>
        <button type="button" id="stopBulkInvitationsBtn" class="text-sm font-medium text-red-600 hover:text-red-700">
            Stop after current
        </button>
    </div>
    <div class="mt-3 h-2 overflow-hidden rounded-full bg-surface-200">
        <div id="bulkInvitationProgressBar" class="h-full rounded-full bg-green-500 transition-all" style="width: 0%"></div>
    </div>
</div>

<div class="card-surface overflow-hidden">
    @if($invitations->isEmpty())
    <div class="px-6 py-12 text-center text-surface-400">
        <p>No eligible invitations found.</p>
        <p class="mt-1 text-xs">Guests appear here once their paid amount meets the event's single-card minimum.</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-surface-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Guest</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Card</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Send Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">RSVP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">QR Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @foreach($invitations as $invitation)
                <tr class="hover:bg-surface-50" id="row-{{ $invitation->id }}">
                    <td class="px-6 py-3">
                        <div class="font-medium text-surface-900">{{ $invitation->guest->full_name }}</div>
                        <div class="text-xs text-surface-400">{{ $invitation->guest->unique_id }}</div>
                    </td>
                    <td class="px-6 py-3 text-sm text-surface-600">{{ $invitation->guest->phone_number }}</td>
                    <td class="px-6 py-3" id="card-cell-{{ $invitation->id }}">
                        @php($paidCardType = $event->cardTypeForAmount((float) $invitation->guest->paid_amount))
                        <span class="badge {{ $paidCardType === 'double' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($paidCardType) }}
                        </span>
                        <div class="text-[10px] text-surface-400 mt-1">Paid {{ number_format($invitation->guest->paid_amount) }}</div>
                        <div class="mt-2" id="card-thumb-{{ $invitation->id }}">
                            @if($invitation->card_image_path)
                            <img src="{{ $invitation->card_image_url }}" alt="Invitation card for {{ $invitation->guest->full_name }}"
                                class="h-14 w-10 rounded border border-surface-200 object-cover">
                            @else
                            <span class="text-[10px] text-surface-400">No card image</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-3" id="status-cell-{{ $invitation->id }}">
                        <span class="badge
                            {{ $invitation->send_status === 'sent' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $invitation->send_status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            {{ $invitation->send_status === 'failed' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $invitation->send_status === 'delivered' ? 'bg-blue-100 text-blue-700' : '' }}">
                            {{ ucfirst($invitation->send_status) }}
                        </span>
                        @if($invitation->sent_at)
                        <div class="text-xs text-surface-400 mt-1">{{ $invitation->sent_at->format('d/m H:i') }}</div>
                        @endif
                        @if($invitation->latestInvitationMessage)
                        <div class="mt-1">
                            <span class="badge {{ $invitation->latestInvitationMessage->status_badge_class }}">
                                {{ $invitation->latestInvitationMessage->status_label }}
                            </span>
                        </div>
                        @endif
                    </td>
                    <td class="px-6 py-3">
                        <span class="badge
                            {{ $invitation->rsvp_status === 'attending' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $invitation->rsvp_status === 'not_attending' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $invitation->rsvp_status === 'pending' ? 'bg-surface-100 text-surface-600' : '' }}">
                            {{ $invitation->rsvp_status === 'attending' ? 'Nitafika' : ($invitation->rsvp_status === 'not_attending' ? 'Sitafika' : 'Pending') }}
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <div class="qr-code-mini" data-value="{{ $invitation->qr_code_data }}"></div>
                    </td>
                    <td class="px-6 py-3" id="action-cell-{{ $invitation->id }}">
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('events.invitations.card', [$event, $invitation->guest]) }}" target="_blank" class="text-blue-600 hover:text-blue-700 text-sm" title="View generated card">
                                <i class="fas fa-id-card"></i>
                            </a>
                            @if($invitation->send_status === 'pending')
                            <button type="button" class="prepare-invitation-btn text-green-600 hover:text-green-700 text-sm font-medium"
                                data-invitation-id="{{ $invitation->id }}"
                                data-guest-name="{{ $invitation->guest->full_name }}"
                                data-guest-unique-id="{{ $invitation->guest->unique_id }}"
                                data-phone="{{ $invitation->guest->phone_number }}"
                                data-action-url="{{ route('events.invitations.send', [$event, $invitation]) }}"
                                data-card-image-url="{{ $invitation->card_image_url }}"
                                title="Upload a card, preview, and send">
                                <i class="fas fa-upload mr-1"></i> <span>Send</span>
                            </button>
                            @elseif($invitation->send_status === 'failed')
                            <button type="button" class="prepare-invitation-btn text-amber-600 hover:text-amber-700 text-sm font-medium"
                                data-invitation-id="{{ $invitation->id }}"
                                data-guest-name="{{ $invitation->guest->full_name }}"
                                data-guest-unique-id="{{ $invitation->guest->unique_id }}"
                                data-phone="{{ $invitation->guest->phone_number }}"
                                data-action-url="{{ route('events.invitations.resend', [$event, $invitation]) }}"
                                data-card-image-url="{{ $invitation->card_image_url }}"
                                title="Upload a card, preview, and retry">
                                <i class="fas fa-rotate-right mr-1"></i> <span>Retry</span>
                            </button>
                            @else
                            <span class="text-surface-400 text-sm" title="Invitation already sent"><i class="fas fa-check"></i></span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-surface-100">
        {{ $invitations->links() }}
    </div>
    @endif
</div>

<div id="invitationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-xl overflow-hidden" style="max-height: 92vh;">
        <div class="px-6 py-4 border-b border-surface-100 flex items-center justify-between">
            <div>
                <h3 class="font-display font-bold text-lg text-surface-900">Prepare Invitation</h3>
                <p class="text-xs text-surface-400 mt-0.5">Upload the guest card, review the message, then send.</p>
            </div>
            <button type="button" onclick="closeInvitationModal()" class="text-surface-400 hover:text-surface-600" title="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="invitationForm" method="POST" action="" enctype="multipart/form-data" data-no-loading>
            @csrf
            <div class="p-6 space-y-5 overflow-y-auto" style="max-height: calc(92vh - 145px);">
                <div class="rounded-lg bg-surface-50 p-3">
                    <p class="text-sm text-surface-600">
                        Sending invitation to <span id="invitationGuestName" class="font-bold text-surface-900"></span>
                    </p>
                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-surface-400">
                        <span id="invitationGuestPhone"></span>
                        <span id="invitationGuestId"></span>
                    </div>
                </div>

                <div>
                    <label for="invitationCardInput" class="block text-sm font-medium text-surface-700 mb-1">
                        Invitation card image
                    </label>
                    <input type="file" name="card_image" id="invitationCardInput" accept="image/jpeg,image/png"
                        class="block w-full text-sm text-surface-500 file:mr-3 file:rounded-lg file:border-0 file:bg-green-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-green-700 hover:file:bg-green-100">
                    <p class="text-[11px] text-surface-400 mt-1">
                        JPG or PNG up to 5 MB. This image becomes the WhatsApp header for this guest.
                    </p>
                </div>

                <div class="rounded-xl border border-surface-200 bg-surface-50 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <span id="invitationCardPreviewLabel" class="text-xs font-medium uppercase tracking-wide text-surface-400">Card preview</span>
                        <a id="invitationCurrentCardLink" href="#" target="_blank" class="hidden text-xs text-blue-600 hover:text-blue-700">
                            Open current image
                        </a>
                    </div>
                    <div id="invitationCardPreviewWrap" class="hidden mt-3">
                        <img id="invitationCardPreview" src="" alt="Invitation card preview"
                            class="mx-auto max-h-56 rounded-lg border border-surface-200 bg-white object-contain shadow-sm">
                    </div>
                    <div id="invitationCardEmpty" class="mt-3 rounded-lg border border-dashed border-surface-300 bg-white px-4 py-8 text-center text-xs text-surface-400">
                        No guest card uploaded yet. The event cover or default image will be used.
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <span class="text-sm font-medium text-surface-700">WhatsApp message preview</span>
                        <span id="invitationTemplateName" class="text-[10px] text-surface-400"></span>
                    </div>
                    <div class="rounded-xl p-3" style="background-color: #e5ddd5;">
                        <div class="ml-auto max-w-[96%] rounded-lg p-2 shadow-sm" style="background-color: #dcf8c6;">
                            <img id="invitationMessageImage" src="" alt="" class="hidden mb-2 max-h-64 w-full rounded object-cover">
                            <p id="invitationMessageText" class="whitespace-pre-line text-sm text-surface-800"></p>
                            <div class="mt-1 text-right text-[10px] text-surface-500">WhatsApp preview</div>
                        </div>
                    </div>
                    <p class="text-[10px] text-surface-400 mt-1">
                        The wording is controlled by your approved Meta template. This preview shows the guest details and header image that will be sent.
                    </p>
                </div>

                <div id="invitationSendProgress" class="hidden rounded-lg bg-surface-50 p-3">
                    <div class="flex items-center gap-2 text-xs text-surface-500">
                        <i class="fas fa-spinner fa-spin text-green-600"></i>
                        <span id="invitationSendProgressText">Uploading card and sending invitation...</span>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-surface-50 border-t border-surface-100 flex justify-end gap-3">
                <button type="button" onclick="closeInvitationModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" id="invitationSubmitBtn" class="btn btn-success">
                    <i class="fab fa-whatsapp mr-2"></i> Send Invitation
                </button>
            </div>
        </form>
    </div>
</div>

<div id="invitationToast" class="hidden fixed bottom-5 right-5 z-[80] max-w-sm rounded-xl px-5 py-3 text-sm font-medium text-white shadow-2xl"></div>
@endsection

@push('scripts')
<script>
loadScriptWithFallback([
    'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',
    'https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js',
    'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js'
], function () {
    document.querySelectorAll('.qr-code-mini').forEach(el => {
        new QRCode(el, {
            text: el.dataset.value,
            width: 40,
            height: 40,
            colorDark: '#1a1a1a',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    });
});

const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const invitationPreviewData = {
    eventName: @json($event->name),
    eventDate: @json($event->event_date->format('l, d M Y')),
    eventTime: @json($event->event_time),
    location: @json($event->location),
    hall: @json($event->hall),
    coverImageUrl: @json($event->cover_image ? asset('storage/'.$event->cover_image) : config('services.whatsapp.default_image_url')),
    templateName: @json(config('services.whatsapp.template_name')),
    pendingUrl: @json(route('events.invitations.pending', $event)),
};

let activeInvitation = null;
let invitationPreviewObjectUrl = null;
let sendingInvitation = false;
let sendingAllInvitations = false;
let stopBulkInvitations = false;
let invitationToastTimer = null;

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

function showInvitationToast(message, type) {
    const toast = document.getElementById('invitationToast');
    clearTimeout(invitationToastTimer);
    toast.className = 'fixed bottom-5 right-5 z-[80] max-w-sm rounded-xl px-5 py-3 text-sm font-medium text-white shadow-2xl ' +
        (type === 'warning' ? 'bg-amber-600' : 'bg-green-600');
    toast.textContent = message;
    toast.classList.remove('hidden');
    invitationToastTimer = setTimeout(() => toast.classList.add('hidden'), 10000);
}

function updateStatCounters(stats) {
    if (!stats) return;
    const map = { total: 'stat-total', sent: 'stat-sent', pending: 'stat-pending', failed: 'stat-failed' };
    Object.entries(map).forEach(([key, id]) => {
        const el = document.getElementById(id);
        if (el && typeof stats[key] !== 'undefined') el.textContent = stats[key];
    });
}

function updateInvitationThumbnail(invitationId, url) {
    const container = document.getElementById('card-thumb-' + invitationId);
    if (!container || !url) return;

    container.innerHTML = '';
    const image = document.createElement('img');
    image.src = url;
    image.alt = 'Invitation card';
    image.className = 'h-14 w-10 rounded border border-surface-200 object-cover';
    container.appendChild(image);
}

function invitationStatusBadge(data) {
    const whatsappStatus = data.invitation?.whatsapp_status;
    if (!whatsappStatus) return '';

    return '<div class="mt-1"><span class="badge ' + escapeHtml(whatsappStatus.badge_class) + '">' +
        escapeHtml(whatsappStatus.label) + '</span></div>';
}

function updateInvitationRow(invitationId, data) {
    const statusCell = document.getElementById('status-cell-' + invitationId);
    const actionCell = document.getElementById('action-cell-' + invitationId);
    if (!statusCell || !actionCell) return;

    const existingLinks = Array.from(actionCell.querySelectorAll('a')).map(link => link.outerHTML).join('');
    const sentAt = data.invitation?.sent_at
        ? '<div class="text-xs text-surface-400 mt-1">' + escapeHtml(data.invitation.sent_at) + '</div>'
        : '';

    if (data.success) {
        statusCell.innerHTML = '<span class="badge bg-green-100 text-green-700">Sent</span>' +
            sentAt + invitationStatusBadge(data);

        const imageLink = data.invitation?.card_image_url
            ? '<a href="' + escapeHtml(data.invitation.card_image_url) + '" target="_blank" class="text-purple-600 hover:text-purple-700 text-sm" title="View sent card image"><i class="fas fa-image"></i></a>'
            : '';

        actionCell.innerHTML = '<div class="flex flex-wrap items-center gap-3">' + existingLinks + imageLink +
            '<span class="text-surface-400 text-sm" title="Invitation sent"><i class="fas fa-check"></i></span></div>';
    } else {
        statusCell.innerHTML = '<span class="badge bg-red-100 text-red-700">Failed</span>' +
            sentAt + invitationStatusBadge(data);
    }

    if (data.invitation?.card_image_url) {
        updateInvitationThumbnail(invitationId, data.invitation.card_image_url);
    }
}

function invitationPreviewText(guestName, guestUniqueId) {
    const lines = [
        'Hello ' + guestName + ',',
        'You are invited to ' + invitationPreviewData.eventName + '.',
        'Date: ' + invitationPreviewData.eventDate,
    ];

    if (invitationPreviewData.eventTime) {
        lines.push('Time: ' + invitationPreviewData.eventTime);
    }

    lines.push('Venue: ' + invitationPreviewData.location + (invitationPreviewData.hall ? ' - ' + invitationPreviewData.hall : ''));
    lines.push('Guest ID: ' + guestUniqueId);
    lines.push('');
    lines.push('Please present your QR code at the entrance.');

    return lines.join('\n');
}

function setInvitationImage(url, label) {
    const imageWrap = document.getElementById('invitationCardPreviewWrap');
    const cardImage = document.getElementById('invitationCardPreview');
    const emptyState = document.getElementById('invitationCardEmpty');
    const messageImage = document.getElementById('invitationMessageImage');

    document.getElementById('invitationCardPreviewLabel').textContent = label;

    if (url) {
        cardImage.src = url;
        messageImage.src = url;
        imageWrap.classList.remove('hidden');
        emptyState.classList.add('hidden');
        messageImage.classList.remove('hidden');
    } else {
        cardImage.removeAttribute('src');
        messageImage.removeAttribute('src');
        imageWrap.classList.add('hidden');
        emptyState.classList.remove('hidden');
        messageImage.classList.add('hidden');
    }
}

function resetInvitationModalState() {
    if (invitationPreviewObjectUrl) {
        URL.revokeObjectURL(invitationPreviewObjectUrl);
        invitationPreviewObjectUrl = null;
    }

    document.getElementById('invitationCardInput').value = '';
    document.getElementById('invitationSendProgress').classList.add('hidden');

    const submitButton = document.getElementById('invitationSubmitBtn');
    submitButton.disabled = false;
    submitButton.classList.remove('opacity-70', 'cursor-not-allowed');
    submitButton.innerHTML = '<i class="fab fa-whatsapp mr-2"></i> Send Invitation';
}

function openInvitationModal(button) {
    activeInvitation = {
        id: button.dataset.invitationId,
        guestName: button.dataset.guestName,
        guestUniqueId: button.dataset.guestUniqueId,
        phone: button.dataset.phone,
        actionUrl: button.dataset.actionUrl,
        cardImageUrl: button.dataset.cardImageUrl || null,
    };

    document.getElementById('invitationForm').action = activeInvitation.actionUrl;
    document.getElementById('invitationGuestName').textContent = activeInvitation.guestName;
    document.getElementById('invitationGuestPhone').textContent = activeInvitation.phone || 'No phone';
    document.getElementById('invitationGuestId').textContent = activeInvitation.guestUniqueId;
    document.getElementById('invitationTemplateName').textContent = 'Template: ' + invitationPreviewData.templateName;
    document.getElementById('invitationMessageText').textContent =
        invitationPreviewText(activeInvitation.guestName, activeInvitation.guestUniqueId);

    const currentLink = document.getElementById('invitationCurrentCardLink');
    if (activeInvitation.cardImageUrl) {
        currentLink.href = activeInvitation.cardImageUrl;
        currentLink.classList.remove('hidden');
        setInvitationImage(activeInvitation.cardImageUrl, 'Uploaded guest card');
    } else {
        currentLink.classList.add('hidden');
        setInvitationImage(invitationPreviewData.coverImageUrl, 'Event cover fallback');
    }

    resetInvitationModalState();
    document.getElementById('invitationModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeInvitationModal() {
    if (sendingInvitation) return;

    document.getElementById('invitationModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    resetInvitationModalState();
}

document.getElementById('invitationCardInput')?.addEventListener('change', function (event) {
    const file = event.target.files[0];
    const currentLink = document.getElementById('invitationCurrentCardLink');

    if (!file) {
        const fallback = activeInvitation?.cardImageUrl || invitationPreviewData.coverImageUrl;
        currentLink.classList.toggle('hidden', !activeInvitation?.cardImageUrl);
        setInvitationImage(fallback, activeInvitation?.cardImageUrl ? 'Uploaded guest card' : 'Event cover fallback');
        return;
    }

    if (invitationPreviewObjectUrl) {
        URL.revokeObjectURL(invitationPreviewObjectUrl);
    }

    invitationPreviewObjectUrl = URL.createObjectURL(file);
    currentLink.classList.add('hidden');
    setInvitationImage(invitationPreviewObjectUrl, 'Selected guest card');
});

document.getElementById('invitationForm')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (sendingInvitation || !activeInvitation) return;

    const form = event.currentTarget;
    const submitButton = document.getElementById('invitationSubmitBtn');
    const progress = document.getElementById('invitationSendProgress');
    const progressText = document.getElementById('invitationSendProgressText');
    const guestName = activeInvitation.guestName;
    const invitationId = activeInvitation.id;

    sendingInvitation = true;
    submitButton.disabled = true;
    submitButton.classList.add('opacity-70', 'cursor-not-allowed');
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
    progress.classList.remove('hidden');
    progressText.textContent = 'Uploading card and sending invitation...';

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: new FormData(form),
        });

        let data = {};
        try {
            data = await response.json();
        } catch (error) {
            data = {};
        }

        if (response.ok && data.success) {
            updateStatCounters(data.stats);
            updateInvitationRow(invitationId, data);
            sendingInvitation = false;
            closeInvitationModal();
            showInvitationToast('Invitation sent to ' + guestName + '. Refresh later for delivery and RSVP updates.', 'success');
            return;
        }

        const validationMessage = data.errors ? Object.values(data.errors)[0]?.[0] : null;
        progressText.textContent = validationMessage || data.message || 'Could not send this invitation.';
        showInvitationToast(validationMessage || data.message || 'Could not send this invitation.', 'warning');
    } catch (error) {
        progressText.textContent = 'Network error while sending the invitation.';
        showInvitationToast('Network error while sending. Please try again.', 'warning');
    } finally {
        sendingInvitation = false;
        submitButton.disabled = false;
        submitButton.classList.remove('opacity-70', 'cursor-not-allowed');
        submitButton.innerHTML = '<i class="fab fa-whatsapp mr-2"></i> Send Invitation';
    }
});

document.querySelector('tbody')?.addEventListener('click', function (event) {
    const button = event.target.closest('.prepare-invitation-btn');
    if (!button) return;
    openInvitationModal(button);
});

function sleep(milliseconds) {
    return new Promise(resolve => setTimeout(resolve, milliseconds));
}

document.getElementById('sendAllPendingBtn')?.addEventListener('click', async function () {
    if (sendingAllInvitations) return;

    const button = this;
    const originalHtml = button.innerHTML;
    const progress = document.getElementById('bulkInvitationProgress');
    const progressText = document.getElementById('bulkInvitationProgressText');
    const progressBar = document.getElementById('bulkInvitationProgressBar');
    const stopButton = document.getElementById('stopBulkInvitationsBtn');

    try {
        const response = await fetch(invitationPreviewData.pendingUrl, {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store',
        });
        const data = await response.json();
        const invitations = data.invitations || [];

        if (!invitations.length) {
            showInvitationToast('No pending invitations to send.', 'success');
            return;
        }

        const uploaded = invitations.filter(invitation => invitation.card_image_url).length;
        const fallback = invitations.length - uploaded;
        const confirmation = 'Send invitations to ' + invitations.length + ' pending guests?\n\n' +
            uploaded + ' have uploaded guest cards. ' +
            fallback + ' will use the event cover or default image.';

        if (!window.confirm(confirmation)) return;

        sendingAllInvitations = true;
        stopBulkInvitations = false;
        button.disabled = true;
        button.classList.add('opacity-70', 'cursor-not-allowed');
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        progress.classList.remove('hidden');
        progressBar.style.width = '0%';
        stopButton.disabled = false;
        stopButton.classList.remove('hidden');
        stopButton.textContent = 'Stop after current';

        let sent = 0;
        let failed = 0;
        let processed = 0;

        for (let index = 0; index < invitations.length; index++) {
            if (stopBulkInvitations) break;

            const invitation = invitations[index];
            progressText.textContent = 'Sending ' + (index + 1) + ' of ' + invitations.length + ': ' + invitation.guest_name;

            try {
                const sendResponse = await fetch(invitation.url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({}),
                });

                let result = {};
                try {
                    result = await sendResponse.json();
                } catch (error) {
                    result = {};
                }

                updateStatCounters(result.stats);
                updateInvitationRow(invitation.id, result);

                if (sendResponse.ok && result.success) {
                    sent++;
                } else {
                    failed++;
                }
            } catch (error) {
                failed++;
            }

            processed = index + 1;
            progressBar.style.width = Math.round((processed / invitations.length) * 100) + '%';

            if (index < invitations.length - 1) {
                await sleep(250);
            }
        }

        const stopped = stopBulkInvitations;
        sendingAllInvitations = false;
        stopBulkInvitations = false;
        stopButton.classList.add('hidden');
        button.disabled = false;
        button.classList.remove('opacity-70', 'cursor-not-allowed');
        button.innerHTML = originalHtml;

        progressText.textContent = (stopped ? 'Stopped after ' + processed + ' of ' + invitations.length + '. ' : 'Finished. ') +
            sent + ' sent, ' + failed + ' failed.';
        showInvitationToast(
            (stopped ? 'Stopped. ' : '') + sent + ' invitation' + (sent === 1 ? '' : 's') + ' sent' +
            (failed ? ', ' + failed + ' failed' : '') + '. Refresh later for delivery and RSVP updates.',
            failed ? 'warning' : 'success'
        );

        setTimeout(() => {
            if (!sendingAllInvitations) progress.classList.add('hidden');
        }, 10000);
    } catch (error) {
        sendingAllInvitations = false;
        button.disabled = false;
        button.classList.remove('opacity-70', 'cursor-not-allowed');
        button.innerHTML = originalHtml;
        progress.classList.add('hidden');
        showInvitationToast('Could not load pending invitations. Please try again.', 'warning');
    }
});

document.getElementById('stopBulkInvitationsBtn')?.addEventListener('click', function () {
    if (!sendingAllInvitations) return;

    stopBulkInvitations = true;
    this.disabled = true;
    this.textContent = 'Stopping after current...';
});

window.addEventListener('click', function (event) {
    if (event.target === document.getElementById('invitationModal')) {
        closeInvitationModal();
    }
});
</script>
@endpush
