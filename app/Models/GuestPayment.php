<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestPayment extends Model
{
    protected $fillable = [
        'event_id', 'guest_id', 'user_id', 'amount',
        'method', 'reference', 'notes', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'cash' => 'Cash',
            'mobile_money' => 'Mobile Money',
            'bank' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'other' => 'Other',
            'import' => 'Imported',
            default => ucfirst((string) $this->method),
        };
    }
}
