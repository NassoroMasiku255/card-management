<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'template_id',
        'name',
        'language',
        'category',
        'status',
        'header_type',
        'body_text',
        'components',
        'synced_at',
    ];

    protected $casts = [
        'components' => 'array',
        'synced_at' => 'datetime',
    ];

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'APPROVED');
    }

    public function isApproved(): bool
    {
        return strtoupper((string) $this->status) === 'APPROVED';
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->language})";
    }
}
