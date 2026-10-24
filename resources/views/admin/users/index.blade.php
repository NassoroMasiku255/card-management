@extends('layouts.app')

@section('title', 'Users - Card Management')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display text-2xl font-bold text-surface-900">Users &amp; Event Access</h1>
        <p class="text-surface-500 mt-1">Create team members and assign the events they are allowed to manage.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> New User
    </a>
</div>

<div class="card-surface p-4 mb-6 border-l-4 border-primary-500">
    <div class="flex items-start gap-3">
        <i class="fas fa-shield-halved text-primary-500 mt-0.5"></i>
        <div>
            <p class="text-sm font-semibold text-surface-800">Event access is enforced</p>
            <p class="text-xs text-surface-500 mt-0.5">
                Regular users only see the events assigned to them, including guests, invitations, reports, and the scanner.
                Administrators can see all events.
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="card-surface p-4 text-center">
        <div class="text-2xl font-bold text-surface-900">{{ $users->count() }}</div>
        <div class="text-xs text-surface-500">Total Users</div>
    </div>
    <div class="card-surface p-4 text-center">
        <div class="text-2xl font-bold text-primary-600">{{ $users->where('is_admin', true)->count() }}</div>
        <div class="text-xs text-surface-500">Administrators</div>
    </div>
    <div class="card-surface p-4 text-center">
        <div class="text-2xl font-bold text-blue-600">{{ $users->where('is_admin', false)->count() }}</div>
        <div class="text-xs text-surface-500">Regular Users</div>
    </div>
    <div class="card-surface p-4 text-center">
        <div class="text-2xl font-bold text-green-600">{{ $users->sum('events_count') }}</div>
        <div class="text-xs text-surface-500">Assigned Events</div>
    </div>
</div>

<div class="card-surface overflow-hidden">
    @if($users->isEmpty())
    <div class="px-6 py-12 text-center text-surface-400">
        <p>No users yet.</p>
    </div>
    @else
    <div class="overflow-x-auto responsive-table-wrap">
        <table class="w-full responsive-table">
            <thead class="bg-surface-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">WhatsApp</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-surface-500 uppercase tracking-wide">Assigned Events</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-surface-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @foreach($users as $user)
                <tr class="hover:bg-surface-50">
                    <td class="px-6 py-4" data-label="User">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gold-100 text-gold-700 flex items-center justify-center text-sm font-bold shrink-0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-medium text-surface-900">
                                    {{ $user->name }}
                                    @if($user->id === Auth::id())
                                    <span class="text-xs text-surface-400">(You)</span>
                                    @endif
                                </div>
                                <div class="text-xs text-surface-400 truncate">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4" data-label="Role">
                        <span class="badge {{ $user->is_admin ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $user->is_admin ? 'Administrator' : 'Regular User' }}
                        </span>
                    </td>
                    <td class="px-6 py-4" data-label="WhatsApp">
                        @if($user->canSendWhatsApp())
                        <span class="badge bg-green-100 text-green-700"><i class="fab fa-whatsapp mr-1"></i>Can send</span>
                        @else
                        <span class="badge bg-surface-100 text-surface-500"><i class="fas fa-lock mr-1"></i>View only</span>
                        @endif
                    </td>
                    <td class="px-6 py-4" data-label="Events">
                        @if($user->is_admin)
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="badge bg-purple-100 text-purple-700">All events</span>
                            @if($user->events->isNotEmpty())
                            <span class="text-xs text-surface-400">{{ $user->events->count() }} owned</span>
                            @endif
                        </div>
                        @elseif($user->events->isEmpty())
                        <span class="text-xs text-surface-400">No events assigned</span>
                        @else
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($user->events->sortBy('name') as $event)
                            <span class="badge bg-surface-100 text-surface-600" title="{{ $event->event_date->format('d M Y') }}">
                                {{ $event->name }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right mobile-actions" data-label="">
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                            <i class="fas fa-user-pen mr-1"></i> Manage access
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
