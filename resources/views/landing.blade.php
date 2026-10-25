<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NiaLike by Hamasika Technology — beautiful digital wedding and event invitations, WhatsApp delivery, guest management, QR check-in and live reports in one place.">
    <title>NiaLike by Hamasika Technology — Digital Invitations & Guest Management</title>
    @include('partials.head-assets')
    <style>
        .landing-hero {
            background:
                radial-gradient(ellipse 70% 60% at 80% 0%, rgba(212, 175, 55, .18), transparent 60%),
                radial-gradient(ellipse 60% 60% at 0% 30%, rgba(225, 29, 72, .14), transparent 60%),
                linear-gradient(180deg, #fffafb 0%, #ffffff 70%);
        }
        .mesh-grid {
            background-image:
                linear-gradient(rgba(225, 29, 72, .06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(225, 29, 72, .06) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: radial-gradient(ellipse 70% 70% at 50% 40%, black, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse 70% 70% at 50% 40%, black, transparent 75%);
        }
        .gradient-text {
            background: linear-gradient(120deg, #be123c 10%, #e11d48 45%, #b8932a 90%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .float-slow { animation: float-slow 6s ease-in-out infinite; }
        .float-slower { animation: float-slow 8s ease-in-out infinite reverse; }
        @keyframes float-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }
        .js-reveal .reveal { opacity: 0; transform: translateY(22px); transition: opacity .7s ease, transform .7s ease; }
        .js-reveal .reveal.is-visible { opacity: 1; transform: translateY(0); }
        .feature-card { transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
        .feature-card:hover { transform: translateY(-4px); box-shadow: 0 20px 45px -28px rgba(190, 18, 60, .45); border-color: #fecdd5; }
        .qr-mock {
            background-image:
                repeating-linear-gradient(0deg, #18181b 0 4px, transparent 4px 8px),
                repeating-linear-gradient(90deg, #18181b 0 4px, transparent 4px 8px);
            background-size: 16px 16px;
            background-blend-mode: multiply;
        }
        .faq-item summary::-webkit-details-marker { display: none; }
        .faq-item[open] .faq-chevron { transform: rotate(180deg); }
        .faq-item[open] summary { color: #be123c; }
        @media (prefers-reduced-motion: reduce) {
            .float-slow, .float-slower, .reveal { animation: none !important; transition: none !important; opacity: 1 !important; transform: none !important; }
        }
    </style>
    <script>document.documentElement.classList.add('js-reveal');</script>
</head>
<body class="bg-white text-surface-800 antialiased">

    <!-- Navigation -->
    <header class="sticky top-0 z-50 border-b border-surface-100 bg-white/85 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3.5 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center">
                <img src="{{ asset('images/nialike-logo.png') }}" alt="NiaLike by Hamasika Technology" class="h-11 w-auto sm:h-12">
            </a>

            <nav class="hidden items-center gap-8 lg:flex">
                <a href="#about" class="text-sm font-medium text-surface-600 transition hover:text-primary-600">About</a>
                <a href="#features" class="text-sm font-medium text-surface-600 transition hover:text-primary-600">Features</a>
                <a href="#inside" class="text-sm font-medium text-surface-600 transition hover:text-primary-600">What you can do</a>
                <a href="#how" class="text-sm font-medium text-surface-600 transition hover:text-primary-600">How it works</a>
            </nav>

            <div class="hidden items-center gap-3 lg:flex">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Open Dashboard <i class="fas fa-arrow-right text-xs"></i></a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost">Sign in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Get started <i class="fas fa-arrow-right text-xs"></i></a>
                @endauth
            </div>

            <button type="button" id="menuButton" class="icon-action text-surface-600 lg:hidden" aria-label="Open menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <div id="mobileMenu" class="hidden border-t border-surface-100 bg-white px-4 pb-5 pt-2 lg:hidden">
            <nav class="flex flex-col gap-1">
                <a href="#about" class="rounded-lg px-3 py-2.5 text-sm font-medium text-surface-700 hover:bg-surface-50">About</a>
                <a href="#features" class="rounded-lg px-3 py-2.5 text-sm font-medium text-surface-700 hover:bg-surface-50">Features</a>
                <a href="#inside" class="rounded-lg px-3 py-2.5 text-sm font-medium text-surface-700 hover:bg-surface-50">What you can do</a>
                <a href="#how" class="rounded-lg px-3 py-2.5 text-sm font-medium text-surface-700 hover:bg-surface-50">How it works</a>
            </nav>
            <div class="mt-3 flex flex-col gap-2 border-t border-surface-100 pt-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-block">Open Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline btn-block">Sign in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-block">Create free account</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        <!-- Hero -->
        <section class="landing-hero relative overflow-hidden">
            <div class="absolute inset-0 mesh-grid"></div>
            <div class="absolute -left-24 top-24 h-72 w-72 rounded-full bg-primary-200/40 decorative-blob"></div>
            <div class="absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-gold-200/40 decorative-blob"></div>

            <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 pb-20 pt-16 sm:px-6 lg:grid-cols-[1.05fr_.95fr] lg:px-8 lg:pb-28 lg:pt-24">
                <div class="reveal">
                    <span class="inline-flex items-center gap-2 rounded-full border border-primary-100 bg-white/80 px-3.5 py-1.5 text-xs font-semibold text-primary-700 shadow-soft backdrop-blur">
                        <span class="flex h-2 w-2 rounded-full bg-primary-500"></span>
                        Hamasika Technology presents NiaLike
                    </span>

                    <h1 class="mt-6 font-display text-4xl font-bold leading-[1.08] tracking-tight text-surface-900 sm:text-5xl lg:text-6xl">
                        Invitations that feel personal.<br>
                        <span class="gradient-text">Management that feels effortless.</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-relaxed text-surface-500 sm:text-lg">
                        NiaLike is the all-in-one event invitation and guest management platform.
                        Create beautiful digital cards, deliver them through WhatsApp, track RSVPs,
                        scan guests in with QR codes and watch your event come together in real time.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary px-6 py-3.5 text-sm">
                                Go to Dashboard <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="btn btn-primary px-6 py-3.5 text-sm">
                                Start free <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                            <a href="{{ route('login') }}" class="btn btn-outline px-6 py-3.5 text-sm">
                                <i class="fas fa-right-to-bracket text-surface-400"></i> Sign in
                            </a>
                        @endauth
                    </div>

                    <div class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm text-surface-500">
                        <span class="inline-flex items-center gap-2"><i class="fas fa-circle-check text-green-500"></i> No printing costs</span>
                        <span class="inline-flex items-center gap-2"><i class="fas fa-circle-check text-green-500"></i> WhatsApp-first delivery</span>
                        <span class="inline-flex items-center gap-2"><i class="fas fa-circle-check text-green-500"></i> Works on any phone</span>
                    </div>
                </div>

                <!-- Product mock -->
                <div class="reveal relative mx-auto w-full max-w-md lg:max-w-none" style="transition-delay: .15s;">
                    <div class="absolute -left-8 -top-6 z-10 float-slow rounded-2xl border border-surface-100 bg-white p-4 shadow-soft-lg">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 text-green-600">
                                <i class="fab fa-whatsapp text-lg"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-surface-900">Invitation delivered</p>
                                <p class="text-[11px] text-surface-400">Just now · WhatsApp</p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -right-4 top-1/3 z-10 float-slower rounded-2xl border border-surface-100 bg-white p-4 shadow-soft-lg">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Checked in</p>
                        <p class="mt-1 text-2xl font-bold text-surface-900">128<span class="text-sm font-semibold text-green-600"> / 160</span></p>
                        <div class="mt-2 h-1.5 w-32 overflow-hidden rounded-full bg-surface-100">
                            <div class="h-full w-4/5 rounded-full bg-green-500"></div>
                        </div>
                    </div>

                    <div class="relative rounded-[2rem] border border-surface-200/80 bg-white/70 p-5 shadow-[0_40px_80px_-40px_rgba(136,19,55,.35)] backdrop-blur">
                        <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#881337] via-[#be123c] to-[#d4af37] p-6 text-white">
                            <div class="flex items-start justify-between">
                                <span class="rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em]">Wedding invitation</span>
                                <i class="fas fa-heart text-white/70"></i>
                            </div>
                            <p class="mt-8 text-center font-display text-3xl font-semibold italic">John &amp; Jane</p>
                            <p class="mt-2 text-center text-[11px] uppercase tracking-[0.28em] text-white/75">Are getting married</p>
                            <div class="mx-auto my-4 h-px w-16 bg-white/30"></div>
                            <p class="text-center text-xs text-white/85">Saturday, 12 December · 4:00 PM</p>
                            <p class="mt-1 text-center text-[11px] text-white/65">Diamond Jubilee Hall, Dar es Salaam</p>
                            <div class="mt-6 flex items-end justify-between">
                                <div>
                                    <p class="text-[9px] uppercase tracking-widest text-white/55">Guest</p>
                                    <p class="text-sm font-semibold">Amina Hassan</p>
                                </div>
                                <div class="rounded-lg bg-white p-1.5">
                                    <div class="qr-mock h-12 w-12 rounded-sm"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-3">
                            <div class="rounded-xl border border-surface-100 bg-white p-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-surface-400">Guests</p>
                                <p class="mt-1 text-lg font-bold text-surface-900">124</p>
                            </div>
                            <div class="rounded-xl border border-surface-100 bg-white p-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-surface-400">RSVP</p>
                                <p class="mt-1 text-lg font-bold text-green-600">86</p>
                            </div>
                            <div class="rounded-xl border border-surface-100 bg-white p-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-surface-400">Pledged</p>
                                <p class="mt-1 text-lg font-bold text-gold-600">6.4M</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Capability strip -->
        <section class="border-y border-surface-100 bg-surface-50/70">
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-px overflow-hidden px-4 py-8 sm:px-6 lg:grid-cols-4 lg:px-8">
                <div class="flex items-center gap-3 px-2 py-3 lg:px-6">
                    <i class="fab fa-whatsapp text-xl text-green-600"></i>
                    <div><p class="text-sm font-semibold text-surface-900">WhatsApp delivery</p><p class="text-xs text-surface-400">Reach every guest instantly</p></div>
                </div>
                <div class="flex items-center gap-3 px-2 py-3 lg:px-6">
                    <i class="fas fa-qrcode text-xl text-primary-600"></i>
                    <div><p class="text-sm font-semibold text-surface-900">QR check-in</p><p class="text-xs text-surface-400">Fast, contactless entry</p></div>
                </div>
                <div class="flex items-center gap-3 px-2 py-3 lg:px-6">
                    <i class="fas fa-chart-pie text-xl text-blue-600"></i>
                    <div><p class="text-sm font-semibold text-surface-900">Live reports</p><p class="text-xs text-surface-400">Know what is happening</p></div>
                </div>
                <div class="flex items-center gap-3 px-2 py-3 lg:px-6">
                    <i class="fas fa-coins text-xl text-gold-600"></i>
                    <div><p class="text-sm font-semibold text-surface-900">Payment tracking</p><p class="text-xs text-surface-400">Pledges and balances</p></div>
                </div>
            </div>
        </section>

        <!-- About -->
        <section id="about" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="grid items-start gap-12 lg:grid-cols-2 lg:gap-20">
                <div class="reveal">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-primary-600">Hamasika Technology</span>
                    <h2 class="mt-3 font-display text-3xl font-bold leading-tight text-surface-900 sm:text-4xl">
                        Technology built around the way people celebrate.
                    </h2>
                    <p class="mt-5 text-base leading-relaxed text-surface-500">
                        Hamasika Technology is an event-technology company that builds practical digital
                        tools for organisers, families and businesses. We believe the technology behind a
                        celebration should be invisible — it should protect the joy, not add work.
                    </p>
                    <p class="mt-4 text-base leading-relaxed text-surface-500">
                        <strong class="font-semibold text-surface-800">NiaLike</strong> is our flagship platform:
                        a complete workspace for invitations, guests, contributions, check-in and reporting.
                        It replaces paper cards, scattered spreadsheets and late-night phone calls with one
                        clear, beautiful system.
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl border border-surface-100 bg-surface-50 p-4">
                            <i class="fas fa-heart text-primary-500"></i>
                            <p class="mt-2 text-sm font-semibold text-surface-900">Human first</p>
                            <p class="mt-1 text-xs leading-relaxed text-surface-400">Designed for real celebrations, not spreadsheets.</p>
                        </div>
                        <div class="rounded-2xl border border-surface-100 bg-surface-50 p-4">
                            <i class="fas fa-bolt text-gold-500"></i>
                            <p class="mt-2 text-sm font-semibold text-surface-900">Fast to start</p>
                            <p class="mt-1 text-xs leading-relaxed text-surface-400">Create an event and send your first card the same day.</p>
                        </div>
                        <div class="rounded-2xl border border-surface-100 bg-surface-50 p-4">
                            <i class="fas fa-shield-halved text-blue-500"></i>
                            <p class="mt-2 text-sm font-semibold text-surface-900">Reliable</p>
                            <p class="mt-1 text-xs leading-relaxed text-surface-400">Secure accounts, clean records and clear audit trails.</p>
                        </div>
                    </div>
                </div>

                <div class="reveal relative">
                    <div class="absolute -right-4 -top-4 h-28 w-28 rounded-3xl bg-gold-100/70"></div>
                    <div class="relative rounded-3xl border border-surface-100 bg-white p-7 shadow-soft-lg sm:p-9">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
                                <i class="fas fa-lightbulb text-lg"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-surface-400">What is NiaLike?</p>
                                <p class="font-display text-xl font-bold text-surface-900">One place for the whole event.</p>
                            </div>
                        </div>

                        <div class="mt-7 space-y-4">
                            <div class="flex gap-3">
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-50 text-[10px] font-bold text-green-600">01</span>
                                <div><p class="text-sm font-semibold text-surface-800">Beautiful digital invitation cards</p><p class="mt-0.5 text-sm leading-relaxed text-surface-500">Choose a template, add your colours and share a personal card with every guest.</p></div>
                            </div>
                            <div class="flex gap-3">
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-50 text-[10px] font-bold text-green-600">02</span>
                                <div><p class="text-sm font-semibold text-surface-800">Organised guest and contribution records</p><p class="mt-0.5 text-sm leading-relaxed text-surface-500">Import your list, track pledges and payments, and know exactly who is coming.</p></div>
                            </div>
                            <div class="flex gap-3">
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-50 text-[10px] font-bold text-green-600">03</span>
                                <div><p class="text-sm font-semibold text-surface-800">A smooth day-of experience</p><p class="mt-0.5 text-sm leading-relaxed text-surface-500">Scan QR codes at the entrance and watch check-ins update in real time.</p></div>
                            </div>
                        </div>

                        <div class="mt-7 rounded-xl bg-gradient-to-r from-primary-50 to-gold-50 px-4 py-3 text-sm text-surface-600">
                            <i class="fas fa-quote-left mr-1 text-primary-300"></i>
                            From the first invitation to the final report, NiaLike keeps the whole celebration in view.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="border-y border-surface-100 bg-surface-50/60 py-20 lg:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center reveal">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-primary-600">Everything in one place</span>
                    <h2 class="mt-3 font-display text-3xl font-bold text-surface-900 sm:text-4xl">Powerful tools, without the complexity.</h2>
                    <p class="mt-4 text-base leading-relaxed text-surface-500">Every feature is built to save time, reduce confusion and make your guests feel valued.</p>
                </div>

                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach([
                        ['fa-calendar-days', 'bg-primary-50 text-primary-600', 'Event management', 'Create unlimited events, set dates, venues and card themes, and keep every detail organised in one dashboard.'],
                        ['fa-users', 'bg-blue-50 text-blue-600', 'Guest management', 'Add guests one by one or import from Excel/CSV. Track categories, tables, phone numbers and notes.'],
                        ['fa-paper-plane', 'bg-green-50 text-green-600', 'WhatsApp invitations', 'Send personalised invitation messages with a unique link and card to each guest — no printing required.'],
                        ['fa-qrcode', 'bg-gold-50 text-gold-600', 'QR check-in', 'Every guest gets a unique QR code. Scan it at the door for instant, accurate attendance tracking.'],
                        ['fa-envelope-circle-check', 'bg-purple-50 text-purple-600', 'RSVP tracking', 'Guests confirm attendance online. See who is coming, who cannot make it and who still needs a reminder.'],
                        ['fa-chart-pie', 'bg-rose-50 text-rose-600', 'Live reports', 'View contributions, payment balances, attendance estimates and category breakdowns whenever you need them.'],
                    ] as [$icon, $tone, $title, $copy])
                    <div class="feature-card reveal rounded-2xl border border-surface-100 bg-white p-6 shadow-soft">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tone }}">
                            <i class="fas {{ $icon }}"></i>
                        </div>
                        <h3 class="mt-4 font-display text-lg font-bold text-surface-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-surface-500">{{ $copy }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- The NiaLike difference -->
        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-2xl text-center reveal">
                <span class="text-xs font-bold uppercase tracking-[0.22em] text-primary-600">The NiaLike difference</span>
                <h2 class="mt-3 font-display text-3xl font-bold text-surface-900 sm:text-4xl">Leave the paperwork behind.</h2>
                <p class="mt-4 text-base leading-relaxed text-surface-500">The same celebration, without the missed calls, lost lists and long queues at the entrance.</p>
            </div>

            <div class="mt-12 grid gap-5 lg:grid-cols-2">
                <div class="reveal rounded-3xl border border-red-100 bg-red-50/40 p-7 sm:p-9">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-red-500 shadow-soft">
                            <i class="fas fa-box-archive"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-red-400">The old way</p>
                            <h3 class="font-display text-xl font-bold text-surface-900">Scattered and stressful</h3>
                        </div>
                    </div>
                    <div class="mt-6 space-y-3.5">
                        @foreach([
                            'Printed invitation cards that cost money and get lost.',
                            'Guest names spread across notebooks, chats and spreadsheets.',
                            'Manual attendance lists and long queues at the entrance.',
                            'Contribution balances that nobody can confirm.',
                            'Reports assembled by hand after the event.',
                        ] as $item)
                        <div class="flex items-start gap-3 text-sm text-surface-500">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white text-red-400"><i class="fas fa-xmark text-[9px]"></i></span>
                            <span>{{ $item }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="reveal relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#6d0f2a] via-[#be123c] to-[#b8932a] p-7 text-white shadow-soft-lg sm:p-9">
                    <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="relative">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 text-white backdrop-blur">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-white/60">With NiaLike</p>
                                <h3 class="font-display text-xl font-bold">Connected and calm</h3>
                            </div>
                        </div>
                        <div class="mt-6 space-y-3.5">
                            @foreach([
                                'One beautiful digital card delivered directly on WhatsApp.',
                                'Every guest, phone number and category in one clean list.',
                                'Unique QR codes scanned at the door in seconds.',
                                'Pledges, payments and outstanding balances in real time.',
                                'Reports ready the moment you need them — no manual work.',
                            ] as $item)
                            <div class="flex items-start gap-3 text-sm text-white/80">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/15 text-gold-200"><i class="fas fa-check text-[9px]"></i></span>
                                <span>{{ $item }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- What you can do inside -->
        <section id="inside" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-20">
                <div class="reveal order-2 lg:order-1">
                    <div class="rounded-3xl border border-surface-100 bg-surface-900 p-2 shadow-2xl">
                        <div class="rounded-2xl bg-surface-50 p-4 sm:p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                    <span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>
                                </div>
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-surface-400">NiaLike workspace</span>
                            </div>
                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-xl border border-surface-100 bg-white p-4">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-surface-400">Guests</p>
                                    <p class="mt-1 text-2xl font-bold text-surface-900">248</p>
                                    <div class="mt-2 flex items-center gap-1 text-[11px] font-medium text-green-600"><i class="fas fa-arrow-trend-up"></i> 12 added this week</div>
                                </div>
                                <div class="rounded-xl border border-surface-100 bg-white p-4">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-surface-400">Invitations sent</p>
                                    <p class="mt-1 text-2xl font-bold text-surface-900">196</p>
                                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-100"><div class="h-full w-[79%] rounded-full bg-primary-500"></div></div>
                                </div>
                                <div class="rounded-xl border border-surface-100 bg-white p-4">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-surface-400">Attending</p>
                                    <p class="mt-1 text-2xl font-bold text-green-600">142</p>
                                    <div class="mt-2 flex -space-x-2">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-[9px] font-bold text-primary-700 ring-2 ring-white">AH</span>
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-[9px] font-bold text-blue-700 ring-2 ring-white">JM</span>
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gold-100 text-[9px] font-bold text-gold-700 ring-2 ring-white">SK</span>
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-surface-100 text-[9px] font-bold text-surface-600 ring-2 ring-white">+9</span>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-surface-100 bg-white p-4">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-surface-400">Collected</p>
                                    <p class="mt-1 text-2xl font-bold text-gold-600">8.2M <span class="text-xs font-medium text-surface-400">TZS</span></p>
                                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-100"><div class="h-full w-[68%] rounded-full bg-gold-500"></div></div>
                                </div>
                            </div>
                            <div class="mt-3 rounded-xl border border-surface-100 bg-white p-4">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-surface-800">Invitation delivery</p>
                                    <span class="badge bg-green-100 text-green-700">On track</span>
                                </div>
                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-surface-100"><div class="h-full w-[79%] rounded-full bg-gradient-to-r from-primary-500 to-gold-500"></div></div>
                                <div class="mt-2 flex justify-between text-[11px] text-surface-400"><span>196 delivered</span><span>52 pending</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reveal order-1 lg:order-2">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-primary-600">Inside the application</span>
                    <h2 class="mt-3 font-display text-3xl font-bold text-surface-900 sm:text-4xl">What you can actually do.</h2>
                    <p class="mt-4 text-base leading-relaxed text-surface-500">NiaLike is more than a card generator. It is your end-to-end event operations tool.</p>

                    <div class="mt-7 space-y-4">
                        @foreach([
                            'Create events and customise invitation card templates, colours and minimum contribution amounts.',
                            'Import guest lists from Excel or CSV and keep phone numbers, categories, tables and notes tidy.',
                            'Send personalised WhatsApp invitations with a unique public invitation link for each guest.',
                            'Let guests RSVP online, download their card and respond from any phone.',
                            'Track pledges and payments, view outstanding balances and record contributions as they arrive.',
                            'Scan QR codes at the entrance and monitor check-ins live from your phone.',
                            'Generate reports for attendance, contributions, categories and card types.',
                            'Give team members access to the events they need, with admin controls for user permissions.',
                        ] as $item)
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600">
                                <i class="fas fa-check text-[9px]"></i>
                            </span>
                            <p class="text-sm leading-relaxed text-surface-600">{{ $item }}</p>
                        </div>
                        @endforeach
                    </div>

                    <a href="{{ route('register') }}" class="btn btn-primary mt-8 px-6 py-3">
                        Create your free account <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Who it is for -->
        <section class="border-y border-surface-100 bg-gradient-to-r from-primary-50/60 via-white to-gold-50/60 py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-6 text-center sm:grid-cols-2 lg:grid-cols-4">
                    @foreach([
                        ['fa-heart', 'Weddings', 'Elegant cards, guest lists and contribution tracking.'],
                        ['fa-graduation-cap', 'Graduations', 'Celebrate milestones with organised invitations.'],
                        ['fa-glass-cheers', 'Send-offs', 'Keep every detail and guest in one clear place.'],
                        ['fa-briefcase', 'Corporate events', 'Professional invitations and reliable attendance data.'],
                    ] as [$icon, $title, $copy])
                    <div class="reveal">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-primary-600 shadow-soft">
                            <i class="fas {{ $icon }}"></i>
                        </div>
                        <h3 class="mt-3 font-display text-lg font-bold text-surface-900">{{ $title }}</h3>
                        <p class="mx-auto mt-1 max-w-xs text-sm leading-relaxed text-surface-500">{{ $copy }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Check-in spotlight -->
        <section class="relative overflow-hidden bg-surface-900 py-20 text-white lg:py-28">
            <div class="absolute -left-20 top-0 h-72 w-72 rounded-full bg-primary-600/30 blur-3xl"></div>
            <div class="absolute -right-20 bottom-0 h-72 w-72 rounded-full bg-gold-500/20 blur-3xl"></div>

            <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div class="reveal">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-gold-300">The day of the event</span>
                    <h2 class="mt-3 font-display text-3xl font-bold leading-tight sm:text-4xl">A welcome that moves as fast as you do.</h2>
                    <p class="mt-5 max-w-lg text-base leading-relaxed text-white/65">
                        No printed guest lists. No guessing. Your team opens the scanner on any phone,
                        scans the guest's QR code and sees an instant verified result at the door.
                    </p>

                    <div class="mt-7 space-y-3.5">
                        @foreach([
                            'Verify each guest in under a second.',
                            'See card type, table and guest status instantly.',
                            'Prevent duplicate check-ins automatically.',
                            'Watch the attendance count update live.',
                        ] as $item)
                        <div class="flex items-start gap-3 text-sm text-white/75">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/10 text-gold-300"><i class="fas fa-check text-[9px]"></i></span>
                            <span>{{ $item }}</span>
                        </div>
                        @endforeach
                    </div>

                    <a href="{{ route('register') }}" class="btn mt-8 bg-white px-6 py-3 text-surface-900 hover:bg-surface-100">
                        Try the check-in workflow <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>

                <div class="reveal mx-auto w-full max-w-sm" style="transition-delay: .12s;">
                    <div class="rounded-[2rem] border border-white/15 bg-white/10 p-3 shadow-2xl backdrop-blur">
                        <div class="overflow-hidden rounded-[1.5rem] bg-white">
                            <div class="flex items-center justify-between bg-surface-900 px-4 py-3 text-white">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-qrcode text-gold-300"></i>
                                    <span class="text-xs font-semibold">NiaLike Scanner</span>
                                </div>
                                <span class="flex items-center gap-1.5 text-[10px] text-green-300">
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-green-400"></span> Camera live
                                </span>
                            </div>

                            <div class="relative bg-surface-900 px-5 py-6">
                                <div class="relative mx-auto aspect-square w-52">
                                    <div class="absolute inset-0 rounded-2xl border border-white/10"></div>
                                    <span class="absolute left-0 top-0 h-8 w-8 rounded-tl-2xl border-l-4 border-t-4 border-gold-400"></span>
                                    <span class="absolute right-0 top-0 h-8 w-8 rounded-tr-2xl border-r-4 border-t-4 border-gold-400"></span>
                                    <span class="absolute bottom-0 left-0 h-8 w-8 rounded-bl-2xl border-b-4 border-l-4 border-gold-400"></span>
                                    <span class="absolute bottom-0 right-0 h-8 w-8 rounded-br-2xl border-b-4 border-r-4 border-gold-400"></span>
                                    <div class="absolute inset-8 rounded-xl bg-white p-3">
                                        <div class="qr-mock h-full w-full rounded-md opacity-90"></div>
                                    </div>
                                    <div class="absolute inset-x-0 top-1/2 h-0.5 bg-gold-400/70 shadow-[0_0_12px_2px_rgba(234,203,95,.45)]"></div>
                                </div>
                            </div>

                            <div class="px-5 pb-5">
                                <div class="flex items-center gap-3 rounded-xl border border-green-100 bg-green-50 p-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-500 text-white">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-green-600">Verified guest</p>
                                        <p class="truncate text-sm font-bold text-surface-900">Amina Hassan</p>
                                        <p class="text-[10px] text-surface-500">Double card · Table 4</p>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <div class="rounded-lg bg-surface-50 px-3 py-2 text-center">
                                        <p class="text-base font-bold text-surface-900">128</p>
                                        <p class="text-[9px] uppercase tracking-wider text-surface-400">Checked in</p>
                                    </div>
                                    <div class="rounded-lg bg-surface-50 px-3 py-2 text-center">
                                        <p class="text-base font-bold text-surface-900">32</p>
                                        <p class="text-[9px] uppercase tracking-wider text-surface-400">Remaining</p>
                                    </div>
                                </div>
                                <p class="mt-3 text-center text-[9px] font-medium uppercase tracking-[0.16em] text-surface-400">Works on any phone with a camera</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section id="how" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="mx-auto max-w-2xl text-center reveal">
                <span class="text-xs font-bold uppercase tracking-[0.22em] text-primary-600">How it works</span>
                <h2 class="mt-3 font-display text-3xl font-bold text-surface-900 sm:text-4xl">From idea to celebration in three steps.</h2>
            </div>

            <div class="relative mt-14 grid gap-8 lg:grid-cols-3">
                <div class="absolute left-0 right-0 top-10 hidden h-px bg-gradient-to-r from-transparent via-primary-200 to-transparent lg:block"></div>
                @foreach([
                    ['1', 'Create your event', 'Add the event name, date, venue and invitation card style. Set your card minimums and you are ready.', 'fa-calendar-plus'],
                    ['2', 'Add and invite guests', 'Import your guest list and send personalised WhatsApp invitations with unique QR cards and RSVP links.', 'fa-paper-plane'],
                    ['3', 'Manage the big day', 'Track RSVPs and contributions, scan guests in at the entrance, and download a full report afterwards.', 'fa-qrcode'],
                ] as [$step, $title, $copy, $icon])
                <div class="reveal relative rounded-2xl border border-surface-100 bg-white p-7 text-center shadow-soft">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 ring-8 ring-white">
                        <i class="fas {{ $icon }} text-lg"></i>
                    </div>
                    <span class="mt-4 inline-block text-[10px] font-bold uppercase tracking-[0.2em] text-surface-400">Step {{ $step }}</span>
                    <h3 class="mt-1 font-display text-xl font-bold text-surface-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-surface-500">{{ $copy }}</p>
                </div>
                @endforeach
            </div>
        </section>

        <!-- Team access -->
        <section class="border-y border-surface-100 bg-surface-50/70 py-16 lg:py-20">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div class="reveal">
                    <span class="text-xs font-bold uppercase tracking-[0.22em] text-primary-600">Team access</span>
                    <h2 class="mt-3 font-display text-3xl font-bold text-surface-900 sm:text-4xl">Everyone has the access they need.</h2>
                    <p class="mt-4 max-w-lg text-base leading-relaxed text-surface-500">
                        Run one event with a committee or manage many events across a team. NiaLike keeps
                        responsibilities clear and your guest data protected.
                    </p>
                    <div class="mt-6 space-y-3">
                        @foreach([
                            'Administrators manage users, templates and event access.',
                            'Event planners only see the events assigned to them.',
                            'Check-in teams can scan guests without changing financial records.',
                            'Every important action is recorded in the activity log.',
                        ] as $item)
                        <div class="flex items-start gap-3 text-sm text-surface-600">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white text-primary-600 shadow-soft"><i class="fas fa-check text-[9px]"></i></span>
                            <span>{{ $item }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 reveal" style="transition-delay: .1s;">
                    <div class="rounded-2xl border border-surface-100 bg-white p-5 shadow-soft sm:col-span-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600"><i class="fas fa-user-shield"></i></div>
                                <div>
                                    <p class="text-sm font-bold text-surface-900">Administrator</p>
                                    <p class="text-[11px] text-surface-400">Full workspace control</p>
                                </div>
                            </div>
                            <span class="badge bg-primary-50 text-primary-700">All access</span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach(['Users', 'Events', 'Payments', 'Templates', 'Reports'] as $permission)
                            <span class="rounded-full bg-surface-100 px-2.5 py-1 text-[10px] font-medium text-surface-500">{{ $permission }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-2xl border border-surface-100 bg-white p-5 shadow-soft">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><i class="fas fa-calendar-check"></i></div>
                        <p class="mt-3 text-sm font-bold text-surface-900">Event planner</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-surface-400">Manages only assigned events, guests and invitations.</p>
                    </div>

                    <div class="rounded-2xl border border-surface-100 bg-white p-5 shadow-soft">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 text-green-600"><i class="fas fa-qrcode"></i></div>
                        <p class="mt-3 text-sm font-bold text-surface-900">Check-in team</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-surface-400">Scans guest QR codes and verifies attendance at the door.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="mx-auto max-w-4xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="text-center reveal">
                <span class="text-xs font-bold uppercase tracking-[0.22em] text-primary-600">Questions, answered</span>
                <h2 class="mt-3 font-display text-3xl font-bold text-surface-900 sm:text-4xl">Everything you might be wondering.</h2>
                <p class="mt-4 text-base leading-relaxed text-surface-500">A few quick answers before you create your first event.</p>
            </div>

            <div class="mt-10 space-y-3 reveal">
                @foreach([
                    ['Is NiaLike only for weddings?', 'No. NiaLike works for weddings, send-offs, graduations, birthdays, corporate events and any celebration where you need to invite and manage guests.'],
                    ['Do my guests need to download an app?', 'No. Guests receive their invitation link through WhatsApp and open it in any browser on any phone. They can RSVP, view the event details and download their card without installing anything.'],
                    ['How are invitations delivered?', 'Invitations are sent as personalised WhatsApp messages containing the guest name, unique guest ID, event date and a private invitation link. Your team can send them individually or in bulk.'],
                    ['Can I import my existing guest list?', 'Yes. Download the template, add your guests in Excel or CSV, and import the file. NiaLike creates each guest record and prepares their invitation automatically.'],
                    ['What happens at the entrance?', 'Open the scanner on any phone with a camera. Scan the guest QR code and NiaLike instantly verifies the guest, shows their card type and records the check-in.'],
                    ['Can I track contributions and balances?', 'Yes. Record pledges and payments against each guest, see outstanding balances and open a complete contribution report for the event.'],
                    ['Can more than one person manage an event?', 'Administrators can assign events to different users. Each planner sees the events they are responsible for, while administrators keep full oversight.'],
                    ['Where is my guest data stored?', 'Guest records live in your NiaLike workspace and are only available to authorised users. Sensitive actions are recorded in the event activity log.'],
                ] as [$question, $answer])
                <details class="faq-item group rounded-2xl border border-surface-200 bg-white px-5 py-4 shadow-soft transition open:border-primary-100 open:shadow-soft-lg sm:px-6">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-surface-800 transition">
                        <span>{{ $question }}</span>
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-100 text-surface-400 transition group-open:bg-primary-50 group-open:text-primary-600">
                            <i class="fas fa-chevron-down faq-chevron text-[10px] transition-transform"></i>
                        </span>
                    </summary>
                    <p class="mt-3 max-w-3xl text-sm leading-relaxed text-surface-500">{{ $answer }}</p>
                </details>
                @endforeach
            </div>

            <div class="mt-10 rounded-2xl border border-primary-100 bg-primary-50/50 px-6 py-5 text-center reveal">
                <p class="text-sm text-surface-600">Still have a question? Talk to the Hamasika Technology team when you create your account.</p>
                <a href="{{ route('register') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:text-primary-700">
                    Create your free account <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </section>

        <!-- CTA -->
        <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8 lg:pb-28">
            <div class="reveal relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#6d0f2a] via-[#be123c] to-[#b8932a] px-6 py-14 text-center text-white shadow-2xl sm:px-12 lg:py-20">
                <div class="absolute -left-16 -top-16 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -bottom-20 -right-10 h-64 w-64 rounded-full bg-gold-300/20 blur-3xl"></div>
                <div class="relative">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3.5 py-1.5 text-xs font-semibold text-white/90">
                        <i class="fas fa-star text-gold-200"></i> Your next celebration starts here
                    </span>
                    <h2 class="mx-auto mt-5 max-w-2xl font-display text-3xl font-bold leading-tight sm:text-4xl">
                        Less paperwork. More presence.
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-white/75">
                        Join organisers who use NiaLike to invite beautifully, manage confidently and celebrate fully.
                    </p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn bg-white px-6 py-3.5 text-sm text-primary-700 hover:bg-primary-50">Open Dashboard <i class="fas fa-arrow-right text-xs"></i></a>
                        @else
                            <a href="{{ route('register') }}" class="btn bg-white px-6 py-3.5 text-sm text-primary-700 hover:bg-primary-50">Get started free <i class="fas fa-arrow-right text-xs"></i></a>
                            <a href="{{ route('login') }}" class="btn border border-white/25 bg-white/10 px-6 py-3.5 text-sm text-white hover:bg-white/15">Sign in</a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-surface-100 bg-surface-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 md:grid-cols-[1.4fr_1fr_1fr]">
                <div>
                    <img src="{{ asset('images/nialike-logo.png') }}" alt="NiaLike by Hamasika Technology" class="h-12 w-auto">
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-surface-500">
                        Beautiful digital invitations and complete guest management for weddings, send-offs,
                        graduations and corporate events.
                    </p>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.18em] text-surface-400">Platform</h3>
                    <ul class="mt-4 space-y-2.5 text-sm text-surface-600">
                        <li><a href="#about" class="hover:text-primary-600">About NiaLike</a></li>
                        <li><a href="#features" class="hover:text-primary-600">Features</a></li>
                        <li><a href="#inside" class="hover:text-primary-600">What you can do</a></li>
                        <li><a href="#how" class="hover:text-primary-600">How it works</a></li>
                        <li><a href="#faq" class="hover:text-primary-600">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.18em] text-surface-400">Get started</h3>
                    <ul class="mt-4 space-y-2.5 text-sm text-surface-600">
                        <li><a href="{{ route('login') }}" class="hover:text-primary-600">Sign in</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-primary-600">Create an account</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-surface-200 pt-6 text-xs text-surface-400 sm:flex-row">
                <p>&copy; {{ date('Y') }} Hamasika Technology. All rights reserved.</p>
                <p>NiaLike — invitations, guests and check-in in one place.</p>
            </div>
        </div>
    </footer>

    <script>
        const menuButton = document.getElementById('menuButton');
        const mobileMenu = document.getElementById('mobileMenu');

        menuButton?.addEventListener('click', () => {
            const open = mobileMenu.classList.toggle('hidden') === false;
            menuButton.setAttribute('aria-expanded', String(open));
            menuButton.innerHTML = open ? '<i class="fas fa-xmark"></i>' : '<i class="fas fa-bars"></i>';
        });

        mobileMenu?.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
                menuButton?.setAttribute('aria-expanded', 'false');
                if (menuButton) menuButton.innerHTML = '<i class="fas fa-bars"></i>';
            });
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));
    </script>
</body>
</html>
