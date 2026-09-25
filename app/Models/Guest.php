<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Guest extends Model
{
    protected $fillable = [
        'event_id', 'unique_id', 'full_name', 'phone_number',
        'amount_contributed', 'card_type', 'email', 'table_number',
        'category', 'notes',
    ];

    protected $casts = [
        'amount_contributed' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($guest) {
            if (empty($guest->unique_id)) {
                $guest->unique_id = self::generateUniqueId($guest->event_id);
            }
        });
    }

    public static function generateUniqueId(int $eventId): string
    {
        do {
            $uniqueId = 'G' . $eventId . '-' . strtoupper(Str::random(8));
        } while (self::where('unique_id', $uniqueId)->exists());

        return $uniqueId;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function invitation(): HasOne
    {
        return $this->hasOne(Invitation::class);
    }

    public function getFormattedPhoneAttribute(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->phone_number);
        if (str_starts_with($phone, '0')) {
            $phone = '255' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '255')) {
            $phone = '255' . $phone;
        }
        return $phone;
    }
}
