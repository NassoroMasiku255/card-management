@extends('layouts.app')
@section('title', 'Webhook Log #' . $webhookLog->id)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('webhook-logs.index') }}" class="w-9 h-9 rounded-lg border border-surface-200 flex items-center justify-center text-surface-500 hover:bg-surface-50 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-display font-bold text-surface-900">Webhook Log #{{ $webhookLog->id }}</h1>
            <p class="text-sm text-surface-500 mt-0.5">{{ $webhookLog->created_at->format('M d, Y H:i:s') }} ({{ $webhookLog->created_at->diffForHumans() }})</p>
        </div>
    </div>

    {{-- Overview cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-surface-400 mb-3">Event Info</p>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between">
                    <dt class="text-surface-500">Direction</dt>
                    <dd class="font-medium text-surface-800">{{ ucfirst($webhookLog->direction) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-surface-500">Method</dt>
                    <dd>
                        <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-mono font-medium {{ $webhookLog->method === 'GET' ? 'bg-purple-100 text-purple-700' : 'bg-indigo-100 text-indigo-700' }}">
                            {{ $webhookLog->method }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-surface-500">Type</dt>
                    <dd>
                        @php
                            $badgeColor = match($webhookLog->event_type) {
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
                        <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium {{ $badgeColor }}">
                            {{ str_replace('_', ' ', $webhookLog->event_type ?? 'unknown') }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-surface-500">Status Code</dt>
                    <dd class="font-mono text-xs font-medium {{ ($webhookLog->status_code ?? '200') === '200' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $webhookLog->status_code ?? '-' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-surface-400 mb-3">Message Details</p>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between">
                    <dt class="text-surface-500">Phone</dt>
                    <dd class="font-mono text-xs text-surface-800">{{ $webhookLog->source_phone ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-surface-500">WA Message ID</dt>
                    <dd class="font-mono text-xs text-surface-800 max-w-[180px] truncate" title="{{ $webhookLog->whatsapp_message_id }}">
                        {{ $webhookLog->whatsapp_message_id ?? '-' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-surface-500">Summary</dt>
                    <dd class="text-surface-800 text-right max-w-[200px]">{{ $webhookLog->summary ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-surface-200 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-surface-400 mb-3">Processing</p>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between">
                    <dt class="text-surface-500">Status</dt>
                    <dd>
                        @if($webhookLog->error)
                        <span class="inline-flex items-center gap-1 text-xs text-red-600 font-medium">
                            <i class="fas fa-circle-xmark"></i> Error
                        </span>
                        @elseif($webhookLog->processed)
                        <span class="inline-flex items-center gap-1 text-xs text-green-600 font-medium">
                            <i class="fas fa-circle-check"></i> Processed
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 text-xs text-surface-400 font-medium">
                            <i class="fas fa-clock"></i> Pending
                        </span>
                        @endif
                    </dd>
                </div>
                @if($webhookLog->error)
                <div>
                    <dt class="text-surface-500 mb-1">Error</dt>
                    <dd class="text-red-700 bg-red-50 rounded-lg p-2 text-xs font-mono break-all">{{ $webhookLog->error }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Parsed Data --}}
    @if($webhookLog->parsed_data)
    <div class="bg-white rounded-xl border border-surface-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-surface-200 flex items-center gap-2">
            <i class="fas fa-puzzle-piece text-surface-400"></i>
            <h2 class="font-semibold text-surface-800">Parsed Data</h2>
        </div>
        <div class="p-5">
            <pre class="bg-surface-900 text-green-400 rounded-lg p-4 text-xs font-mono overflow-x-auto max-h-96"><code>{{ json_encode($webhookLog->parsed_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
        </div>
    </div>
    @endif

    {{-- Full Payload --}}
    @if($webhookLog->payload)
    <div class="bg-white rounded-xl border border-surface-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-surface-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-code text-surface-400"></i>
                <h2 class="font-semibold text-surface-800">Full Payload</h2>
            </div>
            <button onclick="navigator.clipboard.writeText(document.getElementById('payload-json').textContent)" class="text-xs text-primary-600 hover:text-primary-800 font-medium">
                <i class="fas fa-copy mr-1"></i> Copy
            </button>
        </div>
        <div class="p-5">
            <pre id="payload-json" class="bg-surface-900 text-emerald-400 rounded-lg p-4 text-xs font-mono overflow-x-auto max-h-[500px]"><code>{{ json_encode($webhookLog->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
        </div>
    </div>
    @endif

    {{-- Headers --}}
    @if($webhookLog->headers)
    <div class="bg-white rounded-xl border border-surface-200 overflow-hidden">
        <details>
            <summary class="px-5 py-3 border-b border-surface-200 flex items-center gap-2 cursor-pointer hover:bg-surface-50 transition">
                <i class="fas fa-heading text-surface-400"></i>
                <h2 class="font-semibold text-surface-800">Request Headers</h2>
                <span class="text-xs text-surface-400 ml-auto">Click to expand</span>
            </summary>
            <div class="p-5">
                <pre class="bg-surface-900 text-amber-400 rounded-lg p-4 text-xs font-mono overflow-x-auto max-h-96"><code>{{ json_encode($webhookLog->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
        </details>
    </div>
    @endif

</div>
@endsection
