@extends('layouts.app')

@section('title', 'Add Guest - ' . $event->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('events.guests.index', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Guests
        </a>
        <h1 class="font-display text-2xl font-bold text-surface-900 mt-2">Add Guest</h1>
        <p class="text-surface-500 text-sm">for {{ $event->name }}</p>
    </div>

    <div class="card-surface p-8">
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('events.guests.store', $event) }}" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="label-field">Full Name *</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" required
                        class="input-field" placeholder="Guest full name">
                </div>

                <div>
                    <label class="label-field">Phone Number *</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" required
                        class="input-field" placeholder="0712345678">
                </div>

                <div>
                    <label class="label-field">Card Type *</label>
                    <select name="card_type" class="input-field">
                        <option value="single" {{ old('card_type') === 'single' ? 'selected' : '' }}>Single</option>
                        <option value="double" {{ old('card_type') === 'double' ? 'selected' : '' }}>Double (Couple)</option>
                    </select>
                </div>

                <div>
                    <label class="label-field">Amount Contributed (TZS)</label>
                    <input type="number" name="amount_contributed" value="{{ old('amount_contributed', 0) }}" min="0" class="input-field">
                </div>

                <div>
                    <label class="label-field">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="input-field">
                </div>

                <div>
                    <label class="label-field">Table Number</label>
                    <input type="text" name="table_number" value="{{ old('table_number') }}" class="input-field">
                </div>

                <div>
                    <label class="label-field">Category</label>
                    <input type="text" name="category" value="{{ old('category') }}"
                        class="input-field" placeholder="e.g., Family, Friends, Colleagues">
                </div>
            </div>

            <div>
                <label class="label-field">Notes</label>
                <textarea name="notes" rows="2" class="input-field">{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary px-8 py-3">
                    <i class="fas fa-plus"></i> Add Guest
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
