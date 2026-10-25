@extends('layouts.app')

@section('title', 'Edit ' . $event->name)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('events.show', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Event
        </a>
        <h1 class="font-display text-2xl font-bold text-surface-900 mt-2">Edit Event</h1>
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

        <form method="POST" action="{{ route('events.update', $event) }}" enctype="multipart/form-data" class="space-y-7"
            data-loading-text="Saving event changes..." data-loading-hint="Updating event details and cover image.">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="label-field">Event Name *</label>
                    <input type="text" name="name" value="{{ old('name', $event->name) }}" required class="input-field">
                </div>

                <div>
                    <label class="label-field">Event Date *</label>
                    <input type="date" name="event_date" value="{{ old('event_date', $event->event_date->format('Y-m-d')) }}" required class="input-field">
                </div>

                <div>
                    <label class="label-field">Event Time</label>
                    <input type="time" name="event_time" value="{{ old('event_time', $event->event_time) }}" class="input-field">
                </div>

                <div>
                    <label class="label-field">Location *</label>
                    <input type="text" name="location" value="{{ old('location', $event->location) }}" required class="input-field">
                </div>

                <div>
                    <label class="label-field">Hall</label>
                    <input type="text" name="hall" value="{{ old('hall', $event->hall) }}" class="input-field">
                </div>

                <div>
                    <label class="label-field">Status</label>
                    <select name="status" class="input-field">
                        <option value="draft" {{ $event->status === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ $event->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ $event->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $event->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="label-field">Description</label>
                <textarea name="description" rows="3" class="input-field">{{ old('description', $event->description) }}</textarea>
            </div>

            <div>
                <label class="label-field mb-3">Card Template</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($templates as $key => $template)
                    <label class="relative cursor-pointer">
                        <input type="radio" name="card_template" value="{{ $key }}" {{ old('card_template', $event->card_template) === $key ? 'checked' : '' }} class="peer sr-only">
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

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="label-field">Background</label>
                    <input type="color" name="card_background_color" value="{{ old('card_background_color', $event->card_background_color ?: '#ffffff') }}"
                        class="w-full h-10 rounded-lg border border-surface-200 cursor-pointer">
                </div>
                <div>
                    <label class="label-field">Text</label>
                    <input type="color" name="card_text_color" value="{{ old('card_text_color', $event->card_text_color ?: '#333333') }}"
                        class="w-full h-10 rounded-lg border border-surface-200 cursor-pointer">
                </div>
                <div>
                    <label class="label-field">Accent</label>
                    <input type="color" name="card_accent_color" value="{{ old('card_accent_color', $event->card_accent_color ?: '#d4af37') }}"
                        class="w-full h-10 rounded-lg border border-surface-200 cursor-pointer">
                </div>
            </div>

            <div class="rounded-xl border border-surface-200 bg-surface-50 p-4">
                <h3 class="font-display text-sm font-bold text-surface-800 mb-1">Invitation Card Minimums</h3>
                <p class="text-xs text-surface-400 mb-3">
                    Paid amounts decide whether a guest qualifies for an invitation and whether the card is Single or Double.
                    Leave blank to use the defaults.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label-field">Minimum Single Amount (TZS)</label>
                        <input type="number" name="single_card_min_amount" value="{{ old('single_card_min_amount', $event->single_card_minimum) }}"
                            min="1" step="0.01" class="input-field" placeholder="70000">
                    </div>
                    <div>
                        <label class="label-field">Minimum Double Amount (TZS)</label>
                        <input type="number" name="double_card_min_amount" value="{{ old('double_card_min_amount', $event->double_card_minimum) }}"
                            min="1" step="0.01" class="input-field" placeholder="100000">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4">
                <button type="button" onclick="document.getElementById('delete-event-form').requestSubmit()" class="text-red-600 hover:text-red-700 font-medium text-sm">
                    <i class="fas fa-trash mr-1"></i> Delete Event
                </button>
                <button type="submit" class="btn btn-primary px-8 py-3">
                    <i class="fas fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>

        <form id="delete-event-form" method="POST" action="{{ route('events.destroy', $event) }}"
            data-loading-text="Deleting event..." data-loading-hint="Removing the event, its guests and invitations."
            onsubmit="return confirm('Are you sure you want to delete this event? All guests and invitations will be lost.')" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

@push('scripts')
<script>
    const templateColors = {
        elegant: { bg: '#ffffff', text: '#333333', accent: '#d4af37' },
        modern: { bg: '#ffffff', text: '#18181b', accent: '#2563eb' },
        floral: { bg: '#fff5f7', text: '#374151', accent: '#e11d48' },
        royal: { bg: '#ffffff', text: '#1f2937', accent: '#7c3aed' },
        rustic: { bg: '#fcfbf7', text: '#292524', accent: '#15803d' }
    };

    document.querySelectorAll('input[name="card_template"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const colors = templateColors[this.value];
            if (colors) {
                const bgInput = document.querySelector('input[name="card_background_color"]');
                const textInput = document.querySelector('input[name="card_text_color"]');
                const accentInput = document.querySelector('input[name="card_accent_color"]');
                if (bgInput) bgInput.value = colors.bg;
                if (textInput) textInput.value = colors.text;
                if (accentInput) accentInput.value = colors.accent;
            }
        });
    });
</script>
@endpush
@endsection
