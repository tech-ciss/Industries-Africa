<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTicketRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private TicketService $tickets) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $query = Ticket::with(['user', 'category', 'assignee'])
            ->visibleTo($request->user())
            ->filter($request->only(['status', 'priority', 'category_id', 'technician_id', 'date_from', 'date_to', 'search']));

        $tickets = $query->latest()->paginate(15)->withQueryString();
        $categories = Category::all();
        $technicians = User::where('role', 'technician')->where('status', 'active')->get();

        return view('tickets.index', compact('tickets', 'categories', 'technicians'));
    }

    public function create()
    {
        $this->authorize('create', Ticket::class);

        $categories = Category::all();

        return view('tickets.create', compact('categories'));
    }

    public function store(StoreTicketRequest $request)
    {
        $this->authorize('create', Ticket::class);

        // La notification aux admins est envoyée par TicketService::create().
        $ticket = $this->tickets->create(
            $request->only(['title', 'description', 'category_id', 'priority']),
            $request->user(),
            $request->file('attachment')
        );

        return redirect()->route('tickets.show', $ticket)->with('success', 'Demande créée.');
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        $ticket->load(['user', 'category', 'assignee', 'comments.user', 'activities.user', 'timeEntries.user']);
        $technicians = User::where('role', 'technician')->where('status', 'active')->get();

        return view('tickets.show', compact('ticket', 'technicians'));
    }

    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        $categories = Category::all();

        return view('tickets.edit', compact('ticket', 'categories'));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $this->tickets->update(
            $ticket,
            $request->only(['title', 'description', 'category_id', 'priority']),
            $request->user(),
            $request->file('attachment')
        );

        return redirect()->route('tickets.show', $ticket)->with('success', 'Demande mise à jour.');
    }

    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);
        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', 'Demande supprimée.');
    }

    // Autorisation (admin) + validation (technicien actif, date) : voir AssignTicketRequest.
    public function assign(AssignTicketRequest $request, Ticket $ticket)
    {
        $technician = User::findOrFail($request->validated('technician_id'));

        $this->tickets->assign($ticket, $technician, $request->user(), $request->date('deadline'));

        return redirect()->route('tickets.show', $ticket)->with('success', 'Technicien assigné.');
    }

    public function changeStatus(Request $request, Ticket $ticket)
    {
        $this->authorize('changeStatus', $ticket);
        $data = $request->validate([
            'status' => ['required', 'in:assigned,in_progress,resolved,closed'],
        ]);

        // Lève une ValidationException si la transition saute une étape.
        $this->tickets->changeStatus($ticket, $data['status'], $request->user());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Statut mis à jour.');
    }
}
