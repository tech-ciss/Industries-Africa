<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private TicketService $tickets) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        return Ticket::with(['user:id,name,first_name', 'category', 'assignee:id,name,first_name'])
            ->visibleTo($request->user())
            ->latest()
            ->paginate(15);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Ticket::class);

        $data = $request->validate([
            'title' => 'required|min:5|max:255',
            'description' => 'required|min:20',
            'category_id' => 'required|exists:categories,id',
            'priority' => 'required|in:low,normal,high,critical',
        ]);

        // Passe par le service : statut "new", historique et notification aux admins.
        $ticket = $this->tickets->create($data, $request->user());

        return response()->json($ticket, 201);
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        return $ticket->load(['user:id,name,first_name', 'category', 'assignee:id,name,first_name', 'comments']);
    }

    /** Modification du contenu uniquement : le statut passe par PATCH /tickets/{id}/status. */
    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $data = $request->validate([
            'title' => 'sometimes|required|min:5|max:255',
            'description' => 'sometimes|required|min:20',
            'category_id' => 'sometimes|exists:categories,id',
            'priority' => 'sometimes|in:low,normal,high,critical',
        ]);
        // Passe par le service : seuls les champs réellement modifiés sont journalisés.
        return $this->tickets->update($ticket, $data, $request->user());
    }

    /** Changement de statut : même workflow et mêmes droits que l'interface web. */
    public function changeStatus(Request $request, Ticket $ticket)
    {
        $this->authorize('changeStatus', $ticket);

        $data = $request->validate([
            'status' => ['required', 'in:assigned,in_progress,resolved,closed'],
        ]);

        return $this->tickets->changeStatus($ticket, $data['status'], $request->user());
    }

    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);
        $ticket->delete();

        return response()->noContent();
    }
}
