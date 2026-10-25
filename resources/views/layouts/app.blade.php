<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NiaLike · @yield('title', 'Event Invitations & Guest Management')</title>
    @include('partials.head-assets')
    @stack('styles')
</head>
<body class="bg-surface-50 min-h-screen text-surface-800 antialiased">

    <div class="min-h-screen md:flex">

        <!-- Sidebar (desktop) -->
        <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 border-r border-surface-200 bg-white">
            <div class="flex items-center gap-2.5 px-6 h-16 border-b border-surface-100">
                <img src="{{ asset('images/nialike-icon.png') }}?v=2" alt="NiaLike" class="h-10 w-10 shrink-0 object-contain">
                <div class="leading-tight">
                    <span class="font-display font-bold text-lg text-surface-900 tracking-tight">NiaLike</span>
                    <span class="block text-[9px] font-semibold uppercase tracking-[0.16em] text-surface-400">by Hamasika Technology</span>
                </div>
            </div>

            @auth
            <nav class="flex-1 px-3 py-5 space-y-1">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                    {{ request()->routeIs('dashboard') ? 'bg-primary-50 text-primary-700' : 'text-surface-600 hover:bg-surface-100 hover:text-surface-900' }}">
                    <i class="fas fa-house w-4 text-center"></i> Dashboard
                </a>
                <a href="{{ route('events.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                    {{ request()->routeIs('events.*') ? 'bg-primary-50 text-primary-700' : 'text-surface-600 hover:bg-surface-100 hover:text-surface-900' }}">
                    <i class="fas fa-calendar-days w-4 text-center"></i> Events
                </a>
                @if(Auth::user()->isAdmin())
                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                    {{ request()->routeIs('admin.users.*') ? 'bg-primary-50 text-primary-700' : 'text-surface-600 hover:bg-surface-100 hover:text-surface-900' }}">
                    <i class="fas fa-users-gear w-4 text-center"></i> Users
                </a>
                <a href="{{ route('admin.whatsapp-templates.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                    {{ request()->routeIs('admin.whatsapp-templates.*') ? 'bg-primary-50 text-primary-700' : 'text-surface-600 hover:bg-surface-100 hover:text-surface-900' }}">
                    <i class="fas fa-file-lines w-4 text-center"></i> Templates
                </a>
                <a href="{{ route('webhook-logs.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                    {{ request()->routeIs('webhook-logs.*') ? 'bg-primary-50 text-primary-700' : 'text-surface-600 hover:bg-surface-100 hover:text-surface-900' }}">
                    <i class="fab fa-whatsapp w-4 text-center"></i> Webhook Logs
                </a>
                @endif

                <p class="px-3 pt-5 pb-1 text-[10px] font-semibold uppercase tracking-wider text-surface-400">Quick create</p>
                <a href="{{ route('events.create') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-surface-600 hover:bg-surface-100 hover:text-surface-900 transition">
                    <i class="fas fa-plus w-4 text-center"></i> New Event
                </a>
            </nav>

            <div class="px-3 pb-4 border-t border-surface-100 pt-4">
                <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-surface-50">
                    <div class="w-8 h-8 rounded-full bg-gold-100 text-gold-700 flex items-center justify-center text-xs font-bold shrink-0">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-surface-800 truncate">{{ Auth::user()->name }}</p>
                        <p class="text-[11px] text-surface-400 truncate">{{ Auth::user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" data-loading-text="Logging out...">
                        @csrf
                        <button type="submit" class="text-surface-400 hover:text-red-600 transition" title="Log out">
                            <i class="fas fa-arrow-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            </div>
            @endauth
        </aside>

        <!-- Mobile top bar -->
        <header class="md:hidden sticky top-0 z-30 bg-white/95 border-b border-surface-200 backdrop-blur">
            <div class="flex items-center justify-between h-16 px-4 gap-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 min-w-0">
                    <img src="{{ asset('images/nialike-icon.png') }}?v=2" alt="NiaLike" class="h-8 w-8 shrink-0 object-contain">
                    <div class="leading-tight min-w-0">
                        <span class="font-display font-bold text-surface-900">NiaLike</span>
                        <span class="block text-[8px] font-semibold uppercase tracking-[0.14em] text-surface-400 truncate">by Hamasika</span>
                    </div>
                </a>
                @auth
                <div class="flex items-center gap-2 shrink-0">
                    <div class="w-8 h-8 rounded-full bg-gold-100 text-gold-700 flex items-center justify-center text-xs font-bold" title="{{ Auth::user()->name }}">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <form method="POST" action="{{ route('logout') }}" data-loading-text="Logging out...">
                        @csrf
                        <button type="submit" class="icon-action text-surface-400 hover:text-red-600" aria-label="Log out" title="Log out">
                            <i class="fas fa-arrow-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
                @endauth
            </div>
        </header>

        <!-- Main -->
        <div class="flex-1 md:pl-64 min-w-0">
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-28 md:py-8">

                @foreach ([
                    'success' => ['fa-circle-check', 'border-green-200 bg-green-50 text-green-700', 'text-green-500 hover:text-green-700'],
                    'error' => ['fa-circle-exclamation', 'border-red-200 bg-red-50 text-red-700', 'text-red-500 hover:text-red-700'],
                    'warning' => ['fa-triangle-exclamation', 'border-amber-200 bg-amber-50 text-amber-800', 'text-amber-500 hover:text-amber-700'],
                    'info' => ['fa-circle-info', 'border-blue-200 bg-blue-50 text-blue-700', 'text-blue-500 hover:text-blue-700'],
                ] as $level => [$icon, $tone, $closeTone])
                    @if(session($level))
                    <div class="flash-alert mb-6 flex items-start justify-between gap-3 rounded-xl border px-4 py-3 text-sm transition-opacity duration-500 {{ $tone }}" role="alert">
                        <div class="flex items-start gap-2">
                            <i class="fas {{ $icon }} mt-0.5"></i><span>{{ session($level) }}</span>
                        </div>
                        <button type="button" data-dismiss-alert aria-label="Dismiss notification" class="shrink-0 {{ $closeTone }}">
                            <i class="fas fa-xmark"></i>
                        </button>
                    </div>
                    @endif
                @endforeach

                @if($errors->any())
                <div class="mb-6 flex items-start justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-circle-exclamation mt-0.5"></i>
                        <div>
                            <p class="font-semibold">Please fix the following {{ Str::plural('error', $errors->count()) }}:</p>
                            <ul class="mt-1 list-disc list-inside space-y-0.5">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button type="button" data-dismiss-alert aria-label="Dismiss notification" class="shrink-0 text-red-500 hover:text-red-700">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @auth
    <!-- Mobile bottom navigation -->
    <nav class="md:hidden fixed inset-x-0 bottom-0 z-30 border-t border-surface-200 bg-white/95 backdrop-blur shadow-[0_-10px_30px_-24px_rgba(24,24,27,.6)]"
        style="padding-bottom: env(safe-area-inset-bottom);" aria-label="Mobile navigation">
        <div class="grid auto-cols-fr grid-flow-col px-2 py-1">
            <a href="{{ route('dashboard') }}"
                class="flex flex-col items-center gap-1 rounded-xl py-2 text-[10px] font-semibold transition {{ request()->routeIs('dashboard') ? 'text-primary-600' : 'text-surface-500' }}">
                <i class="fas fa-house text-base"></i>
                <span>Home</span>
            </a>
            <a href="{{ route('events.index') }}"
                class="flex flex-col items-center gap-1 rounded-xl py-2 text-[10px] font-semibold transition {{ request()->routeIs('events.*') ? 'text-primary-600' : 'text-surface-500' }}">
                <i class="fas fa-calendar-days text-base"></i>
                <span>Events</span>
            </a>
            @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.users.index') }}"
                class="flex flex-col items-center gap-1 rounded-xl py-2 text-[10px] font-semibold transition {{ request()->routeIs('admin.users.*') ? 'text-primary-600' : 'text-surface-500' }}">
                <i class="fas fa-users-gear text-base"></i>
                <span>Users</span>
            </a>
            <a href="{{ route('admin.whatsapp-templates.index') }}"
                class="flex flex-col items-center gap-1 rounded-xl py-2 text-[10px] font-semibold transition {{ request()->routeIs('admin.whatsapp-templates.*') ? 'text-primary-600' : 'text-surface-500' }}">
                <i class="fas fa-file-lines text-base"></i>
                <span>Templates</span>
            </a>
            <a href="{{ route('webhook-logs.index') }}"
                class="flex flex-col items-center gap-1 rounded-xl py-2 text-[10px] font-semibold transition {{ request()->routeIs('webhook-logs.*') ? 'text-primary-600' : 'text-surface-500' }}">
                <i class="fab fa-whatsapp text-base"></i>
                <span>Logs</span>
            </a>
            @else
            <a href="{{ route('events.create') }}"
                class="flex flex-col items-center gap-1 rounded-xl py-2 text-[10px] font-semibold transition {{ request()->routeIs('events.create') ? 'text-primary-600' : 'text-surface-500' }}">
                <i class="fas fa-circle-plus text-base"></i>
                <span>New</span>
            </a>
            @endif
        </div>
    </nav>
    @endauth

    <!-- Global action loader -->
    <div id="actionLoader" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-surface-900/40 p-4 backdrop-blur-[2px]" aria-hidden="true" role="status" aria-live="polite">
        <div class="w-full max-w-xs rounded-2xl bg-white px-6 py-7 text-center shadow-2xl">
            <div class="action-loader-ring mx-auto mb-4"></div>
            <p id="actionLoaderText" class="text-sm font-semibold text-surface-900">Processing...</p>
            <p id="actionLoaderHint" class="mt-1 text-xs text-surface-400">Please wait, don't close this page.</p>
        </div>
    </div>
    <div id="topProgress" class="pointer-events-none fixed left-0 top-0 z-[10000] h-[3px] w-0 bg-primary-500 opacity-0 transition-[width,opacity] duration-300"></div>

    <script>
        (function () {
            const loader = document.getElementById('actionLoader');
            const loaderText = document.getElementById('actionLoaderText');
            const loaderHint = document.getElementById('actionLoaderHint');
            const progress = document.getElementById('topProgress');
            let progressTimer = null;

            window.showActionLoader = function (message, hint) {
                if (!loader) return;
                loaderText.textContent = message || 'Processing...';
                loaderHint.textContent = hint || "Please wait, don't close this page.";
                loader.classList.remove('hidden');
                loader.classList.add('flex');
                loader.setAttribute('aria-hidden', 'false');
                document.body.classList.add('is-loading');
                startTopProgress(80);
            };

            window.hideActionLoader = function () {
                if (!loader) return;
                loader.classList.add('hidden');
                loader.classList.remove('flex');
                loader.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('is-loading');
                finishTopProgress();
            };

            window.startTopProgress = startTopProgress;
            window.finishTopProgress = finishTopProgress;

            function startTopProgress(amount) {
                if (!progress) return;
                clearTimeout(progressTimer);
                progress.style.transition = 'none';
                progress.style.opacity = '1';
                progress.style.width = (amount || 15) + '%';

                requestAnimationFrame(() => {
                    progress.style.transition = 'width 1.8s ease-out, opacity .3s ease';
                    progress.style.width = '82%';
                });
            }

            function finishTopProgress() {
                if (!progress) return;
                clearTimeout(progressTimer);
                progress.style.transition = 'width .2s ease, opacity .3s ease';
                progress.style.width = '100%';
                progressTimer = setTimeout(() => {
                    progress.style.opacity = '0';
                    progress.style.width = '0';
                }, 220);
            }

            window.showButtonLoading = function (button, label) {
                if (!button) return function () {};
                const original = button.innerHTML;
                button.disabled = true;
                button.dataset.loading = 'true';
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>' + (label || 'Please wait...') + '</span>';
                return function () {
                    button.disabled = false;
                    delete button.dataset.loading;
                    button.innerHTML = original;
                };
            };

            // Forms: block double submits and show a clear action message.
            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (event.defaultPrevented || form.hasAttribute('data-no-loading')) return;

                const message = form.dataset.loadingText || 'Processing...';
                const hint = form.dataset.loadingHint || "Please wait, don't close this page.";
                window.showActionLoader(message, hint);
            });

            // Links with an explicit loading text are usually downloads/exports.
            document.addEventListener('click', function (event) {
                const link = event.target.closest('a[data-loading-text]');
                if (!link) return;
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                if (link.target === '_blank' || link.hasAttribute('download')) return;

                window.showActionLoader(link.dataset.loadingText, link.dataset.loadingHint || 'Please wait...');

                const timeout = parseInt(link.dataset.loadingTimeout || '0', 10);
                if (timeout > 0) setTimeout(window.hideActionLoader, timeout);
            });

            // Subtle progress bar for normal page navigation.
            document.addEventListener('click', function (event) {
                const link = event.target.closest('a[href]');
                if (!link || link.target === '_blank' || link.hasAttribute('download')) return;
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

                const href = link.getAttribute('href') || '';
                if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
                if (link.origin !== window.location.origin) return;

                startTopProgress(20);
            });

            // Live filters: submit automatically while typing or changing a select.
            // The full-page loader is skipped so filtering stays unobtrusive.
            document.querySelectorAll('form[data-live-filter]').forEach(function (form) {
                form.setAttribute('data-no-loading', '');
                let timer = null;

                const submitFilter = function () {
                    window.startTopProgress(25);
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                };

                const scheduleSubmit = function (delay) {
                    clearTimeout(timer);
                    timer = setTimeout(submitFilter, delay);
                };

                form.querySelectorAll('input[type="text"], input[type="search"]').forEach(function (input) {
                    input.addEventListener('input', function () {
                        scheduleSubmit(450);
                    });
                });

                form.querySelectorAll('select').forEach(function (select) {
                    select.addEventListener('change', function () {
                        scheduleSubmit(50);
                    });
                });

                form.addEventListener('submit', function () {
                    clearTimeout(timer);

                    const active = document.activeElement;
                    if (active instanceof HTMLInputElement && active.name) {
                        sessionStorage.setItem('liveFilterFocus', active.name);
                        sessionStorage.setItem('liveFilterCaret', String(active.selectionStart ?? 0));
                    }
                });
            });

            const focusedFilter = sessionStorage.getItem('liveFilterFocus');
            if (focusedFilter) {
                const field = Array.from(document.querySelectorAll('form[data-live-filter] [name]'))
                    .find(function (element) { return element.name === focusedFilter; });
                if (field instanceof HTMLInputElement) {
                    const caret = parseInt(sessionStorage.getItem('liveFilterCaret') || '0', 10);
                    field.focus({ preventScroll: true });
                    field.setSelectionRange(caret, caret);
                }
                sessionStorage.removeItem('liveFilterFocus');
                sessionStorage.removeItem('liveFilterCaret');
            }

            window.addEventListener('pageshow', function () {
                window.hideActionLoader();
                finishTopProgress();
            });
        })();
    </script>

    @stack('scripts')
    <script>
        (function () {
            function dismiss(alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }

            document.querySelectorAll('[data-dismiss-alert]').forEach((button) => {
                button.addEventListener('click', () => dismiss(button.closest('[role="alert"]')));
            });

            // Transient flash messages fade out on their own; validation
            // summaries stay until dismissed so the user can act on them.
            document.querySelectorAll('.flash-alert').forEach((alert) => {
                setTimeout(() => dismiss(alert), 5000);
            });
        })();
    </script>
</body>
</html>
