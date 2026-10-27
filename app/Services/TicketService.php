<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketResolvedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TicketService
{
    /**
     * Transitions manuelles autorisées. Nouveau -> Assigné n'est PAS ici :
     * il ne se fait que par l'affectation d'un technicien (assign()).
     */
    private const TRANSITIONS = [
        'assigned' => 'in_progress',
        'in_progress' => 'resolved',
        'resolved' => 'closed',
    ];

    /** Libellés des champs modifiables, pour l'historique. */
    private const FIELD_LABELS = [
        'title' => 'titre',
        'description' => 'description',
        'category_id' => 'catégorie',
        'priority' => 'priorité',
        'attachment' => 'pièce jointe',
    ];

    public function __construct(private ActivityService $activity) {}

    public function create(array $data, User $user, ?UploadedFile $attachment = null): Ticket
    {
        if ($attachment) {
            $data['attachment'] = $attachment->store('tickets', 'public');
        }
        $data['user_id'] = $user->id;
        $data['status'] = 'new';

        $ticket = DB::transaction(function () use ($data, $user) {
            $ticket = Ticket::create($data);
            $this->activity->log($ticket, $user, 'created', 'Ticket créé.');

            return $ticket;
        });

        $admins = User::where('role', 'admin')->where('status', 'active')->get();
        Notification::send($admins, new TicketCreatedNotification($ticket));

        return $ticket;
    }

    /** Modifie le contenu d'un ticket et journalise les champs réellement changés. */
    public function update(Ticket $ticket, array $data, User $user, ?UploadedFile $attachment = null): Ticket
    {
        $oldAttachment = $ticket->attachment;
        if ($attachment) {
            $data['attachment'] = $attachment->store('tickets', 'public');
        }

        $ticket->fill($data);
        $changed = array_keys($ticket->getDirty());

        if ($changed === []) {
            return $ticket; // rien n'a changé : pas d'entrée d'historique
        }

        DB::transaction(function () use ($ticket, $changed, $user) {
            $ticket->save();

            $fields = array_map(fn ($f) => self::FIELD_LABELS[$f] ?? $f, $changed);
            $this->activity->log($ticket, $user, 'updated', 'Modification : '.implode(', ', $fields).'.');
        });

        if ($attachment && $oldAttachment) {
            Storage::disk('public')->delete($oldAttachment);
        }

        return $ticket;
    }

    public function assign(Ticket $ticket, User $technician, User $admin, ?\DateTimeInterface $deadline = null): Ticket
    {
        if (! in_array($ticket->status, ['new', 'assigned'], true)) {
            throw ValidationException::withMessages([
                'technician_id' => 'Un ticket en cours, résolu ou fermé ne peut plus être réaffecté.',
            ]);
        }
        if (! $technician->isTechnician() || ! $technician->isActive()) {
            throw ValidationException::withMessages([
                'technician_id' => 'Le technicien sélectionné est invalide ou inactif.',
            ]);
        }

        $reassigned = $ticket->assigned_to !== null;

        DB::transaction(function () use ($ticket, $technician, $admin, $deadline, $reassigned) {
            $ticket->update([
                'assigned_to' => $technician->id,
                'deadline' => $deadline,
                'status' => 'assigned',
            ]);

            $verb = $reassigned ? 'Réassigné' : 'Assigné';
            $until = $deadline ? ' (échéance : '.$deadline->format('d/m/Y').')' : '';
            $this->activity->log($ticket, $admin, 'assigned', "{$verb} à {$technician->full_name}{$until}.");
        });

        $technician->notify(new TicketAssignedNotification($ticket));

        return $ticket;
    }

    public function changeStatus(Ticket $ticket, string $status, User $user): Ticket
    {
        $expected = self::TRANSITIONS[$ticket->status] ?? null;

        if ($expected === null || $expected !== $status) {
            $hint = $ticket->status === 'new'
                ? ' Affectez d\'abord un technicien.'
                : '';

            throw ValidationException::withMessages([
                'status' => 'Transition de statut non autorisée : le workflow doit être suivi étape par étape.'.$hint,
            ]);
        }
        if ($ticket->assigned_to === null) {
            throw ValidationException::withMessages([
                'status' => 'Ce ticket n\'a pas de technicien assigné.',
            ]);
        }

        $from = $ticket->status;

        DB::transaction(function () use ($ticket, $status, $user, $from) {
            $updates = ['status' => $status];
            if ($status === 'resolved') {
                $updates['resolved_at'] = now();
            }
            $ticket->update($updates);
            $this->activity->log(
                $ticket,
                $user,
                'status_changed',
                'Statut changé : '.Ticket::STATUS_LABELS[$from].' → '.Ticket::STATUS_LABELS[$status].'.'
            );
        });

        if ($status === 'resolved') {
            $ticket->user?->notify(new TicketResolvedNotification($ticket));
        }

        return $ticket;
    }

    /** Enregistre du temps passé (en minutes) par un technicien sur un ticket. */
    public function logTime(Ticket $ticket, User $technician, int $minutes, ?string $note = null): TimeEntry
    {
        return DB::transaction(function () use ($ticket, $technician, $minutes, $note) {
            $entry = TimeEntry::create([
                'ticket_id' => $ticket->id,
                'user_id' => $technician->id,
                'minutes' => $minutes,
                'note' => $note,
            ]);

            $this->activity->log(
                $ticket,
                $technician,
                'time_logged',
                TimeEntry::format($minutes).' enregistrées.'
            );

            return $entry;
        });
    }
}
