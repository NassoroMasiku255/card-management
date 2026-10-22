<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'event_id', 'guest_id', 'invitation_id',
        'message_type', 'to_phone', 'template_name',
        'language_code', 'language_policy',
        'whatsapp_message_id', 'status',
        'payload', 'response', 'status_payload', 'error',
        'error_code', 'error_title', 'error_message',
        'status_updated_at', 'sent_at', 'delivered_at', 'read_at', 'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'status_payload' => 'array',
        'error' => 'array',
        'status_updated_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public const STATUS_QUEUED = 'queued';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READ = 'read';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RANK = [
        'queued' => 0,
        'accepted' => 1,
        'sent' => 2,
        'delivered' => 3,
        'read' => 4,
        'failed' => 99,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'queued' => 'Queued',
            'accepted' => 'Accepted',
            'sent' => 'Sent',
            'delivered' => 'Delivered',
            'read' => 'Read',
            'failed' => 'Failed',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'queued' => 'bg-surface-100 text-surface-600',
            'accepted' => 'bg-blue-100 text-blue-700',
            'sent' => 'bg-green-100 text-green-700',
            'delivered' => 'bg-emerald-100 text-emerald-700',
            'read' => 'bg-teal-100 text-teal-700',
            'failed' => 'bg-red-100 text-red-700',
            default => 'bg-surface-100 text-surface-600',
        };
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public static function rank(?string $status): int
    {
        return self::STATUS_RANK[$status] ?? -1;
    }

    public function applyStatus(string $status, array $payload = []): void
    {
        $timestamp = match ($status) {
            'sent' => 'sent_at',
            'delivered' => 'delivered_at',
            'read' => 'read_at',
            'failed' => 'failed_at',
            default => null,
        };

        $attributes = [
            'status' => $status,
            'status_payload' => $payload,
            'status_updated_at' => now(),
        ];

        if ($timestamp && ! $this->{$timestamp}) {
            $attributes[$timestamp] = now();
        }

        if ($status === 'failed') {
            $error = $payload['errors'][0] ?? [];

            $attributes['error'] = $payload['errors'] ?? [];
            $attributes['error_code'] = isset($error['code']) ? (string) $error['code'] : null;
            $attributes['error_title'] = $error['title'] ?? null;
            $attributes['error_message'] = $error['message']
                ?? $error['error_data']['details']
                ?? null;
        }

        $this->update($attributes);
    }
}
