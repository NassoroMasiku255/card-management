<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\WebhookLog;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Str;

class EventMessageReportController extends Controller
{
    public function show(Event $event)
    {
        $this->authorizeEvent($event);

        $event->load(['guests']);

        $messages = WhatsAppMessage::where('event_id', $event->id)
            ->with(['guest', 'invitation'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $totalMessages = $messages->count();
        $delivered = $messages->filter(fn ($message) => in_array($message->status, ['delivered', 'read'], true))->count();
        $read = $messages->where('status', 'read')->count();
        $failed = $messages->where('status', 'failed')->count();
        $pending = $messages->filter(fn ($message) => in_array($message->status, ['queued', 'accepted', 'sent'], true))->count();
        $guestsReached = $messages->pluck('guest_id')->filter()->unique()->count();

        $replies = WebhookLog::where('event_id', $event->id)
            ->whereIn('event_type', WebhookLog::REPLY_EVENT_TYPES)
            ->with('guest.invitation')
            ->latest()
            ->get();

        $replyCount = $replies->count();
        $uniqueRepliers = $replies->pluck('guest_id')->filter()->unique()->count();
        $replyRate = $totalMessages > 0 ? (int) min(100, round(($replyCount / $totalMessages) * 100)) : 0;
        $deliveryRate = $totalMessages > 0 ? (int) round(($delivered / $totalMessages) * 100) : 0;
        $readRate = $delivered > 0 ? (int) round(($read / $delivered) * 100) : 0;
        $failureRate = $totalMessages > 0 ? (int) round(($failed / $totalMessages) * 100) : 0;

        $stats = compact(
            'totalMessages',
            'delivered',
            'read',
            'failed',
            'pending',
            'guestsReached',
            'replyCount',
            'uniqueRepliers',
            'replyRate',
            'deliveryRate',
            'readRate',
            'failureRate',
        );

        $channels = [
            'whatsapp' => [
                'label' => 'WhatsApp',
                'total' => $totalMessages,
                'delivered' => $delivered,
                'read' => $read,
                'failed' => $failed,
                'pending' => $pending,
                'available' => true,
            ],
            'normal' => [
                'label' => 'Normal messages',
                'total' => 0,
                'delivered' => 0,
                'read' => 0,
                'failed' => 0,
                'pending' => 0,
                'available' => false,
            ],
        ];

        $typeLabels = [
            'invitation' => 'Invitations',
            'reminder' => 'Reminders',
            'payment_thank_you' => 'Payment thank-you',
        ];

        $typeStats = $messages
            ->groupBy('message_type')
            ->map(function ($group, $type) use ($typeLabels) {
                $delivered = $group->filter(fn ($message) => in_array($message->status, ['delivered', 'read'], true))->count();

                return [
                    'type' => $type,
                    'label' => $typeLabels[$type] ?? Str::headline((string) ($type ?: 'Other')),
                    'count' => $group->count(),
                    'delivered' => $delivered,
                    'read' => $group->where('status', 'read')->count(),
                    'failed' => $group->where('status', 'failed')->count(),
                    'delivery_rate' => $group->count() > 0 ? (int) round(($delivered / $group->count()) * 100) : 0,
                ];
            })
            ->sortByDesc('count')
            ->values();

        $statusOrder = ['queued', 'accepted', 'sent', 'delivered', 'read', 'failed'];
        $statusLabels = [
            'queued' => 'Queued',
            'accepted' => 'Accepted',
            'sent' => 'Sent',
            'delivered' => 'Delivered',
            'read' => 'Read',
            'failed' => 'Failed',
        ];
        $statusTones = [
            'queued' => 'bg-surface-400',
            'accepted' => 'bg-blue-500',
            'sent' => 'bg-green-500',
            'delivered' => 'bg-emerald-500',
            'read' => 'bg-teal-500',
            'failed' => 'bg-red-500',
        ];
        $statusStats = collect($statusOrder)->map(fn ($status) => [
            'status' => $status,
            'label' => $statusLabels[$status],
            'count' => $messages->where('status', $status)->count(),
            'tone' => $statusTones[$status],
        ]);

        $dailyActivity = collect(range(6, 0))->map(function ($daysAgo) use ($messages) {
            $date = now()->subDays($daysAgo);
            $dayMessages = $messages->filter(fn ($message) => $message->created_at?->toDateString() === $date->toDateString());

            return [
                'label' => $date->format('D'),
                'date' => $date->format('d M'),
                'count' => $dayMessages->count(),
                'delivered' => $dayMessages->filter(fn ($message) => in_array($message->status, ['delivered', 'read'], true))->count(),
            ];
        });
        $dailyMax = max($dailyActivity->max('count'), 1);

        $recentMessages = $messages->take(20);
        $recentReplies = $replies->take(20);

        return view('events.messages-report', compact(
            'event',
            'messages',
            'stats',
            'channels',
            'typeStats',
            'statusStats',
            'dailyActivity',
            'dailyMax',
            'recentMessages',
            'replies',
            'recentReplies'
        ));
    }
}
