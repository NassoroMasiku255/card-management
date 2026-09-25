@extends('layouts.app')

@section('title', 'Invitations - ' . $event->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('events.show', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $event->name }}
    </a>
    <div class="flex flex-wrap items-center justify-between gap-3 mt-2">
        <h1 class="font-display text-2xl font-bold text-surface-900">Invitations</h1>
        <form method="POST" action="{{ route('events.invitations.sendAll', $event) }}"
            onsubmit="return confirm('Send invitations to all pending guests via WhatsApp?')">
            @csrf
            <button type="submit" class="btn btn-success">
                <i class="fab fa-whatsapp"></i> Send All Pending
            </button>
        </form>
    </div>
</div>

<!-- Stats -->
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

<div class="card-surface overflow-hidden">
    @if($invitations->isEmpty())
    <div class="px-6 py-12 text-center text-surface-400">
        <p>No invitations yet. Add guests first.</p>
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
                    <td class="px-6 py-3">
                        <span class="badge {{ $invitation->guest->card_type === 'double' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($invitation->guest->card_type) }}
                        </span>
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
                        <div class="flex items-center gap-3">
                            <a href="{{ route('events.invitations.card', [$event, $invitation->guest]) }}" target="_blank" class="text-blue-600 hover:text-blue-700 text-sm" title="View Card">
                                <i class="fas fa-id-card"></i>
                            </a>
                            @if($invitation->send_status === 'pending')
                            <button type="button" class="send-invitation-btn text-green-600 hover:text-green-700 text-sm font-medium"
                                data-invitation-id="{{ $invitation->id }}"
                                data-url="{{ route('events.invitations.send', [$event, $invitation]) }}">
                                <i class="fab fa-whatsapp mr-1"></i> <span class="btn-label">Send</span>
                            </button>
                            @elseif($invitation->send_status === 'failed')
                            <button type="button" class="send-invitation-btn text-amber-600 hover:text-amber-700 text-sm font-medium"
                                data-invitation-id="{{ $invitation->id }}"
                                data-url="{{ route('events.invitations.resend', [$event, $invitation]) }}">
                                <i class="fas fa-rotate-right mr-1"></i> <span class="btn-label">Retry</span>
                            </button>
                            @else
                            <span class="text-surface-400 text-sm"><i class="fas fa-check"></i></span>
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
@endsection

@push('scripts')
<script>
loadScriptWithFallback([
    'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',
    'https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js',
    'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js'
], function () {
    document.querySelectorAll('.qr-code-mini').forEach(el => {
        const value = el.dataset.value;
        new QRCode(el, {
            text: value,
            width: 40,
            height: 40,
            colorDark: '#1a1a1a',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    });
});

const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function updateStatCounters(stats) {
    if (!stats) return;
    const map = { total: 'stat-total', sent: 'stat-sent', pending: 'stat-pending', failed: 'stat-failed' };
    Object.entries(map).forEach(([key, id]) => {
        const el = document.getElementById(id);
        if (el && typeof stats[key] !== 'undefined') el.textContent = stats[key];
    });
}

// Event delegation so this keeps working even after we re-render a row's action cell
document.querySelector('tbody')?.addEventListener('click', function (e) {
    const btn = e.target.closest('.send-invitation-btn');
    if (!btn) return;

    const invitationId = btn.dataset.invitationId;
    const url = btn.dataset.url;
    const statusCell = document.getElementById(`status-cell-${invitationId}`);
    const actionCell = document.getElementById(`action-cell-${invitationId}`);
    const cardLinkHtml = actionCell.querySelector('a')?.outerHTML || '';

    // Show a little loading spinner on this specific row while the request is in flight
    btn.disabled = true;
    btn.dataset.originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sending...';
    btn.classList.add('opacity-70', 'cursor-not-allowed');

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({}),
    })
    .then(res => res.json())
    .then(data => {
        updateStatCounters(data.stats);

        if (data.success) {
            statusCell.innerHTML = `
                <span class="badge bg-green-100 text-green-700">Sent</span>
                ${data.invitation?.sent_at ? `<div class="text-xs text-surface-400 mt-1">${data.invitation.sent_at}</div>` : ''}
            `;
            actionCell.innerHTML = `
                <div class="flex items-center gap-3">
                    ${cardLinkHtml}
                    <span class="text-surface-400 text-sm"><i class="fas fa-check"></i></span>
                </div>
            `;
        } else {
            statusCell.innerHTML = `<span class="badge bg-red-100 text-red-700">Failed</span>`;
            actionCell.innerHTML = `
                <div class="flex items-center gap-3">
                    ${cardLinkHtml}
                    <button type="button" class="send-invitation-btn text-amber-600 hover:text-amber-700 text-sm font-medium"
                        data-invitation-id="${invitationId}"
                        data-url="${url.replace('/send', '/resend')}">
                        <i class="fas fa-rotate-right mr-1"></i> <span class="btn-label">Retry</span>
                    </button>
                </div>
            `;
            alert(data.message || 'Failed to send invitation.');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalHtml;
        btn.classList.remove('opacity-70', 'cursor-not-allowed');
        alert('Network error while sending. Please try again.');
    });
});
</script>
@endpush
