<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'can_send_whatsapp',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'can_send_whatsapp' => 'boolean',
        ];
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    public function ownedEvents(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function canManageEvent(Event $event): bool
    {
        return $this->isAdmin()
            || $event->user_id === $this->id
            || $event->users()->whereKey($this->id)->exists();
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function canSendWhatsApp(): bool
    {
        return $this->isAdmin() || (bool) $this->can_send_whatsapp;
    }
}
