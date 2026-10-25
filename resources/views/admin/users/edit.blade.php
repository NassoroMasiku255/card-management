@extends('layouts.app')

@section('title', 'Manage User')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-surface-500 hover:text-surface-700 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Users
        </a>
        <h1 class="font-display text-2xl font-bold text-surface-900 mt-2">Manage {{ $user->name }}</h1>
        <p class="text-surface-500 text-sm">Update account details and assigned event access.</p>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}"
        data-loading-text="Saving user..." data-loading-hint="Updating account and event access.">
        @csrf
        @method('PUT')
        @include('admin.users._form', ['selectedEventIds' => $user->events->pluck('id')->all()])
    </form>
</div>
@endsection
