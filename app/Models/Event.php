<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $fillable = [
        'user_id', 'name', 'slug', 'event_date', 'event_time', 'location',
        'hall', 'description', 'card_template', 'card_background_color',
        'card_text_color', 'card_accent_color', 'cover_image', 'status',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->name) . '-' . Str::random(6);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
