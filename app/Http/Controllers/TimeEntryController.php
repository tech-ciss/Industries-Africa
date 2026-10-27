<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimeEntryRequest;
use App\Models\Ticket;
use App\Services\TicketService;

class TimeEntryController extends Controller
{
    public function __construct(private TicketService $tickets) {}

    // Autorisation (technicien assigné, ticket en cours/résolu) : voir StoreTimeEntryRequest.
    public function store(StoreTimeEntryRequest $request, Ticket $ticket)
    {
        $this->tickets->logTime(
            $ticket,
            $request->user(),
            (int) $request->validated('minutes'),
            $request->validated('note')
        );

        return redirect()->route('tickets.show', $ticket)->with('success', 'Temps enregistré.');
    }
}
