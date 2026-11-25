<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    protected $fillable = [
        'event_id', 'guest_id', 'invitation_id', 'message_type', 'to_phone',
        'from_name', 'text', 'reference', 'provider_message_id', 'status',
        'response', 'error_message', 'status_updated_at', 'sent_at',
    ];

    protected $casts = [
        'response' => 'array',
        'status_updated_at' => 'datetime',
        'sent_at' => 'datetime',
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
        return ucfirst((string) $this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'sent', 'delivered' => 'bg-green-100 text-green-700',
            'failed' => 'bg-red-100 text-red-700',
            default => 'bg-surface-100 text-surface-600',
        };
    }

    public function getSimpleStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'Sent',
            'delivered' => 'Delivered',
            'read' => 'Read',
            default => 'Pending',
        };
    }

    public function getSimpleStatusBadgeClassAttribute(): string
    {
        return match ($this->simple_status_label) {
            'Sent' => 'bg-green-100 text-green-700',
            'Delivered' => 'bg-emerald-100 text-emerald-700',
            'Read' => 'bg-teal-100 text-teal-700',
            default => 'bg-amber-100 text-amber-700',
        };
    }
}