<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    public const ACTION_LABELS = [
        'created' => 'Création',
        'updated' => 'Modification',
        'status_changed' => 'Changement de statut',
        'assigned' => 'Assignation technicien',
        'commented' => 'Commentaire',
        'time_logged' => 'Temps enregistré',
    ];

    protected $fillable = ['ticket_id', 'user_id', 'action', 'description'];

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
