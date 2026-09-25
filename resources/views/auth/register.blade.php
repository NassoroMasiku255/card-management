<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Card Management System</title>
    @include('partials.head-assets')
</head>
<body class="min-h-screen bg-surface-50 flex items-center justify-center relative overflow-hidden px-4 py-10">

    <div class="absolute -top-24 -right-24 w-80 h-80 bg-gold-200/50 rounded-full decorative-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-primary-200/50 rounded-full decorative-blob"></div>

    <div class="relative max-w-md w-full">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-primary-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-soft-lg">
                <i class="fas fa-envelope-open-text text-white text-xl"></i>
            </div>
            <h1 class="font-display text-3xl font-bold text-surface-900">Card Management</h1>
            <p class="text-surface-500 mt-1.5 text-sm">Create your account</p>
        </div>

        <div class="card-surface shadow-soft-lg p-8">
            <h2 class="text-lg font-semibold text-surface-900 mb-6">Register</h2>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-5 text-sm">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="label-field">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus
                        class="input-field" placeholder="Your full name">
                </div>

                <div>
                    <label class="label-field">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="input-field" placeholder="you@example.com">
                </div>

                <div>
                    <label class="label-field">Password</label>
                    <input type="password" name="password" required
                        class="input-field" placeholder="Min. 8 characters">
                </div>

                <div>
                    <label class="label-field">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="input-field" placeholder="Repeat password">
                </div>

                <button type="submit" class="btn btn-primary btn-block py-3">
                    Create Account <i class="fas fa-arrow-right text-xs"></i>
                </button>
            </form>

            <p class="text-center text-sm text-surface-500 mt-6">
                Already have an account?
                <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700 font-semibold">Sign in</a>
            </p>
        </div>
    </div>
</body>
</html>
