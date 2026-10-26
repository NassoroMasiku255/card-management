<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    public const DEFAULT_SINGLE_CARD_MINIMUM = 70000;

    public const DEFAULT_DOUBLE_CARD_MINIMUM = 100000;

    protected $fillable = [
        'user_id', 'name', 'slug', 'event_date', 'event_time', 'location',
        'hall', 'description', 'card_template', 'card_background_color',
        'card_text_color', 'card_accent_color', 'single_card_min_amount',
        'double_card_min_amount', 'payment_thank_you_enabled', 'estimated_guests',
        'cover_image', 'status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'single_card_min_amount' => 'decimal:2',
        'double_card_min_amount' => 'decimal:2',
        'payment_thank_you_enabled' => 'boolean',
        'estimated_guests' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->name).'-'.Str::random(6);
            }
        });

        static::created(function ($event) {
            if ($event->user_id) {
                $event->users()->syncWithoutDetaching([$event->user_id]);
            }
        });

        static::updated(function ($event) {
            if ($event->wasChanged('user_id') && $event->user_id) {
                $event->users()->syncWithoutDetaching([$event->user_id]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    public function guestPayments(): HasMany
    {
        return $this->hasMany(GuestPayment::class);
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(EventBudgetItem::class);
    }

    public function getEventTimeAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    public function getAttendingCountAttribute(): int
    {
        return $this->invitations()->where('rsvp_status', 'attending')->count();
    }

    public function getNotAttendingCountAttribute(): int
    {
        return $this->invitations()->where('rsvp_status', 'not_attending')->count();
    }

    public function getPendingRsvpCountAttribute(): int
    {
        return $this->invitations()->where('rsvp_status', 'pending')->count();
    }

    public function getCheckedInCountAttribute(): int
    {
        return $this->invitations()->where('attendance_status', 'attended')->count();
    }

    public function getSentInvitationsCountAttribute(): int
    {
        return $this->invitations()->where('send_status', 'sent')->count();
    }

    public function getTotalGuestsAttribute(): int
    {
        return $this->guests()->count();
    }

    public function getSingleCardMinimumAttribute(): float
    {
        $amount = (float) ($this->single_card_min_amount ?? 0);

        return $amount > 0 ? $amount : self::DEFAULT_SINGLE_CARD_MINIMUM;
    }

    public function getDoubleCardMinimumAttribute(): float
    {
        $amount = (float) ($this->double_card_min_amount ?? 0);

        return $amount > 0 ? $amount : self::DEFAULT_DOUBLE_CARD_MINIMUM;
    }

    public function cardTypeForAmount(float $amount): string
    {
        return $amount >= $this->double_card_minimum ? 'double' : 'single';
    }

    public function paidAmountQualifiesForInvitation(float $paidAmount): bool
    {
        return $paidAmount > 0 && $paidAmount >= $this->single_card_minimum;
    }
}
