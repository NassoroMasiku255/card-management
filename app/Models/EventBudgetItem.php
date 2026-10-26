<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventBudgetItem extends Model
{
    public const CATEGORIES = [
        'Venue',
        'Catering',
        'MC',
        'Photography',
        'Videography',
        'Decoration',
        'Music & DJ',
        'Attire',
        'Transport',
        'Cake',
        'Stationery',
        'Security',
        'Other',
    ];

    protected $fillable = [
        'event_id', 'category', 'service_name', 'vendor_name',
        'vendor_contact_name', 'vendor_phone', 'vendor_email',
        'total_price', 'paid_amount', 'due_date', 'notes', 'sort_order',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'sort_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->total_price - (float) $this->paid_amount);
    }

    public function getStatusAttribute(): string
    {
        $total = (float) $this->total_price;
        $paid = (float) $this->paid_amount;

        if ($paid <= 0) {
            return 'unpaid';
        }

        if ($paid > $total && $total > 0) {
            return 'overpaid';
        }

        if ($paid >= $total) {
            return 'paid';
        }

        return 'partial';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'Paid',
            'partial' => 'Partial',
            'overpaid' => 'Overpaid',
            default => 'Unpaid',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'bg-green-100 text-green-700',
            'partial' => 'bg-amber-100 text-amber-700',
            'overpaid' => 'bg-blue-100 text-blue-700',
            default => 'bg-surface-100 text-surface-600',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date !== null
            && $this->due_date->lt(today())
            && $this->balance > 0;
    }

    public function getCategoryLabelAttribute(): string
    {
        return trim((string) ($this->category ?: 'Other'));
    }

    public function getFormattedVendorPhoneAttribute(): ?string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $this->vendor_phone);

        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '0')) {
            return '255'.substr($phone, 1);
        }

        if (! str_starts_with($phone, '255')) {
            return '255'.$phone;
        }

        return $phone;
    }
}
