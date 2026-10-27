<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'first_name', 'email', 'password', 'phone', 'role', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, \Laravel\Sanctum\HasApiTokens;

    public const ROLE_LABELS = [
        'admin' => 'Administrateur',
        'employee' => 'Employé',
        'technician' => 'Technicien',
    ];

    public const STATUS_LABELS = [
        'active' => 'Actif',
        'inactive' => 'Inactif',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** "Prénom Nom" ("name" contient le nom de famille). */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->name));
    }

    protected function initials(): Attribute
    {
        return Attribute::get(fn () => mb_strtoupper(
            mb_substr((string) $this->first_name, 0, 1).mb_substr((string) $this->name, 0, 1)
        ));
    }

    protected function roleLabel(): Attribute
    {
        return Attribute::get(fn () => self::ROLE_LABELS[$this->role] ?? $this->role);
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn () => self::STATUS_LABELS[$this->status] ?? $this->status);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
