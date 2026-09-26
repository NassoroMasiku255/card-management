@extends('layouts.app')
@section('title', 'Webhook Logs')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-surface-900">Webhook Logs</h1>
            <p class="text-sm text-surface-500 mt-1">All incoming WhatsApp webhook events</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ([
            ['Total', $stats['total'], 'fa-layer-group', 'bg-surface-50 text-surface-600'],
            ['Today', $stats['today'], 'fa-clock', 'bg-blue-50 text-blue-600'],
            ['Processed', $stats['processed'], 'fa-circle-check', 'bg-green-50 text-green-600'],
            ['Errors', $stats['errors'], 'fa-circle-exclamation', 'bg-red-50 text-red-600'],
        ] as [$label, $value, $icon, $color])
        <div class="bg-white rounded-xl border border-surface-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg {{ $color }} flex items-center justify-center">
                    <i class="fas {{ $icon }}"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-surface-900">{{ number_format($value) }}</p>
                    <p class="text-xs text-surface-500">{{ $label }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-surface-200 p-4">
        <form method="GET" action="{{ route('webhook-logs.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search phone, message ID, summary..."
                    class="w-full rounded-lg border border-surface-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>
            <select name="type" class="rounded-lg border border-surface-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Types</option>
                @foreach ($eventTypes as $type)
                <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>
                    {{ str_replace('_', ' ', ucfirst($type)) }}
                </option>
                @endforeach
            </select>
            <select name="processed" class="rounded-lg border border-surface-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Status</option>
                <option value="1" {{ request('processed') === '1' ? 'selected' : '' }}>Processed</option>
                <option value="0" {{ request('processed') === '0' ? 'selected' : '' }}>Pending</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-lg hover:bg-primary-600 transition">
                <i class="fas fa-search mr-1"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'type', 'processed']))
            <a href="{{ route('webhook-logs.index') }}" class="px-4 py-2 border border-surface-200 text-surface-600 text-sm font-medium rounded-lg hover:bg-surface-50 transition text-center">
                Clear
            </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-surface-200 overflow-hidden">
        @if($logs->isEmpty())
        <div class="p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-surface-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-inbox text-2xl text-surface-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-surface-700">No webhook logs yet</h3>
            <p class="text-sm text-surface-500 mt-1">Webhook events will appear here once received</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-50 border-b border-surface-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-surface-600">Time</th>
                        <th class="text-left px-4 py-3 font-semibold text-surface-600">Type</th>
                        <th class="text-left px-4 py-3 font-semibold text-surface-600">Method</th>
                        <th class="text-left px-4 py-3 font-semibold text-surface-600">Phone</th>
                        <th class="text-left px-4 py-3 font-semibold text-surface-600">Summary</th>
                        <th class="text-left px-4 py-3 font-semibold text-surface-600">Status</th>
                        <th class="text-left px-4 py-3 font-semibold text-surface-600"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100">
                    @foreach ($logs as $log)
                    <tr class="hover:bg-surface-50 transition {{ $log->error ? 'bg-red-50/50' : '' }}">
                        <td class="px-4 py-3 whitespace-nowrap text-surface-500">
                            <span title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php
                                $badgeColor = match($log->event_type) {
                                    'message_status' => 'bg-blue-100 text-blue-700',
                                    'message_reply' => 'bg-green-100 text-green-700',
                                    'interactive_reply' => 'bg-emerald-100 text-emerald-700',
                                    'button_reply' => 'bg-teal-100 text-teal-700',
                                    'verification' => 'bg-amber-100 text-amber-700',
                                    'error' => 'bg-red-100 text-red-700',
                                    'ignored' => 'bg-surface-100 text-surface-500',
                                    default => 'bg-surface-100 text-surface-600',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $badgeColor }}">
                                {{ str_replace('_', ' ', $log->event_type ?? 'unknown') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-medium {{ $log->method === 'GET' ? 'bg-purple-100 text-purple-700' : 'bg-indigo-100 text-indigo-700' }}">
                                {{ $log->method }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-surface-700 font-mono text-xs">
                            {{ $log->source_phone ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-surface-700 max-w-xs truncate">
                            {{ $log->summary ?? '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($log->error)
                            <span class="inline-flex items-center gap-1 text-xs text-red-600">
                                <i class="fas fa-circle-xmark"></i> Error
                            </span>
                            @elseif($log->processed)
                            <span class="inline-flex items-center gap-1 text-xs text-green-600">
                                <i class="fas fa-circle-check"></i> Processed
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 text-xs text-surface-400">
                                <i class="fas fa-clock"></i> Pending
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="{{ route('webhook-logs.show', $log) }}" class="text-primary-600 hover:text-primary-800 text-xs font-medium">
                                View <i class="fas fa-arrow-right ml-0.5"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-surface-200">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
