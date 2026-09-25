@extends('layouts.app')

@section('title', 'Edit Guest - ' . $guest->full_name)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('events.guests.index', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Guests
        </a>
        <h1 class="font-display text-2xl font-bold text-surface-900 mt-2">Edit Guest</h1>
        <p class="text-surface-500 text-sm">ID: {{ $guest->unique_id }}</p>
    </div>

    <div class="card-surface p-8">
        <form method="POST" action="{{ route('events.guests.update', [$event, $guest]) }}" class="space-y-6">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="label-field">Full Name *</label>
                    <input type="text" name="full_name" value="{{ old('full_name', $guest->full_name) }}" required class="input-field">
                </div>
                <div>
                    <label class="label-field">Phone Number *</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $guest->phone_number) }}" required class="input-field">
                </div>
                <div>
                    <label class="label-field">Card Type *</label>
                    <select name="card_type" class="input-field">
                        <option value="single" {{ $guest->card_type === 'single' ? 'selected' : '' }}>Single</option>
                        <option value="double" {{ $guest->card_type === 'double' ? 'selected' : '' }}>Double (Couple)</option>
                    </select>
                </div>
                <div>
                    <label class="label-field">Amount Contributed</label>
                    <input type="number" name="amount_contributed" value="{{ old('amount_contributed', $guest->amount_contributed) }}" min="0" class="input-field">
                </div>
                <div>
                    <label class="label-field">Email</label>
                    <input type="email" name="email" value="{{ old('email', $guest->email) }}" class="input-field">
                </div>
                <div>
                    <label class="label-field">Table Number</label>
                    <input type="text" name="table_number" value="{{ old('table_number', $guest->table_number) }}" class="input-field">
                </div>
                <div>
                    <label class="label-field">Category</label>
                    <input type="text" name="category" value="{{ old('category', $guest->category) }}" class="input-field">
                </div>
            </div>
            <div>
                <label class="label-field">Notes</label>
                <textarea name="notes" rows="2" class="input-field">{{ old('notes', $guest->notes) }}</textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary px-8 py-3">
                    <i class="fas fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
