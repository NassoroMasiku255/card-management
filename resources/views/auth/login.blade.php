<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Card Management System</title>
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-surface-50 flex items-center justify-center relative overflow-hidden px-4">

    <div class="absolute -top-24 -left-24 w-80 h-80 bg-primary-200/50 rounded-full decorative-blob"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-gold-200/50 rounded-full decorative-blob"></div>

    <div class="relative max-w-md w-full">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-primary-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-soft-lg">
                <i class="fas fa-envelope-open-text text-white text-xl"></i>
            </div>
            <h1 class="font-display text-3xl font-bold text-surface-900">Card Management</h1>
            <p class="text-surface-500 mt-1.5 text-sm">Wedding &amp; event invitation system</p>
        </div>

        <div class="card-surface shadow-soft-lg p-8">
            <h2 class="text-lg font-semibold text-surface-900 mb-6">Sign in to your account</h2>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-5 text-sm flex items-start gap-2">
                <i class="fas fa-circle-exclamation mt-0.5"></i><span>{{ $errors->first() }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="label-field">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="input-field" placeholder="you@example.com">
                </div>

                <div>
                    <label class="label-field">Password</label>
                    <input type="password" name="password" required
                        class="input-field" placeholder="Enter your password">
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="rounded border-surface-300 text-primary-500 focus:ring-primary-400">
                        <span class="text-sm text-surface-600">Remember me</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block py-3">
                    Sign In <i class="fas fa-arrow-right text-xs"></i>
                </button>
            </form>

            <p class="text-center text-sm text-surface-500 mt-6">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-primary-600 hover:text-primary-700 font-semibold">Create one</a>
            </p>
        </div>
    </div>
</body>
</html>
