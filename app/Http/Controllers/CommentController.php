<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Ticket;
use App\Services\ActivityService;

class CommentController extends Controller
{
    public function __construct(private ActivityService $activity) {}

    // L'autorisation ('comment' sur le ticket) est dans StoreCommentRequest.
    public function store(StoreCommentRequest $request, Ticket $ticket)
    {
        $data = $request->only(['message']);
        $data['ticket_id'] = $ticket->id;
        $data['user_id'] = $request->user()->id;
        if ($request->file('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('comments', 'public');
        }
        Comment::create($data);
        $this->activity->log($ticket, $request->user(), 'commented', 'Commentaire ajouté.');

        return redirect()->route('tickets.show', $ticket)->with('success', 'Commentaire ajouté.');
    }

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);
        $comment->delete();

        return back()->with('success', 'Commentaire supprimé.');
    }
}
