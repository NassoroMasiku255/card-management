<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMessageCredit extends Model
{
    public const TYPE_TOPUP = 'topup';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_USAGE = 'usage';

    protected $fillable = [
        'event_id', 'user_id', 'channel', 'type', 'amount',
        'balance_after', 'message_type', 'note',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getChannelLabelAttribute(): string
    {
        return Event::MESSAGE_CHANNELS[$this->channel] ?? ucfirst((string) $this->channel);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_TOPUP => 'Top-up',
            self::TYPE_ADJUSTMENT => 'Correction',
            self::TYPE_USAGE => 'Message sent',
            default => ucfirst((string) $this->type),
        };
    }
}
