<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use Illuminate\Http\Request;

class WebhookLogController extends Controller
{
    public function index(Request $request)
    {
        $query = WebhookLog::query()->latest();

        if ($type = $request->input('type')) {
            $query->where('event_type', $type);
        }

        if ($request->input('processed') !== null && $request->input('processed') !== '') {
            $query->where('processed', $request->boolean('processed'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('source_phone', 'like', "%{$search}%")
                  ->orWhere('whatsapp_message_id', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        $eventTypes = WebhookLog::select('event_type')
            ->distinct()
            ->whereNotNull('event_type')
            ->pluck('event_type');

        $stats = [
            'total' => WebhookLog::count(),
            'today' => WebhookLog::whereDate('created_at', today())->count(),
            'processed' => WebhookLog::where('processed', true)->count(),
            'errors' => WebhookLog::whereNotNull('error')->count(),
        ];

        $logs = $query->paginate(25)->withQueryString();

        return view('webhook-logs.index', compact('logs', 'eventTypes', 'stats'));
    }

    public function show(WebhookLog $webhookLog)
    {
        return view('webhook-logs.show', compact('webhookLog'));
    }
}
