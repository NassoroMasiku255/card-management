<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
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
