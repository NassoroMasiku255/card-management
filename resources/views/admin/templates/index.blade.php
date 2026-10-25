@extends('layouts.app')

@section('title', 'WhatsApp Templates - Admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.index') }}" class="text-surface-500 hover:text-surface-700 text-sm">
                    <i class="fas fa-shield-halved mr-1"></i> Admin
                </a>
                <span class="text-surface-300">/</span>
                <span class="text-sm font-medium text-surface-700">WhatsApp Templates</span>
            </div>
            <h1 class="font-display font-bold text-2xl text-surface-900 mt-1">WhatsApp Message Templates</h1>
            <p class="text-sm text-surface-500">
                Sync approved templates directly from Meta WhatsApp Cloud API to use across invitations and reminders.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <form method="POST" action="{{ route('admin.whatsapp-templates.sync') }}"
                data-loading-text="Syncing from Meta WhatsApp..."
                data-loading-hint="Fetching all templates from Meta WhatsApp Business API.">
                @csrf
                <button type="submit" class="btn btn-primary inline-flex items-center gap-2 shadow-sm">
                    <i class="fas fa-rotate"></i>
                    <span>Sync from WhatsApp</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-surface-200 p-4 shadow-soft">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-surface-400">Total Templates</span>
                <span class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center text-sm">
                    <i class="fab fa-whatsapp"></i>
                </span>
            </div>
            <div class="text-2xl font-bold text-surface-900 mt-2">{{ number_format($totalCount) }}</div>
            <div class="text-xs text-surface-500 mt-1">Saved in database</div>
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-4 shadow-soft">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-surface-400">Approved</span>
                <span class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center text-sm">
                    <i class="fas fa-circle-check"></i>
                </span>
            </div>
            <div class="text-2xl font-bold text-green-600 mt-2">{{ number_format($approvedCount) }}</div>
            <div class="text-xs text-surface-500 mt-1">Ready for sending</div>
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-4 shadow-soft">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-surface-400">Languages</span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                    <i class="fas fa-language"></i>
                </span>
            </div>
            <div class="text-2xl font-bold text-blue-600 mt-2">{{ number_format($languagesCount) }}</div>
            <div class="text-xs text-surface-500 mt-1">Unique language codes</div>
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-4 shadow-soft">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-surface-400">Last Synced</span>
                <span class="w-8 h-8 rounded-lg bg-surface-100 text-surface-600 flex items-center justify-center text-sm">
                    <i class="fas fa-clock"></i>
                </span>
            </div>
            <div class="text-sm font-bold text-surface-800 mt-2 truncate">
                {{ $lastSyncedAt ? \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() : 'Never' }}
            </div>
            <div class="text-xs text-surface-400 mt-1 truncate">
                {{ $lastSyncedAt ? \Carbon\Carbon::parse($lastSyncedAt)->format('M d, Y H:i') : 'Click Sync button above' }}
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-xl border border-surface-200 p-4 shadow-soft">
        <form method="GET" action="{{ route('admin.whatsapp-templates.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 text-sm"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search template name, language, category, or content..."
                    class="input-field !pl-9">
            </div>
            <div class="w-full sm:w-48">
                <select name="status" class="input-field" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                    <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>PENDING</option>
                    <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
                    <option value="PAUSED" {{ request('status') === 'PAUSED' ? 'selected' : '' }}>PAUSED</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.whatsapp-templates.index') }}" class="btn btn-outline btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Templates Table -->
    <div class="bg-white rounded-xl border border-surface-200 shadow-soft overflow-hidden">
        @if($templates->isEmpty())
        <div class="text-center py-12 px-4">
            <div class="w-16 h-16 rounded-full bg-surface-100 text-surface-400 flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fab fa-whatsapp"></i>
            </div>
            <h3 class="font-display font-semibold text-lg text-surface-800">No WhatsApp Templates Found</h3>
            <p class="text-sm text-surface-500 max-w-md mx-auto mt-1">
                @if(request()->hasAny(['search', 'status']))
                No templates matched your search filters. Try clearing the filter.
                @else
                Sync your templates from Meta WhatsApp Business Account to load and manage them here.
                @endif
            </p>
            @unless(request()->hasAny(['search', 'status']))
            <form method="POST" action="{{ route('admin.whatsapp-templates.sync') }}" class="mt-4"
                data-loading-text="Syncing from Meta WhatsApp...">
                @csrf
                <button type="submit" class="btn btn-primary inline-flex items-center gap-2">
                    <i class="fas fa-rotate"></i>
                    <span>Sync Templates Now</span>
                </button>
            </form>
            @endunless
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-surface-100 bg-surface-50 text-xs font-semibold uppercase tracking-wider text-surface-500">
                        <th class="px-6 py-3.5">Template Name</th>
                        <th class="px-6 py-3.5">Language</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Header Type</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Body Preview</th>
                        <th class="px-6 py-3.5">Last Synced</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100 text-surface-700">
                    @foreach($templates as $template)
                    <tr class="hover:bg-surface-50/75 transition-colors">
                        <td class="px-6 py-4 font-mono font-medium text-surface-900">
                            {{ $template->name }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                {{ $template->language }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs font-medium text-surface-600">
                            {{ $template->category ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-xs font-medium text-surface-600">
                            @if($template->header_type)
                            <span class="inline-flex items-center gap-1">
                                <i class="fas {{ $template->header_type === 'IMAGE' ? 'fa-image' : 'fa-heading' }} text-surface-400"></i>
                                {{ $template->header_type }}
                            </span>
                            @else
                            <span class="text-surface-400">None</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($template->status === 'APPROVED')
                            <span class="badge bg-green-100 text-green-700 inline-flex items-center gap-1">
                                <i class="fas fa-check text-[10px]"></i> APPROVED
                            </span>
                            @elseif($template->status === 'PENDING')
                            <span class="badge bg-amber-100 text-amber-700 inline-flex items-center gap-1">
                                <i class="fas fa-clock text-[10px]"></i> PENDING
                            </span>
                            @elseif($template->status === 'REJECTED')
                            <span class="badge bg-red-100 text-red-700 inline-flex items-center gap-1">
                                <i class="fas fa-times text-[10px]"></i> REJECTED
                            </span>
                            @else
                            <span class="badge bg-surface-100 text-surface-600">
                                {{ $template->status }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 max-w-xs truncate text-xs text-surface-600" title="{{ $template->body_text }}">
                            {{ $template->body_text ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-xs text-surface-500 whitespace-nowrap">
                            {{ $template->synced_at ? $template->synced_at->format('M d, Y H:i') : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($templates->hasPages())
        <div class="px-6 py-4 border-t border-surface-100">
            {{ $templates->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
