@extends('layouts.app')

@section('title', 'Create Event - Card Management')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('events.index') }}" class="text-surface-500 hover:text-surface-700 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Events
        </a>
        <h1 class="font-display text-2xl font-bold text-surface-900 mt-2">Create New Event</h1>
    </div>

    <div class="card-surface p-8">
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('events.store') }}" enctype="multipart/form-data" class="space-y-7">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="label-field">Event Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="input-field" placeholder="e.g., Wedding of John &amp; Jane">
                </div>

                <div>
                    <label class="label-field">Event Date *</label>
                    <input type="date" name="event_date" value="{{ old('event_date') }}" required class="input-field">
                </div>

                <div>
                    <label class="label-field">Event Time</label>
                    <input type="time" name="event_time" value="{{ old('event_time') }}" class="input-field">
                </div>

                <div>
                    <label class="label-field">Location / Venue *</label>
                    <input type="text" name="location" value="{{ old('location') }}" required
                        class="input-field" placeholder="e.g., Diamond Jubilee Hall">
                </div>

                <div>
                    <label class="label-field">Hall Name</label>
                    <input type="text" name="hall" value="{{ old('hall') }}"
                        class="input-field" placeholder="e.g., Main Ballroom">
                </div>
            </div>

            <div>
                <label class="label-field">Description</label>
                <textarea name="description" rows="3" class="input-field"
                    placeholder="Brief description of the event...">{{ old('description') }}</textarea>
            </div>

            <!-- Card Template Selection -->
            <div>
                <label class="label-field mb-3">Card Template *</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($templates as $key => $template)
                    <label class="relative cursor-pointer">
                        <input type="radio" name="card_template" value="{{ $key }}" {{ old('card_template', 'elegant') === $key ? 'checked' : '' }} class="peer sr-only">
                        <div class="border-2 border-surface-200 rounded-xl p-4 text-center peer-checked:border-primary-500 peer-checked:bg-primary-50/60 peer-checked:shadow-soft transition hover:border-primary-300">
                            <div class="w-10 h-10 mx-auto mb-2 rounded-lg flex items-center justify-center
                                {{ $key === 'elegant' ? 'bg-gold-100' : '' }}
                                {{ $key === 'modern' ? 'bg-surface-100' : '' }}
                                {{ $key === 'floral' ? 'bg-pink-100' : '' }}
                                {{ $key === 'royal' ? 'bg-purple-100' : '' }}
                                {{ $key === 'rustic' ? 'bg-green-100' : '' }}">
                                <i class="fas
                                    {{ $key === 'elegant' ? 'fa-star text-gold-600' : '' }}
                                    {{ $key === 'modern' ? 'fa-square text-surface-600' : '' }}
                                    {{ $key === 'floral' ? 'fa-fan text-pink-600' : '' }}
                                    {{ $key === 'royal' ? 'fa-crown text-purple-600' : '' }}
                                    {{ $key === 'rustic' ? 'fa-leaf text-green-600' : '' }}"></i>
                            </div>
                            <p class="text-sm font-medium text-surface-700">{{ $template['name'] }}</p>
                            <p class="text-xs text-surface-400 mt-0.5">{{ $template['preview'] }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Colors -->
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="label-field">Background Color</label>
                    <input type="color" name="card_background_color" value="{{ old('card_background_color', '#ffffff') }}"
                        class="w-full h-10 rounded-lg border border-surface-200 cursor-pointer">
                </div>
                <div>
                    <label class="label-field">Text Color</label>
                    <input type="color" name="card_text_color" value="{{ old('card_text_color', '#333333') }}"
                        class="w-full h-10 rounded-lg border border-surface-200 cursor-pointer">
                </div>
                <div>
                    <label class="label-field">Accent Color</label>
                    <input type="color" name="card_accent_color" value="{{ old('card_accent_color', '#d4af37') }}"
                        class="w-full h-10 rounded-lg border border-surface-200 cursor-pointer">
                </div>
            </div>

            <div>
                <label class="label-field">Cover Image (optional)</label>
                <input type="file" name="cover_image" accept="image/*"
                    class="w-full text-sm text-surface-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 border border-surface-200 rounded-xl px-1 py-1">
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="btn btn-primary px-8 py-3">
                    <i class="fas fa-plus"></i> Create Event
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
