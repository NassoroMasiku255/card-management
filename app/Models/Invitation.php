<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    protected $fillable = [
        'event_id', 'guest_id', 'qr_code_data', 'qr_code_path',
        'send_status', 'sent_at', 'rsvp_status', 'rsvp_responded_at',
        'attendance_status', 'checked_in_at', 'whatsapp_message_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'rsvp_responded_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function markAsSent(?string $messageId = null): void
    {
        $this->update([
            'send_status' => 'sent',
            'sent_at' => now(),
            'whatsapp_message_id' => $messageId,
        ]);
    }

    public function markRsvp(string $status): void
    {
        $this->update([
            'rsvp_status' => $status,
            'rsvp_responded_at' => now(),
        ]);
    }

    public function markAttended(): void
    {
        $this->update([
            'attendance_status' => 'attended',
            'checked_in_at' => now(),
        ]);
    }
}
