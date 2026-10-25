<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NiaLike · Invitation Guest List - {{ $event->name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f4f4f5;
            color: #18181b;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 13px;
            line-height: 1.45;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid #e4e4e7;
            backdrop-filter: blur(8px);
        }
        .toolbar a, .toolbar button {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid #e4e4e7;
            border-radius: 9px;
            background: #fff;
            color: #3f3f46;
            padding: 8px 14px;
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .toolbar button {
            border-color: #18181b;
            background: #18181b;
            color: #fff;
        }
        .toolbar button:hover { background: #27272a; }
        .toolbar a:hover { background: #fafafa; }
        .sheet {
            max-width: 1060px;
            margin: 24px auto;
            padding: 28px;
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 18px;
            box-shadow: 0 10px 30px -18px rgba(24, 24, 27, .25);
        }
        .header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 18px;
            border-bottom: 2px solid #18181b;
        }
        .eyebrow {
            margin: 0 0 3px;
            color: #a1a1aa;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
        h1 {
            margin: 0;
            font-family: "Playfair Display", Georgia, serif;
            font-size: 27px;
            line-height: 1.15;
        }
        .event-name {
            margin-top: 5px;
            font-size: 15px;
            font-weight: 700;
        }
        .meta {
            margin-top: 5px;
            color: #71717a;
            font-size: 11px;
        }
        .summary {
            flex: none;
            min-width: 150px;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            padding: 12px 14px;
            text-align: right;
        }
        .summary strong {
            display: block;
            font-size: 25px;
            line-height: 1;
        }
        .summary span {
            display: block;
            margin-top: 4px;
            color: #71717a;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .filter-note {
            margin: 12px 0 0;
            padding: 8px 11px;
            border-radius: 8px;
            background: #fffbeb;
            color: #92400e;
            font-size: 11px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
        }
        .guest-card {
            display: flex;
            gap: 13px;
            break-inside: avoid;
            page-break-inside: avoid;
            border: 1px solid #e4e4e7;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }
        .qr-wrap {
            flex: none;
            width: 106px;
            text-align: center;
        }
        .qr-code {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100px;
            height: 100px;
            margin: 0 auto;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            background: #fff;
            color: #a1a1aa;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .08em;
        }
        .qr-code img, .qr-code canvas {
            display: block;
            width: 94px !important;
            height: 94px !important;
        }
        .qr-code .fallback { padding: 5px; word-break: break-all; font-weight: 500; letter-spacing: 0; }
        .qr-label {
            margin-top: 4px;
            color: #a1a1aa;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .09em;
        }
        .guest-details { min-width: 0; flex: 1; }
        .guest-name {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.25;
        }
        .guest-id {
            margin-top: 2px;
            color: #71717a;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 10px;
        }
        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 6px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 2px 7px;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.3;
        }
        .badge-single { background: #dbeafe; color: #1d4ed8; }
        .badge-double { background: #f3e8ff; color: #7e22ce; }
        .badge-pending { background: #fef9c3; color: #a16207; }
        .badge-sent, .badge-attending { background: #dcfce7; color: #15803d; }
        .badge-failed, .badge-not_attending { background: #fee2e2; color: #b91c1c; }
        .badge-delivered { background: #dbeafe; color: #1d4ed8; }
        .badge-neutral { background: #f4f4f5; color: #52525b; }
        .details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 3px 10px;
            margin-top: 8px;
            font-size: 10px;
        }
        .details div { min-width: 0; }
        .details dt {
            color: #a1a1aa;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
        }
        .details dd {
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #3f3f46;
            font-weight: 600;
        }
        .qr-value {
            margin-top: 7px;
            padding-top: 6px;
            border-top: 1px dashed #e4e4e7;
            color: #71717a;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 9px;
        }
        .empty {
            padding: 60px 20px;
            text-align: center;
            color: #a1a1aa;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e4e4e7;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            color: #a1a1aa;
            font-size: 9px;
        }
        @media print {
            @page { size: A4; margin: 10mm; }
            body { background: #fff; font-size: 11px; }
            .no-print { display: none !important; }
            .sheet {
                max-width: none;
                margin: 0;
                padding: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }
            .header { padding-bottom: 12px; }
            h1 { font-size: 22px; }
            .grid { gap: 8px; margin-top: 12px; }
            .guest-card { padding: 9px; gap: 9px; border-radius: 8px; }
            .qr-wrap { width: 88px; }
            .qr-code { width: 84px; height: 84px; }
            .qr-code img, .qr-code canvas { width: 78px !important; height: 78px !important; }
            .details { font-size: 9px; }
            .footer { margin-top: 12px; }
        }
        @media (max-width: 720px) {
            .sheet { margin: 12px; padding: 18px; }
            .header { display: block; }
            .summary { margin-top: 14px; text-align: left; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <a href="{{ route('events.invitations.index', $event) }}">
            <i class="fas fa-arrow-left"></i> Back to invitations
        </a>
        <button type="button" onclick="window.print()">
            <i class="fas fa-print"></i> Print / Save PDF
        </button>
    </div>

    <main class="sheet">
        <header class="header">
            <div>
                <p class="eyebrow">Invitation Guest List</p>
                <h1>{{ $event->name }}</h1>
                <div class="event-name">QR Barcode Export</div>
                <div class="meta">
                    {{ optional($event->event_date)->format('l, d M Y') }}
                    @if($event->location) &middot; {{ $event->location }} @endif
                    @if($event->hall) &middot; {{ $event->hall }} @endif
                </div>
                <div class="meta">Generated {{ now()->format('d M Y, H:i') }}</div>
            </div>
            <div class="summary">
                <strong>{{ $invitations->count() }}</strong>
                <span>Guests</span>
            </div>
        </header>

        @if(request()->hasAny(['search', 'send_status', 'rsvp']))
        <p class="filter-note">This export reflects the search and filters applied on the invitations page.</p>
        @endif

        @if($invitations->isEmpty())
        <div class="empty">
            No eligible guests found. Guests appear here after their paid amount meets the invitation minimum.
        </div>
        @else
        <section class="grid">
            @foreach($invitations as $invitation)
            @php
                $guest = $invitation->guest;
                $paidCardType = $event->cardTypeForAmount((float) $guest->paid_amount);
                $qrCode = $invitation->qr_code_data ?: $guest->unique_id;
            @endphp
            <article class="guest-card">
                <div class="qr-wrap">
                    <div class="qr-code" data-qr-code="{{ $qrCode }}">
                        <span class="fallback">{{ $qrCode }}</span>
                    </div>
                    <div class="qr-label">Scan at entrance</div>
                </div>

                <div class="guest-details">
                    <h2 class="guest-name">{{ $guest->full_name }}</h2>
                    <div class="guest-id">{{ $guest->unique_id }}</div>

                    <div class="badges">
                        <span class="badge badge-{{ $paidCardType }}">{{ ucfirst($paidCardType) }}</span>
                        <span class="badge badge-{{ $invitation->rsvp_status }}">
                            {{ $invitation->rsvp_status === 'attending' ? 'Nitafika' : ($invitation->rsvp_status === 'not_attending' ? 'Sitafika' : 'RSVP Pending') }}
                        </span>
                        <span class="badge badge-{{ $invitation->send_status }}">{{ ucfirst($invitation->send_status) }}</span>
                    </div>

                    <dl class="details">
                        <div>
                            <dt>Phone</dt>
                            <dd>{{ $guest->phone_number ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt>Paid</dt>
                            <dd>{{ number_format((float) $guest->paid_amount) }} TZS</dd>
                        </div>
                        <div>
                            <dt>Table</dt>
                            <dd>{{ $guest->table_number ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt>Category</dt>
                            <dd>{{ $guest->category ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt>Pledged</dt>
                            <dd>{{ number_format((float) $guest->amount_contributed) }} TZS</dd>
                        </div>
                        <div>
                            <dt>Attendance</dt>
                            <dd>{{ $invitation->attendance_status === 'attended' ? 'Checked in' : 'Not checked in' }}</dd>
                        </div>
                    </dl>

                    <div class="qr-value">QR: {{ $qrCode }}</div>
                </div>
            </article>
            @endforeach
        </section>
        @endif

        <footer class="footer">
            <span>{{ $event->name }} &middot; Invitation QR list</span>
            <span>Only guests who meet the paid invitation minimum are included.</span>
        </footer>
    </main>

    <script>
        function loadScriptWithFallback(urls, onready) {
            var index = 0;

            function next() {
                if (index >= urls.length) {
                    console.error('Could not load the QR generator from any CDN mirror.');
                    return;
                }

                var script = document.createElement('script');
                script.src = urls[index++];
                script.onload = function () { if (onready) onready(); };
                script.onerror = next;
                document.head.appendChild(script);
            }

            next();
        }

        function renderQrCodes() {
            if (typeof QRCode === 'undefined') {
                return;
            }

            document.querySelectorAll('[data-qr-code]').forEach(function (element) {
                var value = element.getAttribute('data-qr-code');

                if (!value) {
                    return;
                }

                element.innerHTML = '';
                new QRCode(element, {
                    text: value,
                    width: 94,
                    height: 94,
                    colorDark: '#18181b',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        }

        loadScriptWithFallback([
            'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',
            'https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js',
            'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js'
        ], renderQrCodes);
    </script>
</body>
</html>
