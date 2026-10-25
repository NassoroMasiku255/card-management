<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    public const REPLY_EVENT_TYPES = [
        'message_reply',
        'interactive_reply',
        'button_reply',
    ];

    protected $fillable = [
        'event_id',
        'guest_id',
        'direction',
        'method',
        'event_type',
        'status_code',
        'source_phone',
        'whatsapp_message_id',
        'summary',
        'headers',
        'payload',
        'parsed_data',
        'processed',
        'error',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'parsed_data' => 'array',
        'processed' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function getReplyTextAttribute(): ?string
    {
        if (! in_array($this->event_type, self::REPLY_EVENT_TYPES, true)) {
            return null;
        }

        $data = $this->parsed_data ?? [];

        return match ($this->event_type) {
            'message_reply' => $data['text']['body'] ?? $this->summary,
            'interactive_reply' => $data['interactive']['button_reply']['title']
                ?? $data['interactive']['list_reply']['title']
                ?? $this->summary,
            'button_reply' => $data['button']['text'] ?? $this->summary,
            default => $this->summary,
        };
    }

    public function getReplyKindAttribute(): ?string
    {
        return match ($this->event_type) {
            'message_reply' => 'Text',
            'interactive_reply' => 'Quick reply',
            'button_reply' => 'Button',
            default => null,
        };
    }

    /**
     * Create a log entry from an incoming webhook request.
     */
    public static function logIncoming(
        string $method,
        ?array $payload,
        ?array $headers = null,
    ): self {
        return static::create([
            'direction' => 'incoming',
            'method' => $method,
            'headers' => $headers,
            'payload' => $payload,
        ]);
    }

    /**
     * Badge colour for the event type.
     */
    public function getTypeBadgeColorAttribute(): string
    {
        return match ($this->event_type) {
            'message_status' => 'blue',
            'message_reply', 'interactive_reply', 'button_reply' => 'green',
            'rsvp' => 'purple',
            'verification' => 'amber',
            'error' => 'red',
            default => 'gray',
        };
    }
}
