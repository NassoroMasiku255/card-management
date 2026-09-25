@extends('layouts.app')

@section('title', 'QR Scanner - ' . $event->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('events.show', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $event->name }}
    </a>
    <h1 class="font-display text-2xl font-bold text-surface-900 mt-2">QR Code Scanner</h1>
    <p class="text-surface-500 text-sm">Scan guest QR codes to verify and check in</p>
</div>

<!-- Live Stats -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="card-surface p-4 text-center">
        <div class="text-2xl font-bold text-surface-900" id="stat-total">{{ $stats['total_guests'] }}</div>
        <div class="text-xs text-surface-500">Total Guests</div>
    </div>
    <div class="card-surface p-4 text-center">
        <div class="text-2xl font-bold text-green-600" id="stat-checked">{{ $stats['checked_in'] }}</div>
        <div class="text-xs text-surface-500">Checked In</div>
    </div>
    <div class="card-surface p-4 text-center">
        <div class="text-2xl font-bold text-primary-600" id="stat-remaining">{{ $stats['remaining'] }}</div>
        <div class="text-xs text-surface-500">Remaining</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Scanner Area -->
    <div class="card-surface p-6">
        <h3 class="font-semibold text-surface-900 mb-4"><i class="fas fa-camera mr-2 text-primary-500"></i>Camera Scanner</h3>
        <div id="scanner-container" class="relative rounded-xl overflow-hidden bg-surface-900" style="min-height: 300px;">
            <video id="scanner-video" class="w-full rounded-xl" playsinline></video>
            <canvas id="scanner-canvas" class="hidden"></canvas>
            <div id="scanner-overlay" class="absolute inset-0 flex items-center justify-center">
                <div class="border-2 border-primary-400 rounded-lg w-48 h-48 opacity-75"></div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button id="btn-start-scan" onclick="startScanner()" class="btn btn-success flex-1">
                <i class="fas fa-play"></i> Start Scanner
            </button>
            <button id="btn-stop-scan" onclick="stopScanner()" class="btn btn-destructive flex-1 hidden">
                <i class="fas fa-stop"></i> Stop Scanner
            </button>
        </div>

        <!-- Manual Entry -->
        <div class="mt-4 pt-4 border-t border-surface-100">
            <h4 class="text-sm font-medium text-surface-700 mb-2">Or enter ID manually:</h4>
            <div class="flex gap-2">
                <input type="text" id="manual-qr-input" placeholder="Enter Guest ID (e.g., G1-ABCD1234)"
                    class="input-field flex-1">
                <button onclick="manualVerify()" class="btn btn-primary">
                    Verify
                </button>
            </div>
        </div>
    </div>

    <!-- Result Area -->
    <div>
        <div id="result-area" class="card-surface p-6">
            <h3 class="font-semibold text-surface-900 mb-4"><i class="fas fa-user-check mr-2 text-primary-500"></i>Verification Result</h3>
            <div id="result-content" class="text-center py-8 text-surface-400">
                <i class="fas fa-qrcode text-5xl mb-3"></i>
                <p>Scan a QR code to verify a guest</p>
            </div>
        </div>

        <!-- Recent Check-ins -->
        <div class="card-surface p-6 mt-6">
            <h3 class="font-semibold text-surface-900 mb-4"><i class="fas fa-clock-rotate-left mr-2 text-primary-500"></i>Recent Check-ins</h3>
            <div id="recent-checkins" class="space-y-2 max-h-64 overflow-y-auto">
                <p class="text-surface-400 text-sm text-center py-4">No check-ins yet</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let jsQRReady = false;
loadScriptWithFallback([
    'https://cdnjs.cloudflare.com/ajax/libs/jsQR/1.4.0/jsQR.js',
    'https://unpkg.com/jsqr@1.4.0/dist/jsQR.js',
    'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js'
], function () { jsQRReady = true; });

const eventId = {{ $event->id }};
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
let videoStream = null;
let scanInterval = null;

function startScanner() {
    if (!jsQRReady) {
        alert('Scanner is still loading, please try again in a moment.');
        return;
    }
    const video = document.getElementById('scanner-video');

    navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 480 } }
    }).then(stream => {
        videoStream = stream;
        video.srcObject = stream;
        video.play();

        document.getElementById('btn-start-scan').classList.add('hidden');
        document.getElementById('btn-stop-scan').classList.remove('hidden');

        scanInterval = setInterval(scanFrame, 200);
    }).catch(err => {
        alert('Camera access denied. Please use manual entry.');
    });
}

function stopScanner() {
    if (videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
        videoStream = null;
    }
    if (scanInterval) {
        clearInterval(scanInterval);
        scanInterval = null;
    }
    document.getElementById('btn-start-scan').classList.remove('hidden');
    document.getElementById('btn-stop-scan').classList.add('hidden');
}

function scanFrame() {
    const video = document.getElementById('scanner-video');
    const canvas = document.getElementById('scanner-canvas');

    if (video.readyState !== video.HAVE_ENOUGH_DATA) return;

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

    const code = jsQR(imageData.data, imageData.width, imageData.height);
    if (code) {
        clearInterval(scanInterval);
        scanInterval = null;
        verifyGuest(code.data);
        setTimeout(() => { if (videoStream) scanInterval = setInterval(scanFrame, 200); }, 3000);
    }
}

function manualVerify() {
    const input = document.getElementById('manual-qr-input');
    if (input.value.trim()) {
        verifyGuest(input.value.trim());
        input.value = '';
    }
}

document.getElementById('manual-qr-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') manualVerify();
});

function verifyGuest(qrData) {
    const resultContent = document.getElementById('result-content');
    resultContent.innerHTML = '<div class="py-4"><i class="fas fa-spinner fa-spin text-2xl text-primary-500"></i><p class="mt-2 text-surface-500">Verifying...</p></div>';

    fetch(`/events/${eventId}/scanner/verify`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ qr_code: qrData })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showGuestInfo(data.guest);
        } else {
            showError(data.message, data.guest);
        }
    })
    .catch(err => {
        showError('Network error. Please try again.');
    });
}

function showGuestInfo(guest) {
    const resultContent = document.getElementById('result-content');
    resultContent.innerHTML = `
        <div class="text-left">
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4">
                <div class="flex items-center text-green-700">
                    <i class="fas fa-circle-check text-xl mr-2"></i>
                    <span class="font-semibold">Guest Verified!</span>
                </div>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-surface-500">Name:</span>
                    <span class="font-semibold text-surface-900">${guest.name}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-surface-500">ID:</span>
                    <span class="font-mono text-sm text-surface-600">${guest.unique_id}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-surface-500">Card Type:</span>
                    <span class="badge ${guest.card_type === 'double' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'}">${guest.card_type}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-surface-500">RSVP:</span>
                    <span class="badge ${guest.rsvp_status === 'attending' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}">${guest.rsvp_status === 'attending' ? 'Nitafika' : (guest.rsvp_status === 'not_attending' ? 'Sitafika' : 'Pending')}</span>
                </div>
                ${guest.table_number ? `<div class="flex justify-between items-center"><span class="text-sm text-surface-500">Table:</span><span class="text-surface-900">${guest.table_number}</span></div>` : ''}
                ${guest.category ? `<div class="flex justify-between items-center"><span class="text-sm text-surface-500">Category:</span><span class="text-surface-900">${guest.category}</span></div>` : ''}
            </div>
            <button onclick="checkInGuest(${guest.invitation_id}, '${guest.name}')"
                class="btn btn-success btn-block mt-6 py-3 text-base">
                <i class="fas fa-circle-check"></i> Mark Attended
            </button>
        </div>
    `;
}

function showError(message, guest = null) {
    const resultContent = document.getElementById('result-content');
    let html = `
        <div class="text-center">
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                <div class="flex items-center justify-center text-red-700">
                    <i class="fas fa-circle-xmark text-xl mr-2"></i>
                    <span class="font-semibold">${message}</span>
                </div>
            </div>
    `;
    if (guest && guest.already_checked_in) {
        html += `
            <div class="mt-3 text-left bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                <p class="text-sm"><strong>${guest.name}</strong></p>
                <p class="text-xs text-surface-500">Checked in at: ${guest.checked_in_at}</p>
            </div>
        `;
    }
    html += `</div>`;
    resultContent.innerHTML = html;
}

function checkInGuest(invitationId, guestName) {
    fetch(`/events/${eventId}/scanner/check-in`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ invitation_id: invitationId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const resultContent = document.getElementById('result-content');
            resultContent.innerHTML = `
                <div class="text-center py-4">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-check text-green-600 text-2xl"></i>
                    </div>
                    <h4 class="font-semibold text-green-700 text-lg">Checked In!</h4>
                    <p class="text-surface-600 mt-1">${guestName}</p>
                </div>
            `;

            // Update stats
            if (data.stats) {
                document.getElementById('stat-checked').textContent = data.stats.checked_in;
                document.getElementById('stat-remaining').textContent = data.stats.total_guests - data.stats.checked_in;
            }

            // Add to recent
            addRecentCheckin(guestName);
        } else {
            showError(data.message);
        }
    });
}

function addRecentCheckin(name) {
    const container = document.getElementById('recent-checkins');
    const placeholder = container.querySelector('p.text-surface-400');
    if (placeholder) placeholder.remove();

    const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    const item = document.createElement('div');
    item.className = 'flex items-center justify-between py-2 px-3 bg-green-50 rounded-lg';
    item.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-circle-check text-green-500 mr-2"></i>
            <span class="text-sm font-medium text-surface-700">${name}</span>
        </div>
        <span class="text-xs text-surface-400">${time}</span>
    `;
    container.prepend(item);
}
</script>
@endpush
