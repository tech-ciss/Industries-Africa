<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Ticket;
use App\Models\User;

class ActivityService
{
    public function log(Ticket $ticket, User $user, string $action, ?string $description = null): Activity
    {
        return Activity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
