@extends('layouts.app')

@section('title', 'Invitations - ' . $event->name)

@section('content')
@php($canSendWhatsApp = auth()->user()->canSendWhatsApp())
<div class="mb-6">
    <a href="{{ route('events.show', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $event->name }}
    </a>
    <div class="flex flex-wrap items-center justify-between gap-3 mt-2">
        <h1 class="font-display text-2xl font-bold text-surface-900">Invitations</h1>
        <div class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:items-center">
            <a href="{{ route('events.invitations.export', array_merge(['event' => $event], request()->query())) }}"
                target="_blank" rel="noopener" class="btn btn-outline {{ $canSendWhatsApp ? '' : 'col-span-2' }} sm:col-span-1">
                <i class="fas fa-file-export"></i> Export QR List
            </a>
            @if($canSendWhatsApp)
            <button type="button" id="sendAllPendingBtn" class="btn btn-success">
                <i class="fab fa-whatsapp"></i> Send All Pending
            </button>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <div class="card-surface p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-[.12em] text-surface-400">Total</p>
                <p class="mt-1 text-2xl sm:text-3xl font-bold text-surface-900" id="stat-total">{{ $stats['total'] }}</p>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-surface-100 text-surface-500"><i class="fas fa-users"></i></span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-surface-100"><div class="h-full w-full rounded-full bg-surface-300"></div></div>
    </div>
    <div class="card-surface p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-[.12em] text-green-600/70">Sent</p>
                <p class="mt-1 text-2xl sm:text-3xl font-bold text-green-600" id="stat-sent">{{ $stats['sent'] }}</p>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-600"><i class="fas fa-paper-plane"></i></span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-green-50"><div class="h-full w-full rounded-full bg-green-500"></div></div>
    </div>
    <div class="card-surface p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-[.12em] text-amber-600/70">Pending</p>
                <p class="mt-1 text-2xl sm:text-3xl font-bold text-amber-600" id="stat-pending">{{ $stats['pending'] }}</p>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><i class="fas fa-clock"></i></span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-amber-50"><div class="h-full w-full rounded-full bg-amber-400"></div></div>
    </div>
    <div class="card-surface p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-[.12em] text-red-600/70">Failed</p>
                <p class="mt-1 text-2xl sm:text-3xl font-bold text-red-600" id="stat-failed">{{ $stats['failed'] }}</p>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600"><i class="fas fa-circle-exclamation"></i></span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-red-50"><div class="h-full w-full rounded-full bg-red-400"></div></div>
    </div>
</div>

<div class="card-surface p-4 mb-4">
    <form method="GET" action="{{ route('events.invitations.index', $event) }}" class="filter-bar"
        data-live-filter data-loading-text="Filtering invitations...">
        <div class="filter-search">
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

@if($canSendWhatsApp)
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
@endif

<div class="card-surface overflow-hidden">
    @if($invitations->isEmpty())
    <div class="px-6 py-12 text-center text-surface-400">
        <p>No eligible invitations found.</p>
        <p class="mt-1 text-xs">Guests appear here once their paid amount meets the event's single-card minimum.</p>
    </div>
    @else
    <div class="overflow-x-auto responsive-table-wrap">
        <table class="w-full responsive-table">
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
                    <td class="px-6 py-3" data-label="Guest">
                        <div class="min-w-0">
                            <div class="font-medium text-surface-900">{{ $invitation->guest->full_name }}</div>
                            <div class="text-xs text-surface-400">{{ $invitation->guest->unique_id }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-3 text-sm text-surface-600" data-label="Phone">{{ $invitation->guest->phone_number }}</td>
                    <td class="px-6 py-3" id="card-cell-{{ $invitation->id }}" data-label="Card">
                        @php($paidCardType = $event->cardTypeForAmount((float) $invitation->guest->paid_amount))
                        <div class="min-w-0">
                            <span class="badge {{ $paidCardType === 'double' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ ucfirst($paidCardType) }}
                            </span>
                            <div class="text-[10px] text-surface-400 mt-1">Paid {{ number_format($invitation->guest->paid_amount) }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-3" id="status-cell-{{ $invitation->id }}" data-label="Send status">
                        <div class="min-w-0">
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
                        </div>
                    </td>
                    <td class="px-6 py-3" data-label="RSVP">
                        <span class="badge
                            {{ $invitation->rsvp_status === 'attending' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $invitation->rsvp_status === 'not_attending' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $invitation->rsvp_status === 'pending' ? 'bg-surface-100 text-surface-600' : '' }}">
                            {{ $invitation->rsvp_status === 'attending' ? 'Nitafika' : ($invitation->rsvp_status === 'not_attending' ? 'Sitafika' : 'Pending') }}
                        </span>
                    </td>
                    <td class="px-6 py-3" data-label="QR code">
                        <div class="qr-code-mini" data-value="{{ $invitation->qr_code_data }}"></div>
                    </td>
                    <td class="px-6 py-3 mobile-actions" id="action-cell-{{ $invitation->id }}" data-label="">
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                            <button type="button" class="upload-card-btn icon-action text-brand-600 hover:text-brand-700"
                                data-invitation-id="{{ $invitation->id }}"
                                data-guest-name="{{ $invitation->guest->full_name }}"
                                data-upload-url="{{ route('events.invitations.upload-card', [$event, $invitation]) }}"
                                data-card-image-url="{{ $invitation->card_image_url }}"
                                title="{{ $invitation->card_image_path ? 'Change uploaded card image' : 'Upload card image' }}">
                                <i class="fas fa-cloud-arrow-up"></i>
                            </button>
                            <a href="{{ route('events.invitations.card', [$event, $invitation->guest]) }}" target="_blank" class="icon-action text-blue-600 hover:text-blue-700" title="View card">
                                <i class="fas fa-id-card"></i>
                            </a>
                            <button type="button" class="copy-public-link-btn icon-action text-surface-500 hover:text-surface-700"
                                data-public-url="{{ route('invitation.public', $invitation->qr_code_data) }}"
                                title="Copy public invitation link">
                                <i class="fas fa-link"></i>
                            </button>
                            @if($canSendWhatsApp && $invitation->send_status === 'pending')
                            <button type="button" class="prepare-invitation-btn icon-action text-green-600 hover:text-green-700 font-medium"
                                data-invitation-id="{{ $invitation->id }}"
                                data-guest-name="{{ $invitation->guest->full_name }}"
                                data-guest-unique-id="{{ $invitation->guest->unique_id }}"
                                data-phone="{{ $invitation->guest->phone_number }}"
                                data-action-url="{{ route('events.invitations.send', [$event, $invitation]) }}"
                                data-card-image-url="{{ $invitation->card_image_url }}"
                                data-public-url="{{ route('invitation.public', $invitation->qr_code_data) }}"
                                title="Send WhatsApp invitation">
                                <i class="fas fa-paper-plane"></i> <span class="hidden sm:inline">Send</span>
                            </button>
                            @elseif($canSendWhatsApp && $invitation->send_status === 'failed')
                            <button type="button" class="prepare-invitation-btn icon-action text-amber-600 hover:text-amber-700 font-medium"
                                data-invitation-id="{{ $invitation->id }}"
                                data-guest-name="{{ $invitation->guest->full_name }}"
                                data-guest-unique-id="{{ $invitation->guest->unique_id }}"
                                data-phone="{{ $invitation->guest->phone_number }}"
                                data-action-url="{{ route('events.invitations.resend', [$event, $invitation]) }}"
                                data-card-image-url="{{ $invitation->card_image_url }}"
                                data-public-url="{{ route('invitation.public', $invitation->qr_code_data) }}"
                                title="Retry WhatsApp invitation">
                                <i class="fas fa-rotate-right"></i> <span class="hidden sm:inline">Retry</span>
                            </button>
                            @elseif($canSendWhatsApp)
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

<!-- Dedicated Upload Card Modal -->
<div id="uploadCardModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="bg-white rounded-t-2xl sm:rounded-xl shadow-xl w-full max-w-md overflow-hidden" style="max-height: 92dvh;">
        <div class="px-6 py-4 border-b border-surface-100 flex items-center justify-between">
            <div>
                <h3 class="font-display font-bold text-lg text-surface-900">Upload Invitation Card</h3>
                <p class="text-xs text-surface-400 mt-0.5">Guest: <span id="uploadCardGuestName" class="font-semibold text-surface-800"></span></p>
            </div>
            <button type="button" onclick="closeUploadCardModal()" class="text-surface-400 hover:text-surface-600" title="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="uploadCardForm" method="POST" action="" enctype="multipart/form-data" data-no-loading>
            @csrf
            <div class="p-6 space-y-4 overflow-y-auto" style="max-height: calc(92vh - 145px);">
                <div id="uploadCardCurrentSection" class="hidden rounded-xl border border-surface-200 bg-surface-50 p-3 text-center">
                    <p class="text-xs font-medium uppercase tracking-wide text-surface-400 mb-2">Current Uploaded Card</p>
                    <img id="uploadCardCurrentImg" src="" alt="Current card" class="mx-auto max-h-48 rounded-lg border border-surface-200 object-contain shadow-sm">
                </div>

                <div>
                    <label for="uploadCardFileInput" class="block text-sm font-medium text-surface-700 mb-1">
                        Select Card Image (JPG, PNG, WEBP)
                    </label>
                    <input type="file" name="card_image" id="uploadCardFileInput" accept="image/jpeg,image/png,image/webp" required
                        class="block w-full text-sm text-surface-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                    <p class="text-[11px] text-surface-400 mt-1">
                        Max size: 5 MB. If a card is already uploaded, uploading a new one will override it.
                    </p>
                </div>

                <div id="uploadCardNewPreviewSection" class="hidden rounded-xl border border-brand-200 bg-brand-50/40 p-3 text-center">
                    <p class="text-xs font-semibold text-brand-700 mb-2">New Card Preview</p>
                    <img id="uploadCardNewPreviewImg" src="" alt="New card preview" class="mx-auto max-h-48 rounded-lg border border-brand-200 object-contain shadow-sm">
                </div>

                <div id="uploadCardProgress" class="hidden rounded-lg bg-surface-50 p-3">
                    <div class="flex items-center gap-2 text-xs text-surface-500">
                        <i class="fas fa-spinner fa-spin text-brand-600"></i>
                        <span>Uploading card image...</span>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-surface-50 border-t border-surface-100 flex justify-end gap-3">
                <button type="button" onclick="closeUploadCardModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" id="uploadCardSubmitBtn" class="btn btn-primary">
                    <i class="fas fa-cloud-arrow-up mr-2"></i> Upload Card
                </button>
            </div>
        </form>
    </div>
</div>

@if($canSendWhatsApp)
<div id="invitationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="bg-white rounded-t-2xl sm:rounded-xl shadow-xl w-full max-w-xl overflow-hidden" style="max-height: 92dvh;">
        <div class="px-6 py-4 border-b border-surface-100 flex items-center justify-between">
            <div>
                <h3 class="font-display font-bold text-lg text-surface-900">Prepare Invitation</h3>
                <p class="text-xs text-surface-400 mt-0.5">Upload or review the guest card, review the message, then send.</p>
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="invitationTemplateInput" class="block text-sm font-medium text-surface-700">
                                WhatsApp template
                            </label>
                            @if(Auth::user()->isAdmin())
                            <a href="{{ route('admin.whatsapp-templates.index') }}" target="_blank" class="text-xs text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                                <i class="fas fa-rotate text-[10px]"></i> Sync Templates
                            </a>
                            @endif
                        </div>
                        @if(!empty($whatsappTemplates) && $whatsappTemplates->isNotEmpty())
                        <select name="template_name" id="invitationTemplateInput" class="input-field">
                            @foreach($whatsappTemplates as $tmpl)
                            <option value="{{ $tmpl->name }}" data-language="{{ $tmpl->language }}" {{ ($tmpl->name === config('services.whatsapp.template_name')) ? 'selected' : '' }}>
                                {{ $tmpl->name }} ({{ $tmpl->language }}) @if($tmpl->status !== 'APPROVED') [{{ $tmpl->status }}] @endif
                            </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="template_language" id="invitationTemplateLanguage" value="{{ $whatsappTemplates->firstWhere('name', config('services.whatsapp.template_name'))?->language ?? $whatsappTemplates->first()?->language ?? config('services.whatsapp.template_language') }}">
                        @else
                        <input type="text" name="template_name" id="invitationTemplateInput"
                            list="invitationTemplateOptions"
                            value="{{ config('services.whatsapp.template_name') }}"
                            class="input-field" placeholder="e.g. wedding_invitation">
                        <datalist id="invitationTemplateOptions">
                            @foreach(config('services.whatsapp.invitation_templates', []) as $template)
                            <option value="{{ $template }}"></option>
                            @endforeach
                        </datalist>
                        <input type="hidden" name="template_language" id="invitationTemplateLanguage" value="{{ config('services.whatsapp.template_language') }}">
                        @endif
                        <p class="text-[10px] text-surface-400 mt-1">
                            Your approved template must contain 4 body parameters: guest name, guest ID, event date, and public invitation link.
                        </p>
                    </div>
                    <div>
                        <label for="invitationPublicUrl" class="block text-sm font-medium text-surface-700 mb-1">
                            Public invitation link
                        </label>
                        <div class="flex gap-2">
                            <input type="text" id="invitationPublicUrl" readonly class="input-field text-[11px]">
                            <button type="button" onclick="copyInvitationLink()" class="btn btn-outline shrink-0" title="Copy link">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <p class="text-[10px] text-surface-400 mt-1">
                            Share this link by SMS too. It opens the guest invitation and card download.
                        </p>
                    </div>
                </div>

                <div id="invitationCardSection" class="space-y-3">
                    <!-- Notice when no card is uploaded yet -->
                    <div id="invitationCardNotUploadedNotice" class="hidden rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 flex items-start gap-2">
                        <i class="fas fa-circle-exclamation mt-0.5 text-amber-600 shrink-0"></i>
                        <div>
                            <p class="font-semibold">No invitation card uploaded yet for this guest.</p>
                            <p class="mt-0.5 text-amber-700">Please upload the card image below for this guest so they receive their personalized card.</p>
                        </div>
                    </div>

                    <!-- Notice when card is already uploaded -->
                    <div id="invitationCardAlreadyUploadedNotice" class="hidden rounded-xl border border-green-200 bg-green-50 p-3 text-xs text-green-800 flex items-start gap-2">
                        <i class="fas fa-circle-check mt-0.5 text-green-600 shrink-0"></i>
                        <div>
                            <p class="font-semibold">Invitation card is already uploaded.</p>
                            <p class="mt-0.5 text-green-700">The current card is displayed below. Uploading a new image will override it.</p>
                        </div>
                    </div>

                    <div>
                        <label for="invitationCardInput" id="invitationCardInputLabel" class="block text-sm font-medium text-surface-700 mb-1">
                            Invitation card image
                        </label>
                        <input type="file" name="card_image" id="invitationCardInput" accept="image/jpeg,image/png,image/webp"
                            class="block w-full text-sm text-surface-500 file:mr-3 file:rounded-lg file:border-0 file:bg-green-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-green-700 hover:file:bg-green-100">
                        <p class="text-[11px] text-surface-400 mt-1" id="invitationCardInputHelp">
                            JPG, PNG or WEBP up to 5 MB.
                        </p>
                    </div>

                    <div class="rounded-xl border border-surface-200 bg-surface-50 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <span id="invitationCardPreviewLabel" class="text-xs font-medium uppercase tracking-wide text-surface-400">Card preview</span>
                            <a id="invitationCurrentCardLink" href="#" target="_blank" class="hidden text-xs text-blue-600 hover:text-blue-700">
                                Open current image <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>
                        <div id="invitationCardPreviewWrap" class="hidden mt-3">
                            <img id="invitationCardPreview" src="" alt="Invitation card preview"
                                class="mx-auto max-h-56 rounded-lg border border-surface-200 bg-white object-contain shadow-sm">
                        </div>
                        <div id="invitationCardEmpty" class="mt-3 rounded-lg border border-dashed border-surface-300 bg-white px-4 py-8 text-center text-xs text-surface-400">
                            No guest card uploaded yet. Select an image above to upload and preview.
                        </div>
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
@endif

<div id="invitationToast" class="hidden fixed bottom-24 right-4 z-[80] max-w-sm rounded-xl px-5 py-3 text-sm font-medium text-white shadow-2xl md:bottom-5 md:right-5"></div>
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
    eventName: {!! json_encode($event->name) !!},
    eventDate: {!! json_encode($event->event_date->format('l, d M Y')) !!},
    eventTime: {!! json_encode($event->event_time) !!},
    location: {!! json_encode($event->location) !!},
    hall: {!! json_encode($event->hall) !!},
    coverImageUrl: {!! json_encode($event->cover_image ? asset('storage/'.$event->cover_image) : config('services.whatsapp.default_image_url')) !!},
    templateName: {!! json_encode(config('services.whatsapp.template_name')) !!},
    pendingUrl: {!! json_encode(route('events.invitations.pending', $event)) !!},
    publicUrl: null,
};

let activeInvitation = null;
let invitationPreviewObjectUrl = null;
let sendingInvitation = false;
let sendingAllInvitations = false;
let stopBulkInvitations = false;
let invitationToastTimer = null;

let activeUploadInvitation = null;
let uploadPreviewObjectUrl = null;
let uploadingCard = false;

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
    if (container) {
        if (url) {
            container.innerHTML = `
                <a href="${escapeHtml(url)}" target="_blank" title="View uploaded card" class="block shrink-0">
                    <img src="${escapeHtml(url)}" alt="Invitation card" class="h-14 w-10 rounded border border-surface-200 object-cover shadow-xs hover:opacity-90">
                </a>
            `;
        } else {
            container.innerHTML = '';
        }
    }

    // Update upload button and prepare/send button data attributes across the row
    const uploadButtons = document.querySelectorAll(`.upload-card-btn[data-invitation-id="${invitationId}"]`);
    uploadButtons.forEach(btn => {
        btn.dataset.cardImageUrl = url || '';
        btn.setAttribute('title', url ? 'Change uploaded card image' : 'Upload card image');
    });

    const prepareBtn = document.querySelector(`.prepare-invitation-btn[data-invitation-id="${invitationId}"]`);
    if (prepareBtn) {
        prepareBtn.dataset.cardImageUrl = url || '';
    }

    if (activeInvitation && String(activeInvitation.id) === String(invitationId)) {
        activeInvitation.cardImageUrl = url || null;
        const currentLink = document.getElementById('invitationCurrentCardLink');
        const notUploadedNotice = document.getElementById('invitationCardNotUploadedNotice');
        const alreadyUploadedNotice = document.getElementById('invitationCardAlreadyUploadedNotice');
        const cardInputLabel = document.getElementById('invitationCardInputLabel');
        const cardInputHelp = document.getElementById('invitationCardInputHelp');

        if (url) {
            if (currentLink) {
                currentLink.href = url;
                currentLink.classList.remove('hidden');
            }
            if (alreadyUploadedNotice) alreadyUploadedNotice.classList.remove('hidden');
            if (notUploadedNotice) notUploadedNotice.classList.add('hidden');
            if (cardInputLabel) cardInputLabel.textContent = 'Replace / Override Card Image (optional)';
            if (cardInputHelp) cardInputHelp.textContent = 'JPG, PNG or WEBP up to 5 MB. Uploading a new image will override the current card for this guest.';
            setInvitationImage(url, 'Uploaded guest card (Ready to send)');
        } else {
            if (currentLink) currentLink.classList.add('hidden');
            if (alreadyUploadedNotice) alreadyUploadedNotice.classList.add('hidden');
            if (notUploadedNotice) notUploadedNotice.classList.remove('hidden');
            if (cardInputLabel) cardInputLabel.textContent = 'Upload Invitation Card Image';
            if (cardInputHelp) cardInputHelp.textContent = 'JPG, PNG or WEBP up to 5 MB. Please upload a card image for this guest.';
            setInvitationImage(invitationPreviewData.coverImageUrl, 'Event cover fallback (No guest card uploaded)');
        }
    }
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
    lines.push('Your invitation: ' + invitationPreviewData.publicUrl);
    lines.push('');
    lines.push('Please present your QR code at the entrance.');

    return lines.join('\n');
}

function setInvitationImage(url, label) {
    const imageWrap = document.getElementById('invitationCardPreviewWrap');
    const cardImage = document.getElementById('invitationCardPreview');
    const emptyState = document.getElementById('invitationCardEmpty');
    const messageImage = document.getElementById('invitationMessageImage');

    const previewLabelEl = document.getElementById('invitationCardPreviewLabel');
    if (previewLabelEl) previewLabelEl.textContent = label;

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

    const cardInput = document.getElementById('invitationCardInput');
    if (cardInput) cardInput.value = '';
    document.getElementById('invitationSendProgress').classList.add('hidden');

    const submitButton = document.getElementById('invitationSubmitBtn');
    submitButton.disabled = false;
    submitButton.classList.remove('opacity-70', 'cursor-not-allowed');
    submitButton.innerHTML = '<i class="fab fa-whatsapp mr-2"></i> Send Invitation';
}

function openInvitationModal(button) {
    const cardImageUrl = (button.dataset.cardImageUrl && button.dataset.cardImageUrl.trim() !== '')
        ? button.dataset.cardImageUrl.trim()
        : null;

    activeInvitation = {
        id: button.dataset.invitationId,
        guestName: button.dataset.guestName,
        guestUniqueId: button.dataset.guestUniqueId,
        phone: button.dataset.phone,
        actionUrl: button.dataset.actionUrl,
        cardImageUrl: cardImageUrl,
        publicUrl: button.dataset.publicUrl || '',
    };

    invitationPreviewData.publicUrl = activeInvitation.publicUrl;

    document.getElementById('invitationForm').action = activeInvitation.actionUrl;
    const templateInput = document.getElementById('invitationTemplateInput');
    const templateLangInput = document.getElementById('invitationTemplateLanguage');
    if (templateInput) {
        if (templateInput.tagName === 'SELECT') {
            const selectedOpt = templateInput.options[templateInput.selectedIndex];
            if (templateLangInput && selectedOpt) {
                templateLangInput.value = selectedOpt.dataset.language || '';
            }
            const langLabel = selectedOpt && selectedOpt.dataset.language ? ' (' + selectedOpt.dataset.language + ')' : '';
            document.getElementById('invitationTemplateName').textContent = 'Template: ' + (templateInput.value || '') + langLabel;
        } else {
            templateInput.value = invitationPreviewData.templateName;
            document.getElementById('invitationTemplateName').textContent = 'Template: ' + invitationPreviewData.templateName;
        }
    }
    document.getElementById('invitationPublicUrl').value = activeInvitation.publicUrl;
    document.getElementById('invitationGuestName').textContent = activeInvitation.guestName;
    document.getElementById('invitationGuestPhone').textContent = activeInvitation.phone || 'No phone';
    document.getElementById('invitationGuestId').textContent = activeInvitation.guestUniqueId;
    document.getElementById('invitationMessageText').textContent =
        invitationPreviewText(activeInvitation.guestName, activeInvitation.guestUniqueId);

    const currentLink = document.getElementById('invitationCurrentCardLink');
    const notUploadedNotice = document.getElementById('invitationCardNotUploadedNotice');
    const alreadyUploadedNotice = document.getElementById('invitationCardAlreadyUploadedNotice');
    const cardInputLabel = document.getElementById('invitationCardInputLabel');
    const cardInputHelp = document.getElementById('invitationCardInputHelp');

    if (activeInvitation.cardImageUrl) {
        if (currentLink) {
            currentLink.href = activeInvitation.cardImageUrl;
            currentLink.classList.remove('hidden');
        }
        if (alreadyUploadedNotice) alreadyUploadedNotice.classList.remove('hidden');
        if (notUploadedNotice) notUploadedNotice.classList.add('hidden');
        if (cardInputLabel) cardInputLabel.textContent = 'Replace / Override Card Image (optional)';
        if (cardInputHelp) cardInputHelp.textContent = 'JPG, PNG or WEBP up to 5 MB. Uploading a new image will override the current card for this guest.';
        setInvitationImage(activeInvitation.cardImageUrl, 'Uploaded guest card (Ready to send)');
    } else {
        if (currentLink) currentLink.classList.add('hidden');
        if (alreadyUploadedNotice) alreadyUploadedNotice.classList.add('hidden');
        if (notUploadedNotice) notUploadedNotice.classList.remove('hidden');
        if (cardInputLabel) cardInputLabel.textContent = 'Upload Invitation Card Image';
        if (cardInputHelp) cardInputHelp.textContent = 'JPG, PNG or WEBP up to 5 MB. Please upload a card image for this guest.';
        setInvitationImage(invitationPreviewData.coverImageUrl, 'Event cover fallback (No guest card uploaded)');
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

function openUploadCardModal(button) {
    activeUploadInvitation = {
        id: button.dataset.invitationId,
        guestName: button.dataset.guestName,
        uploadUrl: button.dataset.uploadUrl,
        cardImageUrl: button.dataset.cardImageUrl || null,
    };

    document.getElementById('uploadCardGuestName').textContent = activeUploadInvitation.guestName;
    document.getElementById('uploadCardForm').action = activeUploadInvitation.uploadUrl;
    document.getElementById('uploadCardFileInput').value = '';

    const currentSection = document.getElementById('uploadCardCurrentSection');
    const currentImg = document.getElementById('uploadCardCurrentImg');
    const newSection = document.getElementById('uploadCardNewPreviewSection');
    const newImg = document.getElementById('uploadCardNewPreviewImg');

    if (activeUploadInvitation.cardImageUrl) {
        currentImg.src = activeUploadInvitation.cardImageUrl;
        currentSection.classList.remove('hidden');
    } else {
        currentImg.removeAttribute('src');
        currentSection.classList.add('hidden');
    }

    newSection.classList.add('hidden');
    newImg.removeAttribute('src');
    if (uploadPreviewObjectUrl) {
        URL.revokeObjectURL(uploadPreviewObjectUrl);
        uploadPreviewObjectUrl = null;
    }

    document.getElementById('uploadCardProgress').classList.add('hidden');
    const submitBtn = document.getElementById('uploadCardSubmitBtn');
    submitBtn.disabled = false;
    submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
    submitBtn.innerHTML = '<i class="fas fa-cloud-arrow-up mr-2"></i> Upload Card';

    document.getElementById('uploadCardModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeUploadCardModal() {
    if (uploadingCard) return;

    document.getElementById('uploadCardModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    if (uploadPreviewObjectUrl) {
        URL.revokeObjectURL(uploadPreviewObjectUrl);
        uploadPreviewObjectUrl = null;
    }
}

document.getElementById('uploadCardFileInput')?.addEventListener('change', function (e) {
    const file = e.target.files[0];
    const newSection = document.getElementById('uploadCardNewPreviewSection');
    const newImg = document.getElementById('uploadCardNewPreviewImg');

    if (uploadPreviewObjectUrl) {
        URL.revokeObjectURL(uploadPreviewObjectUrl);
        uploadPreviewObjectUrl = null;
    }

    if (file) {
        uploadPreviewObjectUrl = URL.createObjectURL(file);
        newImg.src = uploadPreviewObjectUrl;
        newSection.classList.remove('hidden');
    } else {
        newSection.classList.add('hidden');
        newImg.removeAttribute('src');
    }
});

document.getElementById('uploadCardForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    if (uploadingCard || !activeUploadInvitation) return;

    const form = e.currentTarget;
    const submitBtn = document.getElementById('uploadCardSubmitBtn');
    const progress = document.getElementById('uploadCardProgress');
    const invitationId = activeUploadInvitation.id;
    const guestName = activeUploadInvitation.guestName;

    uploadingCard = true;
    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Uploading...';
    progress.classList.remove('hidden');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: new FormData(form),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            const cardUrl = data.card_image_url || data.invitation?.card_image_url || '';
            updateInvitationThumbnail(invitationId, cardUrl);
            uploadingCard = false;
            closeUploadCardModal();
            showInvitationToast('Card image uploaded for ' + guestName + '!', 'success');
            return;
        }

        const msg = data.errors ? Object.values(data.errors)[0]?.[0] : (data.message || 'Could not upload card image.');
        showInvitationToast(msg, 'warning');
    } catch (err) {
        showInvitationToast('Network error while uploading card.', 'warning');
    } finally {
        uploadingCard = false;
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
        submitBtn.innerHTML = '<i class="fas fa-cloud-arrow-up mr-2"></i> Upload Card';
        progress.classList.add('hidden');
    }
});

document.getElementById('invitationTemplateInput')?.addEventListener('change', function () {
    const templateLangInput = document.getElementById('invitationTemplateLanguage');
    if (this.tagName === 'SELECT') {
        const selectedOpt = this.options[this.selectedIndex];
        if (templateLangInput && selectedOpt) {
            templateLangInput.value = selectedOpt.dataset.language || '';
        }
        const langLabel = selectedOpt && selectedOpt.dataset.language ? ' (' + selectedOpt.dataset.language + ')' : '';
        document.getElementById('invitationTemplateName').textContent = 'Template: ' + (this.value.trim() || '') + langLabel;
    } else {
        document.getElementById('invitationTemplateName').textContent = 'Template: ' + (this.value.trim() || invitationPreviewData.templateName);
    }
});

document.getElementById('invitationTemplateInput')?.addEventListener('input', function () {
    if (this.tagName !== 'SELECT') {
        document.getElementById('invitationTemplateName').textContent = 'Template: ' + (this.value.trim() || invitationPreviewData.templateName);
    }
});

document.getElementById('invitationCardInput')?.addEventListener('change', function (event) {
    const file = event.target.files[0];
    const currentLink = document.getElementById('invitationCurrentCardLink');
    const notUploadedNotice = document.getElementById('invitationCardNotUploadedNotice');
    const alreadyUploadedNotice = document.getElementById('invitationCardAlreadyUploadedNotice');

    if (!file) {
        if (activeInvitation?.cardImageUrl) {
            currentLink.classList.remove('hidden');
            if (alreadyUploadedNotice) alreadyUploadedNotice.classList.remove('hidden');
            if (notUploadedNotice) notUploadedNotice.classList.add('hidden');
            setInvitationImage(activeInvitation.cardImageUrl, 'Uploaded guest card (Ready to send)');
        } else {
            currentLink.classList.add('hidden');
            if (alreadyUploadedNotice) alreadyUploadedNotice.classList.add('hidden');
            if (notUploadedNotice) notUploadedNotice.classList.remove('hidden');
            setInvitationImage(invitationPreviewData.coverImageUrl, 'Event cover fallback (No guest card uploaded)');
        }
        return;
    }

    if (invitationPreviewObjectUrl) {
        URL.revokeObjectURL(invitationPreviewObjectUrl);
    }

    invitationPreviewObjectUrl = URL.createObjectURL(file);
    currentLink.classList.add('hidden');
    if (notUploadedNotice) notUploadedNotice.classList.add('hidden');
    if (alreadyUploadedNotice) alreadyUploadedNotice.classList.remove('hidden');
    setInvitationImage(invitationPreviewObjectUrl, activeInvitation?.cardImageUrl ? 'New card to override existing' : 'Selected guest card');
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

function copyInvitationLink() {
    copyTextToClipboard(document.getElementById('invitationPublicUrl').value);
}

async function copyTextToClipboard(value) {
    if (!value) return;

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(value);
        } else {
            const input = document.createElement('textarea');
            input.value = value;
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
        }

        showInvitationToast('Invitation link copied.', 'success');
    } catch (error) {
        window.prompt('Copy this invitation link:', value);
    }
}

document.querySelector('tbody')?.addEventListener('click', function (event) {
    const uploadBtn = event.target.closest('.upload-card-btn');
    if (uploadBtn) {
        openUploadCardModal(uploadBtn);
        return;
    }

    const copyButton = event.target.closest('.copy-public-link-btn');
    if (copyButton) {
        copyTextToClipboard(copyButton.dataset.publicUrl);
        return;
    }

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
