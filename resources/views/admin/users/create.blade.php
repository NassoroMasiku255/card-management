@extends('layouts.app')

@section('title', 'New User - NiaLike')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-surface-500 hover:text-surface-700 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Users
        </a>
        <h1 class="font-display text-2xl font-bold text-surface-900 mt-2">Create User</h1>
        <p class="text-surface-500 text-sm">Create an account and decide which events this person can access.</p>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}"
        data-loading-text="Creating user..." data-loading-hint="Saving the account and event access.">
        @csrf
        @include('admin.users._form', ['selectedEventIds' => []])
    </form>
</div>
@endsection
