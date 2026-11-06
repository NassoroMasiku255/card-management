<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPaymentMethod extends Model
{
    protected $fillable = ['event_id', 'payment_name', 'payment_number'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}