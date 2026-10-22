<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Guest extends Model
{
    protected $fillable = [
        'event_id', 'unique_id', 'full_name', 'phone_number',
        'amount_contributed', 'paid_amount', 'last_payment_at', 'card_type',
        'email', 'table_number', 'category', 'notes',
    ];

    protected $casts = [
        'amount_contributed' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'last_payment_at' => 'datetime',
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
            $uniqueId = 'G'.$eventId.'-'.strtoupper(Str::random(8));
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

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    public function latestWhatsAppMessage(): HasOne
    {
        return $this->hasOne(WhatsAppMessage::class)->latestOfMany('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(GuestPayment::class);
    }

    public function getFormattedPhoneAttribute(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->phone_number ?? '');

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '0')) {
            $phone = '255'.substr($phone, 1);
        } elseif (! str_starts_with($phone, '255')) {
            $phone = '255'.$phone;
        }

        return $phone;
    }

    public function getPledgedAmountAttribute(): float
    {
        return (float) $this->amount_contributed;
    }

    public function getBalanceAttribute(): float
    {
        return $this->pledged_amount - (float) $this->paid_amount;
    }

    public function getPaymentStatusAttribute(): string
    {
        $pledged = $this->pledged_amount;
        $paid = (float) $this->paid_amount;

        if ($paid <= 0) {
            return 'unpaid';
        }

        if ($pledged <= 0) {
            return 'paid';
        }

        if ($paid > $pledged) {
            return 'overpaid';
        }

        if ($paid >= $pledged) {
            return 'paid';
        }

        return 'partial';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'Paid',
            'partial' => 'Partial',
            'overpaid' => 'Overpaid',
            default => 'Unpaid',
        };
    }

    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'bg-green-100 text-green-700',
            'partial' => 'bg-amber-100 text-amber-700',
            'overpaid' => 'bg-blue-100 text-blue-700',
            default => 'bg-surface-100 text-surface-600',
        };
    }

    public function getEstimatedAttendeesAttribute(): int
    {
        return $this->card_type === 'double' ? 2 : 1;
    }

    public function getCollectionRateAttribute(): int
    {
        $pledged = $this->pledged_amount;
        $paid = (float) $this->paid_amount;

        if ($pledged <= 0) {
            return $paid > 0 ? 100 : 0;
        }

        return (int) min(100, round(($paid / $pledged) * 100));
    }
}
