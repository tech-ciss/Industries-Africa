<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // la liste est filtrée par Ticket::visibleTo()
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isTechnician()) {
            return $ticket->assigned_to === $user->id;
        }

        return $ticket->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $ticket->user_id === $user->id && $ticket->status === 'new';
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTechnician() && $ticket->assigned_to === $user->id;
    }

    /** Seul le technicien assigné enregistre du temps, une fois le travail commencé. */
    public function logTime(User $user, Ticket $ticket): bool
    {
        return $user->isTechnician()
            && $ticket->assigned_to === $user->id
            && in_array($ticket->status, ['in_progress', 'resolved'], true);
    }

    /** Commenter = avoir accès au ticket. */
    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
