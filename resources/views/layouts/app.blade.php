<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Card Management System')</title>
    @include('partials.head-assets')
    @stack('styles')
</head>
<body class="bg-surface-50 min-h-screen text-surface-800 antialiased">

    <div class="min-h-screen md:flex">

        <!-- Sidebar (desktop) -->
        <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 border-r border-surface-200 bg-white">
            <div class="flex items-center gap-2.5 px-6 h-16 border-b border-surface-100">
                <div class="w-9 h-9 rounded-xl bg-primary-500 flex items-center justify-center shadow-soft shrink-0">
                    <i class="fas fa-envelope-open-text text-white text-sm"></i>
                </div>
                <span class="font-display font-bold text-lg text-surface-900 tracking-tight">CardMS</span>
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
                <a href="{{ route('webhook-logs.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                    {{ request()->routeIs('webhook-logs.*') ? 'bg-primary-50 text-primary-700' : 'text-surface-600 hover:bg-surface-100 hover:text-surface-900' }}">
                    <i class="fab fa-whatsapp w-4 text-center"></i> Webhook Logs
                </a>

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
                    <form method="POST" action="{{ route('logout') }}">
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
        <header class="md:hidden sticky top-0 z-30 bg-white border-b border-surface-200">
            <div class="flex items-center justify-between h-16 px-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-primary-500 flex items-center justify-center">
                        <i class="fas fa-envelope-open-text text-white text-xs"></i>
                    </div>
                    <span class="font-display font-bold text-surface-900">CardMS</span>
                </a>
                @auth
                <div class="flex items-center gap-4">
                    <a href="{{ route('dashboard') }}" class="text-surface-500 {{ request()->routeIs('dashboard') ? 'text-primary-600' : '' }}"><i class="fas fa-house"></i></a>
                    <a href="{{ route('events.index') }}" class="text-surface-500 {{ request()->routeIs('events.*') ? 'text-primary-600' : '' }}"><i class="fas fa-calendar-days"></i></a>
                    <a href="{{ route('webhook-logs.index') }}" class="text-surface-500 {{ request()->routeIs('webhook-logs.*') ? 'text-primary-600' : '' }}"><i class="fab fa-whatsapp"></i></a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-surface-400 hover:text-red-600"><i class="fas fa-arrow-right-from-bracket"></i></button>
                    </form>
                </div>
                @endauth
            </div>
        </header>

        <!-- Main -->
        <div class="flex-1 md:pl-64 min-w-0">
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

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
