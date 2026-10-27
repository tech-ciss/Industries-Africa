<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    public const STATUS_LABELS = [
        'new' => 'Nouveau',
        'assigned' => 'Assigné',
        'in_progress' => 'En cours',
        'resolved' => 'Résolu',
        'closed' => 'Fermé',
    ];

    public const PRIORITY_LABELS = [
        'low' => 'Faible',
        'normal' => 'Normale',
        'high' => 'Haute',
        'critical' => 'Critique',
    ];

    protected $fillable = [
        'title', 'description', 'priority', 'status', 'user_id',
        'category_id', 'assigned_to', 'deadline', 'attachment', 'resolved_at',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Limite la requête aux tickets que l'utilisateur a le droit de voir :
     * admin = tous, technicien = ses affectations, employé = ses demandes.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->isAdmin() => $query,
            $user->isTechnician() => $query->where('assigned_to', $user->id),
            default => $query->where('user_id', $user->id),
        };
    }

    /**
     * Filtres communs à la liste des tickets et aux exports :
     * status, priority, category_id, technician_id, date_from, date_to, search.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['priority'] ?? null, fn ($q, $v) => $q->where('priority', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['technician_id'] ?? null, fn ($q, $v) => $q->where('assigned_to', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('title', 'like', '%'.$v.'%'));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? $this->priority;
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /** Total des minutes enregistrées par les techniciens. */
    public function totalTimeMinutes(): int
    {
        return (int) ($this->relationLoaded('timeEntries')
            ? $this->timeEntries->sum('minutes')
            : $this->timeEntries()->sum('minutes'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function resolutionTimeInHours(): ?float
    {
        if (! $this->resolved_at) {
            return null;
        }
        return $this->created_at->diffInMinutes($this->resolved_at) / 60;
    }
}
