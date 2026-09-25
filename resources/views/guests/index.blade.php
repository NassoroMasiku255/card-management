@extends('layouts.app')

@section('title', 'Guests - ' . $event->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('events.show', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $event->name }}
    </a>
    <div class="flex flex-wrap items-center justify-between gap-3 mt-2">
        <h1 class="font-display text-2xl font-bold text-surface-900">Guests</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('events.guests.export', $event) }}" class="btn btn-outline btn-sm">
                <i class="fas fa-download"></i> Export
            </a>
            <a href="{{ route('events.guests.import', $event) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-import"></i> Import
            </a>
            <a href="{{ route('events.guests.create', $event) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Guest
            </a>
        </div>
    </div>
</div>

<!-- Search & Filter -->
<div class="card-surface p-4 mb-4">
    <form method="GET" action="{{ route('events.guests.index', $event) }}" class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone, ID..."
                class="input-field !py-2">
        </div>
        <select name="card_type" class="input-field !py-2 !w-auto">
            <option value="">All Card Types</option>
            <option value="single" {{ request('card_type') === 'single' ? 'selected' : '' }}>Single</option>
            <option value="double" {{ request('card_type') === 'double' ? 'selected' : '' }}>Double</option>
        </select>
        <select name="rsvp" class="input-field !py-2 !w-auto">
            <option value="">All RSVP</option>
            <option value="attending" {{ request('rsvp') === 'attending' ? 'selected' : '' }}>Nitafika</option>
            <option value="not_attending" {{ request('rsvp') === 'not_attending' ? 'selected' : '' }}>Sitafika</option>
            <option value="pending" {{ request('rsvp') === 'pending' ? 'selected' : '' }}>Pending</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-magnifying-glass"></i> Filter
        </button>
        @if(request()->hasAny(['search', 'card_type', 'rsvp']))
        <a href="{{ route('events.guests.index', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">Clear</a>
        @endif
    </form>
</div>

<div class="card-surface overflow-hidden">
    @if($guests->isEmpty())
    <div class="px-6 py-12 text-center">
        <div class="w-16 h-16 bg-surface-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-user-plus text-surface-400 text-xl"></i>
        </div>
        <h3 class="text-surface-700 font-medium">No guests yet</h3>
        <p class="text-surface-400 text-sm mt-1">Add guests manually or import from Excel</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-surface-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Card</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">RSVP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @foreach($guests as $guest)
                <tr class="hover:bg-surface-50">
                    <td class="px-6 py-3 text-xs font-mono text-surface-500">{{ $guest->unique_id }}</td>
                    <td class="px-6 py-3">
                        <div class="font-medium text-surface-900">{{ $guest->full_name }}</div>
                        @if($guest->category)<div class="text-xs text-surface-400">{{ $guest->category }}</div>@endif
                    </td>
                    <td class="px-6 py-3 text-sm text-surface-600">{{ $guest->phone_number }}</td>
                    <td class="px-6 py-3">
                        <span class="badge {{ $guest->card_type === 'double' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($guest->card_type) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm text-surface-600">{{ number_format($guest->amount_contributed) }}</td>
                    <td class="px-6 py-3">
                        @if($guest->invitation)
                        <span class="badge
                            {{ $guest->invitation->rsvp_status === 'attending' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $guest->invitation->rsvp_status === 'not_attending' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $guest->invitation->rsvp_status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}">
                            {{ $guest->invitation->rsvp_status === 'attending' ? 'Nitafika' : ($guest->invitation->rsvp_status === 'not_attending' ? 'Sitafika' : 'Pending') }}
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('events.guests.edit', [$event, $guest]) }}" class="text-blue-600 hover:text-blue-700" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('events.guests.destroy', [$event, $guest]) }}" onsubmit="return confirm('Remove this guest?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700" title="Remove">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-surface-100">
        {{ $guests->links() }}
    </div>
    @endif
</div>
@endsection
