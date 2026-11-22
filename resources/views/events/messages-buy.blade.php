@extends('layouts.app')

@section('title', 'Buy Messages - ' . $event->name)

@section('content')
<!-- Breadcrumb -->
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <a href="{{ route('events.messages.report', $event) }}" class="text-sm font-medium text-surface-500 transition hover:text-surface-800">
        <i class="fas fa-arrow-left mr-1.5"></i> Back to messages
    </a>
    <div class="flex items-center gap-2 text-xs text-surface-400">
        <span class="max-w-[160px] truncate">{{ $event->name }}</span>
        <i class="fas fa-chevron-right text-[8px]"></i>
        <span class="font-medium text-surface-600">Buy messages</span>
    </div>
</div>

<!-- Header -->
<div class="card-surface mb-6 overflow-hidden border-l-4 border-primary-500">
    <div class="p-5 sm:p-6">
        <span class="badge bg-amber-100 text-amber-700">Coming soon</span>
        <h1 class="mt-2.5 font-display text-2xl font-bold leading-tight text-surface-900 sm:text-3xl">Buy Messages</h1>
        <p class="mt-2 max-w-2xl text-sm text-surface-600">
            Top up WhatsApp or SMS messages for <span class="font-medium text-surface-900">{{ $event->name }}</span> and pay with mobile money.
        </p>
    </div>

    @include('events.partials.section-nav', ['active' => 'messages'])
</div>

@if(session('purchase_coming_soon'))
<div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4" role="alert" id="purchaseComingSoon">
    <i class="fas fa-clock mt-0.5 text-amber-500"></i>
    <div>
        <p class="text-sm font-semibold text-amber-900">This service is coming soon</p>
        <p class="mt-1 text-sm leading-relaxed text-amber-800">
            Buying messages with mobile money is not available yet. No payment was taken and no messages were added.
            For now, please ask an administrator to add message balance to this event.
        </p>
    </div>
</div>
@endif

<div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
    <!-- Order form -->
    <form method="POST" action="{{ route('events.messages.buy.submit', $event) }}" class="card-surface overflow-hidden lg:col-span-2"
        data-loading-text="Checking your order...">
        @csrf
        <div class="border-b border-surface-100 px-5 py-4 sm:px-6">
            <h2 class="text-sm font-semibold text-surface-900">Your order</h2>
            <p class="mt-0.5 text-xs text-surface-500">Choose the message type, how many you need and how you will pay.</p>
        </div>

        <div class="space-y-6 p-5 sm:p-6">
            <div>
                <p class="label-field">1. Message type</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($channels as $value => $label)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-surface-200 p-4 transition hover:border-surface-300 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                        <input type="radio" name="channel" value="{{ $value }}" class="h-4 w-4 border-surface-300 text-primary-600 focus:ring-primary-500"
                            @checked(old('channel', 'whatsapp') === $value) required>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-surface-900">{{ $label }} messages</span>
                            <span class="block text-xs text-surface-500">Current balance: {{ number_format($event->messageBalance($value)) }}</span>
                        </span>
                    </label>
                    @endforeach
                </div>
                @error('channel')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="buy_quantity" class="label-field">2. Number of messages</label>
                <div class="mb-3 flex flex-wrap gap-2">
                    @foreach($packages as $package)
                    <button type="button" data-package="{{ $package }}"
                        class="buy-package rounded-lg border border-surface-200 bg-white px-3 py-1.5 text-xs font-medium text-surface-600 transition hover:border-surface-300 hover:bg-surface-50 hover:text-surface-900">
                        {{ number_format($package) }}
                    </button>
                    @endforeach
                </div>
                <input type="number" name="quantity" id="buy_quantity" value="{{ old('quantity', $packages[0]) }}" min="1" max="1000000" required class="input-field sm:max-w-xs">
                @error('quantity')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <p class="label-field">3. Pay with mobile money</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($networks as $value => $label)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-surface-200 p-3.5 transition hover:border-surface-300 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                        <input type="radio" name="network" value="{{ $value }}" class="h-4 w-4 border-surface-300 text-primary-600 focus:ring-primary-500"
                            @checked(old('network') === $value) required>
                        <span class="text-sm font-medium text-surface-800">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
                @error('network')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="buy_phone" class="label-field">4. Mobile money phone number</label>
                <input type="tel" name="phone_number" id="buy_phone" value="{{ old('phone_number') }}" maxlength="20" required class="input-field sm:max-w-xs" placeholder="e.g. 0712 345 678">
                <p class="mt-1.5 text-xs text-surface-400">A payment request will be sent to this number.</p>
                @error('phone_number')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-surface-100 px-5 py-4 sm:px-6">
            <a href="{{ route('events.messages.report', $event) }}" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-lock"></i> Continue to payment</button>
        </div>
    </form>

    <!-- Summary -->
    <div class="space-y-6">
        <div class="card-surface p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-surface-900">Current balance</h3>
            <div class="mt-3 divide-y divide-surface-100">
                @foreach($channels as $value => $label)
                <div class="flex items-center justify-between py-2.5">
                    <span class="text-sm text-surface-600">{{ $label }} messages</span>
                    <span class="text-sm font-semibold {{ $event->messageBalance($value) > 0 ? 'text-surface-900' : 'text-red-600' }}">{{ number_format($event->messageBalance($value)) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="card-surface p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-surface-900">Online payment is coming soon</h3>
            <p class="mt-2 text-xs leading-relaxed text-surface-500">
                Paying with mobile money is not available yet, so no payment will be taken from this page.
                For now, an administrator adds message balance to your event manually.
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.buy-package').forEach(button => {
        button.addEventListener('click', () => {
            document.getElementById('buy_quantity').value = button.dataset.package;
        });
    });
</script>
@endpush
