<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NiaLike · Countdown Card - {{ $event->name }}</title>
    @include('partials.head-assets')
    <style>
        .countdown-canvas { display: block; width: 100%; height: auto; }
        .share-card-shadow { box-shadow: 0 30px 80px -20px rgba(24, 24, 27, .45); }
    </style>
</head>
<body class="min-h-screen bg-surface-100 text-surface-900">

<div class="max-w-5xl mx-auto px-4 py-6 md:py-10">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('events.show', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to {{ $event->name }}
            </a>
            <h1 class="font-display text-2xl font-bold text-surface-900 mt-2">Countdown Card</h1>
            <p class="text-sm text-surface-500">A shareable motivation card for WhatsApp.</p>
        </div>
        <span class="badge bg-green-100 text-green-700">
            <i class="fab fa-whatsapp mr-1"></i> WhatsApp ready
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,460px)_1fr] gap-8 items-start">

        <div class="lg:sticky lg:top-6">
            <div class="rounded-2xl overflow-hidden share-card-shadow ring-1 ring-surface-200 bg-surface-900">
                <canvas id="countdownCard" width="1080" height="1350" class="countdown-canvas" aria-label="Countdown card for {{ $event->name }}"></canvas>
            </div>
            <p class="text-xs text-surface-400 mt-3 text-center">
                1080 × 1350 PNG &middot; ready for WhatsApp status, groups and chats
            </p>
        </div>

        <div class="space-y-5">
            <div class="card-surface p-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0"
                        style="background: {{ ($event->card_accent_color ?: '#d4af37') }}1a;">
                        <i class="fas fa-hourglass-half text-xl" style="color: {{ $event->card_accent_color ?: '#d4af37' }};"></i>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-surface-400">Days remaining</div>
                        <div class="text-3xl font-bold text-surface-900">{{ $daysRemaining }}</div>
                    </div>
                </div>

                <div class="mt-5 rounded-xl bg-surface-50 border border-surface-100 p-4">
                    <div class="text-xs uppercase tracking-wide text-surface-400 mb-1">Caption</div>
                    <p id="captionText" class="text-sm text-surface-600 leading-relaxed">{{ $caption }}</p>
                </div>
            </div>

            <div class="card-surface p-6">
                <h2 class="font-semibold text-surface-900 mb-1">Share it</h2>
                <p class="text-sm text-surface-500 mb-4">Download the image, then share it directly to WhatsApp.</p>

                <div class="space-y-3">
                    <button type="button" id="shareBtn" class="btn btn-success btn-block py-3">
                        <i class="fab fa-whatsapp text-lg"></i> Share to WhatsApp
                    </button>
                    <button type="button" id="downloadBtn" class="btn btn-outline btn-block py-3">
                        <i class="fas fa-download"></i> Download PNG
                    </button>
                    <button type="button" id="copyBtn" class="btn btn-secondary btn-block py-3">
                        <i class="fas fa-copy"></i> Copy Caption
                    </button>
                </div>

                <p id="shareStatus" class="hidden mt-4 text-sm text-green-600" role="status"></p>
            </div>

            <div class="card-surface p-6">
                <h2 class="font-semibold text-surface-900 mb-3 flex items-center gap-2">
                    <i class="fas fa-lightbulb text-gold-500"></i> Tips
                </h2>
                <ul class="text-sm text-surface-500 space-y-2 list-disc list-inside">
                    <li>On your phone, “Share to WhatsApp” opens the native share sheet with the image attached.</li>
                    <li>On a computer, download the PNG and attach it to your WhatsApp message.</li>
                    <li>The caption is copied separately so you can paste it with the image.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@php
    $countdownData = [
        'name' => $event->name,
        'date' => $event->event_date->format('l, d M Y'),
        'time' => $event->event_time,
        'location' => $event->location,
        'hall' => $event->hall,
        'days' => $daysRemaining,
        'accent' => $event->card_accent_color ?: '#d4af37',
        'slug' => $event->slug,
        'caption' => $caption,
    ];
@endphp

<script>
    const countdownData = @json($countdownData);

    (function () {
        const canvas = document.getElementById('countdownCard');
        const ctx = canvas.getContext('2d');
        const W = canvas.width;
        const H = canvas.height;
        const accent = /^#[0-9a-f]{6}$/i.test(countdownData.accent) ? countdownData.accent : '#d4af37';
        const accentRgb = hexToRgb(accent);
        const days = Number(countdownData.days) || 0;

        const displayDate = countdownData.time
            ? countdownData.date + ' • ' + countdownData.time
            : countdownData.date;

        const displayVenue = countdownData.hall
            ? countdownData.location + ' — ' + countdownData.hall
            : countdownData.location;

        function hexToRgb(hex) {
            hex = hex.replace('#', '');
            if (hex.length === 3) {
                hex = hex.split('').map(function (c) { return c + c; }).join('');
            }
            const value = parseInt(hex, 16);
            return {
                r: (value >> 16) & 255,
                g: (value >> 8) & 255,
                b: value & 255,
            };
        }

        function rgba(hex, alpha) {
            const c = hexToRgb(hex);
            return 'rgba(' + c.r + ',' + c.g + ',' + c.b + ',' + alpha + ')';
        }

        function roundRect(x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r);
            ctx.arcTo(x + w, y + h, x, y + h, r);
            ctx.arcTo(x, y + h, x, y, r);
            ctx.arcTo(x, y, x + w, y, r);
            ctx.closePath();
        }

        function radialGlow(x, y, radius, color, alpha) {
            const gradient = ctx.createRadialGradient(x, y, 0, x, y, radius);
            gradient.addColorStop(0, rgba(color, alpha));
            gradient.addColorStop(1, rgba(color, 0));
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, W, H);
        }

        function line(x1, y1, x2, y2, color, width) {
            ctx.beginPath();
            ctx.moveTo(x1, y1);
            ctx.lineTo(x2, y2);
            ctx.strokeStyle = color;
            ctx.lineWidth = width;
            ctx.stroke();
        }

        function diamond(x, y, size, color) {
            ctx.save();
            ctx.translate(x, y);
            ctx.rotate(Math.PI / 4);
            ctx.fillStyle = color;
            ctx.fillRect(-size / 2, -size / 2, size, size);
            ctx.restore();
        }

        function sparkle(x, y, size, alpha) {
            ctx.save();
            ctx.translate(x, y);
            ctx.fillStyle = rgba(accent, alpha);
            ctx.beginPath();
            ctx.moveTo(0, -size);
            ctx.quadraticCurveTo(size * .15, -size * .15, size, 0);
            ctx.quadraticCurveTo(size * .15, size * .15, 0, size);
            ctx.quadraticCurveTo(-size * .15, size * .15, -size, 0);
            ctx.quadraticCurveTo(-size * .15, -size * .15, 0, -size);
            ctx.fill();
            ctx.restore();
        }

        function wrapLines(text, maxWidth) {
            const words = String(text).split(/\s+/);
            const lines = [];
            let current = '';

            words.forEach(function (word) {
                const test = current ? current + ' ' + word : word;
                if (ctx.measureText(test).width > maxWidth && current) {
                    lines.push(current);
                    current = word;
                } else {
                    current = test;
                }
            });

            if (current) lines.push(current);
            return lines;
        }

        function centeredText(text, y, font, color, letterSpacing) {
            ctx.font = font;
            ctx.fillStyle = color;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            if (letterSpacing) {
                const chars = String(text).split('');
                const widths = chars.map(function (char) { return ctx.measureText(char).width; });
                const total = widths.reduce(function (sum, width) { return sum + width; }, 0)
                    + (chars.length - 1) * letterSpacing;
                let x = W / 2 - total / 2;

                chars.forEach(function (char, index) {
                    ctx.textAlign = 'left';
                    ctx.fillText(char, x, y);
                    x += widths[index] + letterSpacing;
                });
            } else {
                ctx.fillText(String(text), W / 2, y);
            }
        }

        function fitFont(text, weight, family, startSize, maxWidth, minSize) {
            let size = startSize;
            do {
                ctx.font = weight + ' ' + size + 'px ' + family;
                if (ctx.measureText(text).width <= maxWidth || size <= minSize) break;
                size -= 4;
            } while (size > minSize);

            return size;
        }

        function drawFrame() {
            const bg = ctx.createLinearGradient(0, 0, W, H);
            bg.addColorStop(0, '#0b0b0c');
            bg.addColorStop(.48, '#191512');
            bg.addColorStop(1, '#090909');
            ctx.fillStyle = bg;
            ctx.fillRect(0, 0, W, H);

            radialGlow(120, 80, 620, accent, .20);
            radialGlow(W - 40, H - 40, 760, accent, .13);
            radialGlow(W / 2, 640, 440, accent, .10);

            ctx.strokeStyle = rgba(accent, .12);
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.arc(W + 100, 100, 360, 0, Math.PI * 2);
            ctx.stroke();
            ctx.beginPath();
            ctx.arc(-120, H - 80, 430, 0, Math.PI * 2);
            ctx.stroke();

            ctx.strokeStyle = rgba(accent, .62);
            ctx.lineWidth = 2;
            roundRect(54, 54, W - 108, H - 108, 34);
            ctx.stroke();

            ctx.strokeStyle = rgba(accent, .20);
            ctx.lineWidth = 1.2;
            roundRect(72, 72, W - 144, H - 144, 24);
            ctx.stroke();

            line(104, 150, 270, 150, rgba(accent, .35), 1);
            line(W - 104, 150, W - 270, 150, rgba(accent, .35), 1);
            line(104, H - 150, 270, H - 150, rgba(accent, .35), 1);
            line(W - 104, H - 150, W - 270, H - 150, rgba(accent, .35), 1);

            diamond(88, 88, 7, rgba(accent, .8));
            diamond(W - 88, 88, 7, rgba(accent, .8));
            diamond(88, H - 88, 7, rgba(accent, .8));
            diamond(W - 88, H - 88, 7, rgba(accent, .8));

            sparkle(188, 300, 16, .65);
            sparkle(W - 170, 420, 11, .45);
            sparkle(152, 1010, 9, .35);
            sparkle(W - 154, 930, 14, .5);
        }

        function drawTitle() {
            centeredText('COUNTDOWN TO', 205, '600 25px Inter, sans-serif', rgba(accent, .92), 6);

            let titleSize = 82;
            let titleLines;

            do {
                ctx.font = '700 ' + titleSize + 'px "Playfair Display", Georgia, serif';
                titleLines = wrapLines(countdownData.name, 830);
                if (titleLines.length <= 2 || titleSize <= 52) break;
                titleSize -= 4;
            } while (titleSize > 52);

            if (titleLines.length > 3) {
                titleLines = titleLines.slice(0, 3);
            }

            const lineHeight = titleSize * 1.12;
            const startY = 302 - ((titleLines.length - 1) * lineHeight) / 2;

            titleLines.forEach(function (titleLine, index) {
                centeredText(
                    titleLine,
                    startY + index * lineHeight,
                    '700 ' + titleSize + 'px "Playfair Display", Georgia, serif',
                    '#fffaf0'
                );
            });

            const dividerY = Math.max(455, startY + (titleLines.length - 1) * lineHeight + 72);
            line(W / 2 - 210, dividerY, W / 2 - 26, dividerY, rgba(accent, .55), 1.5);
            line(W / 2 + 26, dividerY, W / 2 + 210, dividerY, rgba(accent, .55), 1.5);
            diamond(W / 2, dividerY, 10, rgba(accent, .9));

            return dividerY;
        }

        function drawCountdown(dividerY) {
            centeredText('THE COUNTDOWN IS ON', dividerY + 62, '600 22px Inter, sans-serif', rgba(accent, .85), 5);

            const bigText = days === 0 ? 'TODAY' : String(days);
            const bigSize = fitFont(bigText, '800', '"Playfair Display", Georgia, serif', days === 0 ? 260 : 370, 820, 190);

            ctx.save();
            ctx.shadowColor = rgba(accent, .28);
            ctx.shadowBlur = 44;
            centeredText(bigText, 700, '800 ' + bigSize + 'px "Playfair Display", Georgia, serif', '#fffaf0');
            ctx.restore();

            const subText = days === 0
                ? 'IS THE DAY'
                : days === 1 ? 'DAY TO GO' : 'DAYS TO GO';

            centeredText(subText, 910, '600 36px Inter, sans-serif', rgba(accent, .95), 9);

            line(W / 2 - 90, 952, W / 2 - 18, 952, rgba(accent, .35), 1);
            line(W / 2 + 18, 952, W / 2 + 90, 952, rgba(accent, .35), 1);
            diamond(W / 2, 952, 7, rgba(accent, .65));
        }

        function drawDetails() {
            ctx.fillStyle = 'rgba(255,255,255,0.045)';
            roundRect(122, 1010, W - 244, 206, 22);
            ctx.fill();

            ctx.strokeStyle = rgba(accent, .24);
            ctx.lineWidth = 1.2;
            roundRect(122, 1010, W - 244, 206, 22);
            ctx.stroke();

            centeredText('SAVE THE DATE', 1042, '600 18px Inter, sans-serif', rgba(accent, .72), 5);

            const dateSize = fitFont(displayDate, '600', 'Inter, sans-serif', 32, 790, 22);
            centeredText(displayDate, 1090, '600 ' + dateSize + 'px Inter, sans-serif', '#fffaf0');

            const venueSize = fitFont(displayVenue, '400', 'Inter, sans-serif', 27, 790, 20);
            centeredText(displayVenue, 1146, '400 ' + venueSize + 'px Inter, sans-serif', 'rgba(255,250,240,0.72)');

            ctx.fillStyle = rgba(accent, .8);
            ctx.fillRect(W / 2 - 30, 1186, 60, 2);

            centeredText('SEE YOU THERE', 1250, '600 21px Inter, sans-serif', rgba(accent, .92), 7);
        }

        function draw() {
            ctx.clearRect(0, 0, W, H);
            drawFrame();
            const dividerY = drawTitle();
            drawCountdown(dividerY);
            drawDetails();
        }

        function getBlob() {
            return new Promise(function (resolve) {
                canvas.toBlob(function (blob) {
                    resolve(blob);
                }, 'image/png');
            });
        }

        function filename() {
            return 'countdown-' + (countdownData.slug || 'event') + '.png';
        }

        function setStatus(message, isError) {
            const status = document.getElementById('shareStatus');
            status.textContent = message;
            status.classList.remove('hidden', 'text-green-600', 'text-red-500');
            status.classList.add(isError ? 'text-red-500' : 'text-green-600');
        }

        async function downloadCard() {
            const blob = await getBlob();
            if (!blob) {
                setStatus('Could not create the image. Please try again.', true);
                return;
            }

            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename();
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);

            setStatus('Image downloaded. You can now attach it in WhatsApp.', false);
        }

        async function shareCard() {
            const blob = await getBlob();
            if (!blob) {
                setStatus('Could not create the image. Please try again.', true);
                return;
            }

            const file = new File([blob], filename(), { type: 'image/png' });

            if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                try {
                    await navigator.share({
                        files: [file],
                        title: 'Countdown to ' + countdownData.name,
                        text: countdownData.caption,
                    });
                    setStatus('Shared successfully.', false);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        setStatus('Sharing was not completed. Try downloading the image instead.', true);
                    }
                }
                return;
            }

            await downloadCard();
            window.open('https://wa.me/?text=' + encodeURIComponent(countdownData.caption), '_blank', 'noopener');
            setStatus('Image downloaded. Attach it to the WhatsApp message that just opened.', false);
        }

        async function copyCaption() {
            try {
                await navigator.clipboard.writeText(countdownData.caption);
                setStatus('Caption copied.', false);
            } catch (error) {
                const textarea = document.createElement('textarea');
                textarea.value = countdownData.caption;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                textarea.remove();
                setStatus('Caption copied.', false);
            }
        }

        document.getElementById('downloadBtn').addEventListener('click', downloadCard);
        document.getElementById('shareBtn').addEventListener('click', shareCard);
        document.getElementById('copyBtn').addEventListener('click', copyCaption);

        draw();

        if (document.fonts && document.fonts.ready) {
            Promise.all([
                document.fonts.load('600 25px Inter'),
                document.fonts.load('700 82px "Playfair Display"'),
                document.fonts.load('800 370px "Playfair Display"'),
            ]).finally(draw);
        }
    })();
</script>
</body>
</html>
