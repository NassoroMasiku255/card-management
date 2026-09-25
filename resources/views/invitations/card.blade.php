@php
    $tpl = $event->card_template ?? 'elegant';
    $bg = $event->card_background_color ?: '#ffffff';
    $fg = $event->card_text_color ?: '#333333';
    $accent = $event->card_accent_color ?: '#d4af37';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Card - {{ $guest->full_name }}</title>
    @include('partials.head-assets')
    <style>
        @media print {
            body { margin: 0; padding: 0; background: #fff !important; }
            .no-print { display: none; }
            .card-container { box-shadow: none !important; }
        }
        .ornament { color: {{ $accent }}; }
    </style>
</head>
<body class="bg-surface-100 min-h-screen flex items-center justify-center p-4 md:p-8">

    <div class="no-print fixed top-4 right-4 flex gap-2 z-20">
        <button onclick="window.print()" class="btn btn-primary btn-sm shadow-soft-lg">
            <i class="fas fa-print"></i> Print
        </button>
        <a href="{{ route('events.invitations.index', $event) }}" class="btn btn-outline btn-sm shadow-soft-lg bg-white">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    {{-- =====================================================
         ELEGANT — gold foil, formal double border, serif type
    ====================================================== --}}
    @if($tpl === 'elegant')
    <div class="card-container relative max-w-md w-full rounded-2xl shadow-2xl overflow-hidden"
        style="background-color: {{ $bg }}; color: {{ $fg }};">
        <div class="absolute inset-3 border rounded-xl pointer-events-none" style="border-color: {{ $accent }}66;"></div>
        <div class="absolute inset-0" style="background: radial-gradient(circle at 50% 0%, {{ $accent }}14, transparent 60%);"></div>

        <div class="relative text-center pt-10 px-8" style="border-top: 4px solid {{ $accent }};">
            <svg class="w-6 h-6 mx-auto mb-3 ornament" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M12 2l1.8 5.6L19 9l-5.2 1.4L12 16l-1.8-5.6L5 9l5.2-1.4L12 2z"/></svg>
            <div class="text-[11px] uppercase tracking-[0.3em] mb-2 opacity-70">You Are Cordially Invited To</div>
            <h1 class="font-display text-2xl font-bold" style="color: {{ $accent }};">{{ $event->name }}</h1>
        </div>

        <div class="relative px-8 py-6 text-center space-y-4">
            <div class="py-2">
                <div class="text-xs opacity-70 mb-1 tracking-wide">Dear</div>
                <div class="font-display text-xl font-semibold">{{ $guest->full_name }}</div>
                <div class="text-xs mt-1 opacity-60">{{ $guest->card_type === 'double' ? 'Couple Invitation' : 'Single Invitation' }}</div>
            </div>

            <div class="flex items-center justify-center gap-3 py-1" style="color: {{ $accent }};">
                <span class="h-px w-10" style="background: {{ $accent }}66;"></span>
                <svg class="w-3.5 h-3.5 ornament" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 7.4H22l-6 4.4 2.4 7.4L12 16.8 5.6 21.2 8 13.8 2 9.4h7.6z"/></svg>
                <span class="h-px w-10" style="background: {{ $accent }}66;"></span>
            </div>

            <div class="border-t border-b py-4 space-y-2" style="border-color: {{ $accent }}33;">
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Date:</span>
                    <span class="font-semibold">{{ $event->event_date->format('d M Y') }}</span>
                </div>
                @if($event->event_time)
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Time:</span>
                    <span class="font-semibold">{{ $event->event_time }}</span>
                </div>
                @endif
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Venue:</span>
                    <span class="font-semibold">{{ $event->location }}</span>
                </div>
                @if($event->hall)
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Hall:</span>
                    <span class="font-semibold">{{ $event->hall }}</span>
                </div>
                @endif
            </div>

            <div class="py-4">
                <div class="inline-block bg-white p-3 rounded-xl shadow-soft">
                    <div id="qr-code" class="flex justify-center"></div>
                </div>
            </div>
        </div>

        <div class="relative text-center pb-6 px-8">
            <div class="text-xs opacity-50">Please present this QR code at the entrance</div>
        </div>
        <div class="h-2" style="background-color: {{ $accent }};"></div>
    </div>

    {{-- =====================================================
         MODERN — minimal, clean geometric lines
    ====================================================== --}}
    @elseif($tpl === 'modern')
    <div class="card-container max-w-md w-full rounded-2xl shadow-2xl overflow-hidden"
        style="background-color: {{ $bg }}; color: {{ $fg }};">
        <div class="h-1.5 w-full" style="background-color: {{ $accent }};"></div>

        <div class="text-left pt-10 px-9">
            <div class="w-8 h-1 mb-4" style="background-color: {{ $accent }};"></div>
            <div class="text-[11px] uppercase tracking-[0.25em] mb-1 opacity-60">Invitation</div>
            <h1 class="text-2xl font-bold tracking-tight">{{ $event->name }}</h1>
        </div>

        <div class="px-9 py-7 space-y-6">
            <div>
                <div class="text-xs opacity-50 mb-1">Guest</div>
                <div class="text-lg font-semibold">{{ $guest->full_name }}</div>
                <div class="text-xs mt-0.5 opacity-50">{{ $guest->card_type === 'double' ? 'Couple Invitation' : 'Single Invitation' }}</div>
            </div>

            <div class="grid grid-cols-2 gap-4 border-t pt-5" style="border-color: {{ $accent }}22;">
                <div>
                    <div class="text-[11px] uppercase tracking-wide opacity-50">Date</div>
                    <div class="font-semibold text-sm mt-0.5">{{ $event->event_date->format('d M Y') }}</div>
                </div>
                @if($event->event_time)
                <div>
                    <div class="text-[11px] uppercase tracking-wide opacity-50">Time</div>
                    <div class="font-semibold text-sm mt-0.5">{{ $event->event_time }}</div>
                </div>
                @endif
                <div class="col-span-2">
                    <div class="text-[11px] uppercase tracking-wide opacity-50">Venue</div>
                    <div class="font-semibold text-sm mt-0.5">{{ $event->location }}@if($event->hall) — {{ $event->hall }}@endif</div>
                </div>
            </div>

            <div class="flex items-center gap-6 pt-3">
                <div class="inline-block bg-white p-2.5 rounded-lg shadow-soft">
                    <div id="qr-code"></div>
                </div>
                <div>
                    <div class="text-xs opacity-50">Present this code at the entrance</div>
                </div>
            </div>
        </div>
        <div class="h-1.5 w-full" style="background-color: {{ $accent }};"></div>
    </div>

    {{-- =====================================================
         FLORAL — flowers, leaves, romantic pastel wash
    ====================================================== --}}
    @elseif($tpl === 'floral')
    <div class="card-container relative max-w-md w-full rounded-[1.75rem] shadow-2xl overflow-hidden"
        style="background-color: {{ $bg }}; color: {{ $fg }};">
        <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(circle at 15% 10%, {{ $accent }}22, transparent 45%), radial-gradient(circle at 85% 90%, {{ $accent }}22, transparent 45%);"></div>

        <!-- Corner flowers -->
        <svg class="absolute -top-2 -left-3 w-24 h-24 ornament opacity-90" viewBox="0 0 100 100" fill="none">
            <g fill="currentColor">
                <circle cx="35" cy="30" r="10" opacity=".85"/>
                <circle cx="20" cy="18" r="8" opacity=".65"/>
                <circle cx="48" cy="15" r="7" opacity=".55"/>
                <circle cx="15" cy="40" r="6" opacity=".5"/>
            </g>
            <path d="M10 55 Q30 40 45 55" stroke="currentColor" stroke-width="2" fill="none" opacity=".4"/>
            <path d="M10 55 Q20 65 15 78" stroke="currentColor" stroke-width="2" fill="none" opacity=".4"/>
        </svg>
        <svg class="absolute -top-3 -right-4 w-28 h-28 ornament opacity-80 scale-x-[-1]" viewBox="0 0 100 100" fill="none">
            <g fill="currentColor">
                <circle cx="35" cy="30" r="9" opacity=".8"/>
                <circle cx="20" cy="20" r="7" opacity=".6"/>
                <circle cx="45" cy="18" r="6" opacity=".5"/>
            </g>
            <path d="M10 50 Q28 38 42 50" stroke="currentColor" stroke-width="2" fill="none" opacity=".4"/>
        </svg>
        <svg class="absolute -bottom-4 -right-3 w-24 h-24 ornament opacity-80" viewBox="0 0 100 100" fill="none">
            <g fill="currentColor">
                <circle cx="65" cy="70" r="9" opacity=".8"/>
                <circle cx="80" cy="80" r="7" opacity=".6"/>
                <circle cx="55" cy="82" r="6" opacity=".5"/>
            </g>
            <path d="M88 55 Q72 66 60 55" stroke="currentColor" stroke-width="2" fill="none" opacity=".4"/>
        </svg>

        <div class="relative text-center pt-10 px-8">
            <div class="text-[11px] uppercase tracking-[0.3em] mb-2 opacity-70">You Are Cordially Invited To</div>
            <h1 class="font-display italic text-2xl font-bold" style="color: {{ $accent }};">{{ $event->name }}</h1>
        </div>

        <div class="relative px-8 py-5 text-center space-y-4">
            <div class="py-2">
                <div class="text-sm opacity-70 mb-1">Dear</div>
                <div class="font-display text-xl font-semibold">{{ $guest->full_name }}</div>
                <div class="text-xs mt-1 opacity-60">{{ $guest->card_type === 'double' ? 'Couple Invitation' : 'Single Invitation' }}</div>
            </div>

            <div class="rounded-2xl py-4 space-y-2" style="background: {{ $accent }}0f;">
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Date:</span>
                    <span class="font-semibold">{{ $event->event_date->format('d M Y') }}</span>
                </div>
                @if($event->event_time)
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Time:</span>
                    <span class="font-semibold">{{ $event->event_time }}</span>
                </div>
                @endif
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Venue:</span>
                    <span class="font-semibold">{{ $event->location }}</span>
                </div>
                @if($event->hall)
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Hall:</span>
                    <span class="font-semibold">{{ $event->hall }}</span>
                </div>
                @endif
            </div>

            <div class="py-3">
                <div class="inline-block bg-white p-3 rounded-xl shadow-soft">
                    <div id="qr-code" class="flex justify-center"></div>
                </div>
            </div>
        </div>

        <div class="relative text-center pb-8 px-8">
            <div class="text-xs opacity-50">Please present this QR code at the entrance</div>
        </div>
    </div>

    {{-- =====================================================
         ROYAL — ornate double border, crown, fleur ornaments
    ====================================================== --}}
    @elseif($tpl === 'royal')
    <div class="card-container relative max-w-md w-full rounded-2xl shadow-2xl overflow-hidden"
        style="background-color: {{ $bg }}; color: {{ $fg }}; border: 6px double {{ $accent }};">
        <div class="absolute inset-2.5 border rounded-lg pointer-events-none" style="border-color: {{ $accent }}55;"></div>

        <!-- Corner fleur ornaments -->
        <svg class="absolute top-2 left-2 w-9 h-9 ornament" viewBox="0 0 24 24" fill="currentColor" opacity=".7"><path d="M12 2c1.5 2 1.5 4 0 6-1.5-2-1.5-4 0-6zM4 9c2-1 4-1 6 0-2 1.5-4 1.5-6 0zm16 0c-2-1-4-1-6 0 2 1.5 4 1.5 6 0zM12 22c-2-1.5-2-4 0-6 2 2 2 4.5 0 6z"/></svg>
        <svg class="absolute top-2 right-2 w-9 h-9 ornament scale-x-[-1]" viewBox="0 0 24 24" fill="currentColor" opacity=".7"><path d="M12 2c1.5 2 1.5 4 0 6-1.5-2-1.5-4 0-6zM4 9c2-1 4-1 6 0-2 1.5-4 1.5-6 0zm16 0c-2-1-4-1-6 0 2 1.5 4 1.5 6 0zM12 22c-2-1.5-2-4 0-6 2 2 2 4.5 0 6z"/></svg>

        <div class="relative text-center pt-12 px-8">
            <i class="fas fa-crown text-2xl mb-3" style="color: {{ $accent }};"></i>
            <div class="text-[11px] uppercase tracking-[0.3em] mb-2 opacity-70">You Are Cordially Invited To</div>
            <h1 class="font-display text-2xl font-bold" style="color: {{ $accent }};">{{ $event->name }}</h1>
            <div class="flex items-center justify-center gap-2 mt-3">
                <span class="h-px w-14" style="background: {{ $accent }};"></span>
                <span class="w-1.5 h-1.5 rotate-45" style="background: {{ $accent }};"></span>
                <span class="h-px w-14" style="background: {{ $accent }};"></span>
            </div>
        </div>

        <div class="relative px-8 py-6 text-center space-y-4">
            <div class="py-2">
                <div class="text-sm opacity-70 mb-1">Dear</div>
                <div class="font-display text-xl font-semibold">{{ $guest->full_name }}</div>
                <div class="text-xs mt-1 opacity-60">{{ $guest->card_type === 'double' ? 'Couple Invitation' : 'Single Invitation' }}</div>
            </div>

            <div class="border-t border-b py-4 space-y-2" style="border-color: {{ $accent }}44;">
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Date:</span>
                    <span class="font-semibold">{{ $event->event_date->format('d M Y') }}</span>
                </div>
                @if($event->event_time)
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Time:</span>
                    <span class="font-semibold">{{ $event->event_time }}</span>
                </div>
                @endif
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Venue:</span>
                    <span class="font-semibold">{{ $event->location }}</span>
                </div>
                @if($event->hall)
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Hall:</span>
                    <span class="font-semibold">{{ $event->hall }}</span>
                </div>
                @endif
            </div>

            <div class="py-4">
                <div class="inline-block bg-white p-3 rounded-xl shadow-soft">
                    <div id="qr-code" class="flex justify-center"></div>
                </div>
            </div>
        </div>

        <div class="relative text-center pb-7 px-8">
            <div class="text-xs opacity-50">Please present this QR code at the entrance</div>
        </div>
    </div>

    {{-- =====================================================
         RUSTIC — leaves/branches, kraft texture, earthy
    ====================================================== --}}
    @else
    <div class="card-container relative max-w-md w-full rounded-2xl shadow-2xl overflow-hidden bg-noise"
        style="background-color: {{ $bg }}; color: {{ $fg }};">
        <div class="absolute inset-0 pointer-events-none" style="background: linear-gradient(180deg, {{ $accent }}12, transparent 30%);"></div>

        <svg class="absolute top-0 left-0 w-20 h-20 ornament opacity-70" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M5 5 Q30 15 25 45" fill="none"/>
            <path d="M10 15 Q20 20 18 30" fill="none"/>
            <path d="M15 30 Q25 33 22 42" fill="none"/>
        </svg>
        <svg class="absolute bottom-0 right-0 w-20 h-20 ornament opacity-70 rotate-180" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M5 5 Q30 15 25 45" fill="none"/>
            <path d="M10 15 Q20 20 18 30" fill="none"/>
            <path d="M15 30 Q25 33 22 42" fill="none"/>
        </svg>

        <div class="relative text-center pt-9 px-8" style="border-top: 4px solid {{ $accent }};">
            <i class="fas fa-leaf mb-2" style="color: {{ $accent }};"></i>
            <div class="text-xs uppercase tracking-widest mb-2 opacity-70">You Are Cordially Invited To</div>
            <h1 class="font-display text-2xl font-bold" style="color: {{ $accent }};">{{ $event->name }}</h1>
        </div>

        <div class="relative px-8 py-6 text-center space-y-4">
            <div class="py-2">
                <div class="text-sm opacity-70 mb-1">Dear</div>
                <div class="font-display text-xl font-semibold">{{ $guest->full_name }}</div>
                <div class="text-xs mt-1 opacity-60">{{ $guest->card_type === 'double' ? 'Couple Invitation' : 'Single Invitation' }}</div>
            </div>

            <div class="py-4 space-y-2" style="border-top: 1px dashed {{ $accent }}66; border-bottom: 1px dashed {{ $accent }}66;">
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Date:</span>
                    <span class="font-semibold">{{ $event->event_date->format('d M Y') }}</span>
                </div>
                @if($event->event_time)
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Time:</span>
                    <span class="font-semibold">{{ $event->event_time }}</span>
                </div>
                @endif
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Venue:</span>
                    <span class="font-semibold">{{ $event->location }}</span>
                </div>
                @if($event->hall)
                <div class="flex items-center justify-center gap-2">
                    <span class="text-sm opacity-70">Hall:</span>
                    <span class="font-semibold">{{ $event->hall }}</span>
                </div>
                @endif
            </div>

            <div class="py-4">
                <div class="inline-block bg-white p-3 rounded-xl shadow-soft">
                    <div id="qr-code" class="flex justify-center"></div>
                </div>
            </div>
        </div>

        <div class="relative text-center pb-6 px-8">
            <div class="text-xs opacity-50">Please present this QR code at the entrance</div>
        </div>
        <div class="h-2" style="background-color: {{ $accent }};"></div>
    </div>
    @endif

    <script>
        loadScriptWithFallback([
            'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',
            'https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js',
            'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js'
        ], function () {
            new QRCode(document.getElementById('qr-code'), {
                text: '{{ $guest->unique_id }}',
                width: 150,
                height: 150,
                colorDark: '#1a1a1a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        });
    </script>
</body>
</html>
