@php
    $accent = $event->card_accent_color ?: '#e11d48';
    $bg = $event->card_background_color ?: '#ffffff';
    $fg = $event->card_text_color ?: '#27272a';
    $coverUrl = $event->cover_image ? asset('storage/'.$event->cover_image) : null;
    $isPast = $event->event_date->isBefore(now()->startOfDay());
    $countdownLabel = $isPast
        ? 'Thank you for celebrating with us'
        : ($daysRemaining === 0 ? 'Today is the day' : $daysRemaining.' '.($daysRemaining === 1 ? 'day' : 'days').' to go');
    $rsvpLabel = $invitation->rsvp_status === 'attending'
        ? 'You confirmed attendance'
        : ($invitation->rsvp_status === 'not_attending' ? 'You declined' : 'Please respond');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $event->name }} - Your Invitation</title>
    @include('partials.head-assets')
    <style>
        :root {
            --accent: {{ $accent }};
            --invite-bg: {{ $bg }};
            --invite-fg: {{ $fg }};
        }
        body {
            background:
                radial-gradient(circle at 10% 0%, {{ $accent }}22, transparent 32rem),
                radial-gradient(circle at 95% 15%, {{ $accent }}18, transparent 28rem),
                #f6f3ef;
        }
        .invite-shell { max-width: 920px; margin: 0 auto; padding: 24px 16px 40px; }
        .hero {
            position: relative;
            min-height: 360px;
            overflow: hidden;
            border-radius: 28px;
            background: #1c1917;
            box-shadow: 0 28px 70px -32px rgba(24, 24, 27, .55);
            isolation: isolate;
        }
        .hero-media {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            transform: scale(1.02);
        }
        .hero-media::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(0, 0, 0, .18) 0%, rgba(0, 0, 0, .44) 48%, rgba(0, 0, 0, .78) 100%),
                linear-gradient(135deg, {{ $accent }}55, transparent 62%);
        }
        .hero-content {
            position: relative;
            z-index: 1;
            min-height: 360px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 28px;
            padding: 28px;
            color: #fff;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            align-self: flex-start;
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 999px;
            background: rgba(255, 255, 255, .13);
            backdrop-filter: blur(10px);
            padding: 7px 12px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .brand-mark {
            display: inline-flex;
            width: 24px;
            height: 24px;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            background: var(--accent);
            font-size: 11px;
        }
        .hero-main { max-width: 680px; }
        .eyebrow {
            margin: 0 0 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .76);
        }
        .hero h1 {
            margin: 0;
            font-family: "Playfair Display", Georgia, serif;
            font-size: clamp(2.15rem, 6vw, 4.1rem);
            line-height: 1.05;
            letter-spacing: -.02em;
        }
        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 18px;
            margin-top: 14px;
            font-size: 13px;
            color: rgba(255, 255, 255, .84);
        }
        .hero-meta i { color: var(--accent); margin-right: 5px; }
        .countdown {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            padding: 9px 14px;
            font-size: 12px;
            font-weight: 700;
            backdrop-filter: blur(8px);
        }
        .countdown-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 5px {{ $accent }}33;
        }
        .content { margin-top: 18px; display: grid; gap: 18px; }
        .panel {
            border: 1px solid color-mix(in srgb, var(--invite-fg) 12%, transparent);
            border-radius: 22px;
            background: var(--invite-bg);
            color: var(--invite-fg);
            box-shadow: 0 18px 45px -35px rgba(24, 24, 27, .45);
            padding: 24px;
        }
        .welcome {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }
        .welcome h2 {
            margin: 0;
            font-family: "Playfair Display", Georgia, serif;
            font-size: clamp(1.45rem, 4vw, 2rem);
            line-height: 1.2;
            color: var(--invite-fg);
        }
        .welcome p { margin: 7px 0 0; color: color-mix(in srgb, var(--invite-fg) 62%, transparent); font-size: 13px; }
        .guest-badge {
            flex: none;
            border: 1px solid color-mix(in srgb, var(--accent) 35%, transparent);
            border-radius: 16px;
            background: color-mix(in srgb, var(--accent) 14%, var(--invite-bg));
            color: var(--accent);
            padding: 10px 14px;
            text-align: center;
        }
        .guest-badge strong { display: block; font-size: 14px; text-transform: capitalize; }
        .guest-badge span { display: block; margin-top: 2px; font-size: 9px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; opacity: .7; }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .detail {
            border: 1px solid color-mix(in srgb, var(--invite-fg) 10%, transparent);
            border-radius: 14px;
            background: color-mix(in srgb, var(--invite-fg) 5%, var(--invite-bg));
            padding: 12px 14px;
        }
        .detail-label {
            display: flex;
            align-items: center;
            gap: 6px;
            color: color-mix(in srgb, var(--invite-fg) 52%, transparent);
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .11em;
            text-transform: uppercase;
        }
        .detail-label i { color: var(--accent); font-size: 10px; }
        .detail-value { margin-top: 5px; color: var(--invite-fg); font-size: 13px; font-weight: 600; line-height: 1.35; }
        .section-title {
            margin: 0 0 4px;
            font-family: "Playfair Display", Georgia, serif;
            font-size: 20px;
            color: var(--invite-fg);
        }
        .section-subtitle { margin: 0 0 16px; color: color-mix(in srgb, var(--invite-fg) 52%, transparent); font-size: 12px; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .btn-invite {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid transparent;
            border-radius: 12px;
            padding: 11px 16px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform .15s ease, background .15s ease;
        }
        .btn-invite:hover { transform: translateY(-1px); }
        .btn-primary-invite { background: var(--accent); color: #fff; }
        .btn-outline-invite {
            border-color: color-mix(in srgb, var(--invite-fg) 16%, transparent);
            background: var(--invite-bg);
            color: var(--invite-fg);
        }
        .btn-outline-invite:hover { background: color-mix(in srgb, var(--invite-fg) 7%, var(--invite-bg)); }
        .btn-rsvp-yes { background: #16a34a; color: #fff; }
        .btn-rsvp-no { background: #f4f4f5; color: #52525b; }
        .btn-rsvp-yes.is-selected { box-shadow: 0 0 0 3px #16a34a33; }
        .btn-rsvp-no.is-selected { box-shadow: 0 0 0 3px #a1a1aa33; }
        .rsvp-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 14px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--invite-fg) 7%, var(--invite-bg));
            padding: 6px 10px;
            color: color-mix(in srgb, var(--invite-fg) 72%, transparent);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .rsvp-status i { color: var(--accent); }
        .two-column { display: grid; grid-template-columns: 1.3fr .7fr; gap: 18px; }
        .qr-card { text-align: center; }
        .qr-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 150px;
            height: 150px;
            border: 1px solid color-mix(in srgb, var(--accent) 45%, transparent);
            border-radius: 16px;
            background: var(--invite-bg);
            padding: 8px;
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 10%, transparent);
        }
        .qr-box img, .qr-box canvas { width: 132px !important; height: 132px !important; }
        .qr-code-text {
            margin-top: 9px;
            color: color-mix(in srgb, var(--invite-fg) 52%, transparent);
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 9px;
            word-break: break-all;
        }
        .link-box {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .link-box input {
            min-width: 0;
            flex: 1;
            border: 1px solid color-mix(in srgb, var(--invite-fg) 14%, transparent);
            border-radius: 10px;
            background: color-mix(in srgb, var(--invite-fg) 4%, var(--invite-bg));
            padding: 9px 11px;
            color: color-mix(in srgb, var(--invite-fg) 62%, transparent);
            font-size: 10px;
        }
        .link-box button {
            border: 1px solid color-mix(in srgb, var(--invite-fg) 14%, transparent);
            border-radius: 10px;
            background: var(--invite-bg);
            padding: 0 12px;
            color: var(--invite-fg);
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }
        .description {
            color: color-mix(in srgb, var(--invite-fg) 68%, transparent);
            font-size: 13px;
            line-height: 1.7;
        }
        .footer {
            margin-top: 18px;
            text-align: center;
            color: #a1a1aa;
            font-size: 10px;
        }
        .footer strong { color: #71717a; }
        .flash {
            margin-bottom: 14px;
            border: 1px solid #bbf7d0;
            border-radius: 14px;
            background: #f0fdf4;
            padding: 12px 14px;
            color: #15803d;
            font-size: 12px;
            font-weight: 600;
        }
        @media (max-width: 640px) {
            .invite-shell { padding: 10px 10px 28px; }
            .hero, .hero-content { min-height: 330px; }
            .hero-content { padding: 20px; }
            .panel { padding: 18px; border-radius: 18px; }
            .welcome { display: block; }
            .guest-badge { display: inline-block; margin-top: 14px; }
            .two-column { grid-template-columns: 1fr; }
            .details-grid { grid-template-columns: 1fr 1fr; }
            .link-box { flex-direction: column; }
            .link-box button { padding: 9px 12px; }
        }
    </style>
</head>
<body class="antialiased">
    <div class="invite-shell">
        <section class="hero">
            <div class="hero-media" style="background-image: url('{{ $coverUrl ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%221%22 height=%221%22%3E%3C/svg%3E' }}'); background-color: {{ $coverUrl ? '#1c1917' : $accent }};"></div>
            <div class="hero-content">
                <div class="brand">
                    <span class="brand-mark"><i class="fas fa-envelope-open-text"></i></span>
                    CardMS Invitation
                </div>

                <div class="hero-main">
                    <p class="eyebrow">You are cordially invited</p>
                    <h1>{{ $event->name }}</h1>
                    <div class="hero-meta">
                        <span><i class="fas fa-calendar-day"></i>{{ $event->event_date->format('l, d M Y') }}</span>
                        @if($event->event_time)
                        <span><i class="fas fa-clock"></i>{{ $event->event_time }}</span>
                        @endif
                        <span><i class="fas fa-location-dot"></i>{{ $event->location }}</span>
                    </div>
                    <div class="countdown">
                        <span class="countdown-dot"></span>
                        {{ $countdownLabel }}
                    </div>
                </div>
            </div>
        </section>

        <div class="content">
            @if(session('success'))
            <div class="flash"><i class="fas fa-circle-check mr-1"></i>{{ session('success') }}</div>
            @endif

            <section class="panel">
                <div class="welcome">
                    <div>
                        <p class="eyebrow" style="color: {{ $accent }};">Personal invitation</p>
                        <h2>Dear {{ $guest->full_name }},</h2>
                        <p>We would be honoured to have you celebrate this special day with us.</p>
                    </div>
                    <div class="guest-badge">
                        <strong>{{ ucfirst($cardType) }}</strong>
                        <span>{{ $cardType === 'double' ? 'Couple Card' : 'Single Card' }}</span>
                    </div>
                </div>

                <div class="details-grid mt-5">
                    <div class="detail">
                        <div class="detail-label"><i class="fas fa-signature"></i> Guest ID</div>
                        <div class="detail-value">{{ $guest->unique_id }}</div>
                    </div>
                    <div class="detail">
                        <div class="detail-label"><i class="fas fa-chair"></i> Table</div>
                        <div class="detail-value">{{ $guest->table_number ?: 'To be advised' }}</div>
                    </div>
                    <div class="detail">
                        <div class="detail-label"><i class="fas fa-map-pin"></i> Venue</div>
                        <div class="detail-value">{{ $event->location }}{{ $event->hall ? ' · '.$event->hall : '' }}</div>
                    </div>
                    <div class="detail">
                        <div class="detail-label"><i class="fas fa-calendar-check"></i> Date &amp; Time</div>
                        <div class="detail-value">{{ $event->event_date->format('d M Y') }}{{ $event->event_time ? ' · '.$event->event_time : '' }}</div>
                    </div>
                </div>

                @if($event->description)
                <div class="mt-5 border-t pt-4" style="border-color: color-mix(in srgb, var(--invite-fg) 12%, transparent);">
                    <p class="description">{!! nl2br(e($event->description)) !!}</p>
                </div>
                @endif

                <div class="actions mt-6">
                    <a href="{{ route('invitation.public.card', $invitation->qr_code_data) }}" target="_blank" class="btn-invite btn-primary-invite">
                        <i class="fas fa-download"></i> Download / Print Card
                    </a>
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(trim($event->location.' '.($event->hall ?? ''))) }}" target="_blank" rel="noopener" class="btn-invite btn-outline-invite">
                        <i class="fas fa-diamond-turn-right"></i> Open Location
                    </a>
                </div>
            </section>

            <div class="two-column">
                <section class="panel">
                    <h3 class="section-title">Will you be joining us?</h3>
                    <p class="section-subtitle">Your response helps us prepare a seat for you.</p>
                    <div class="rsvp-status"><i class="fas fa-circle-info"></i>{{ $rsvpLabel }}</div>

                    <div class="actions">
                        <form method="POST" action="{{ route('invitation.public.rsvp', $invitation->qr_code_data) }}">
                            @csrf
                            <input type="hidden" name="rsvp_status" value="attending">
                            <button type="submit" class="btn-invite btn-rsvp-yes {{ $invitation->rsvp_status === 'attending' ? 'is-selected' : '' }}">
                                <i class="fas fa-check"></i> Yes, I will attend
                            </button>
                        </form>
                        <form method="POST" action="{{ route('invitation.public.rsvp', $invitation->qr_code_data) }}">
                            @csrf
                            <input type="hidden" name="rsvp_status" value="not_attending">
                            <button type="submit" class="btn-invite btn-rsvp-no {{ $invitation->rsvp_status === 'not_attending' ? 'is-selected' : '' }}">
                                <i class="fas fa-xmark"></i> Sorry, I cannot attend
                            </button>
                        </form>
                    </div>

                    <div class="mt-5 rounded-xl border border-dashed p-3" style="border-color: color-mix(in srgb, var(--invite-fg) 18%, transparent); background: color-mix(in srgb, var(--invite-fg) 4%, var(--invite-bg));">
                        <p class="text-[10px] font-bold uppercase tracking-[.12em]" style="color: color-mix(in srgb, var(--invite-fg) 52%, transparent);">Your private link</p>
                        <p class="mt-1 text-[11px]" style="color: color-mix(in srgb, var(--invite-fg) 62%, transparent);">Save this link to reopen your invitation anytime.</p>
                        <div class="link-box">
                            <input id="publicInviteUrl" type="text" readonly value="{{ $publicUrl }}">
                            <button type="button" onclick="copyPublicInviteUrl()"><i class="fas fa-copy mr-1"></i> Copy</button>
                        </div>
                    </div>
                </section>

                <section class="panel qr-card">
                    <h3 class="section-title">Your QR code</h3>
                    <p class="section-subtitle">Show this at the entrance.</p>
                    <div class="qr-box">
                        <div id="publicQrCode" data-qr="{{ $invitation->qr_code_data }}"></div>
                    </div>
                    <div class="qr-code-text">{{ $invitation->qr_code_data }}</div>
                    <p class="mt-3 text-[10px] leading-relaxed" style="color: color-mix(in srgb, var(--invite-fg) 52%, transparent);">
                        Keep this invitation private. The QR code is unique to you and will be used for check-in.
                    </p>
                </section>
            </div>

            <div class="footer">
                <strong>{{ $event->name }}</strong> &middot; Powered by CardMS
            </div>
        </div>
    </div>

    <script>
        function copyPublicInviteUrl() {
            const input = document.getElementById('publicInviteUrl');
            if (!input) return;

            input.select();
            input.setSelectionRange(0, input.value.length);

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(input.value);
            } else {
                document.execCommand('copy');
            }

            window.alert('Invitation link copied.');
        }

        loadScriptWithFallback([
            'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',
            'https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js',
            'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js'
        ], function () {
            const holder = document.getElementById('publicQrCode');
            if (!holder) return;

            new QRCode(holder, {
                text: holder.dataset.qr,
                width: 132,
                height: 132,
                colorDark: {!! json_encode($fg) !!},
                colorLight: {!! json_encode($bg) !!},
                correctLevel: QRCode.CorrectLevel.H
            });
        });
    </script>
</body>
</html>
