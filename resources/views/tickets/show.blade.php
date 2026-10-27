@extends('layouts.bootstrap')
@section('title', 'Ticket #' . $ticket->id)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>{{ $ticket->title }}</h1>
    <span class="badge bg-info">{{ $ticket->status_label }}</span>
</div>
<div class="card mb-3"><div class="card-body">
    <p>{{ $ticket->description }}</p>
    <p><strong>Catégorie:</strong> {{ $ticket->category?->name }}</p>
    <p><strong>Priorité:</strong> {{ $ticket->priority_label }}</p>
    <p><strong>Demandeur:</strong> {{ $ticket->user?->full_name }}</p>
    <p><strong>Technicien:</strong> {{ $ticket->assignee?->full_name ?? 'Non assigné' }}</p>
    <p><strong>Échéance:</strong> {{ $ticket->deadline?->format('d/m/Y H:i') ?? 'N/A' }}</p>
    @if($ticket->attachment)
        <p><a href="{{ asset('storage/' . $ticket->attachment) }}" target="_blank">Pièce jointe</a></p>
    @endif
</div></div>

@can('assign', $ticket)
@if(in_array($ticket->status, ['new', 'assigned']))
<div class="card mb-3"><div class="card-body">
    <h5>{{ $ticket->assigned_to ? 'Réaffecter le technicien' : 'Affecter un technicien' }}</h5>
    <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="row g-2">
        @csrf
        <div class="col"><select name="technician_id" class="form-select" required>@foreach($technicians as $t)<option value="{{ $t->id }}" @selected($ticket->assigned_to == $t->id)>{{ $t->full_name }}</option>@endforeach</select></div>
        <div class="col"><input type="date" name="deadline" class="form-control" min="{{ now()->toDateString() }}"></div>
        <div class="col-auto"><button class="btn btn-primary">Affecter</button></div>
    </form>
</div></div>
@endif
@endcan

@can('changeStatus', $ticket)
@php
    $next = match($ticket->status) {
        'assigned' => 'in_progress', 'in_progress' => 'resolved', 'resolved' => 'closed', default => null
    };
@endphp
@if($next || $ticket->status === 'new')
<div class="card mb-3"><div class="card-body">
    <h5>Changer le statut</h5>
    @if($next)
        <form method="POST" action="{{ route('tickets.status', $ticket) }}" class="d-flex gap-2">
            @csrf
            <input type="hidden" name="status" value="{{ $next }}">
            <button class="btn btn-success">Passer à « {{ \App\Models\Ticket::STATUS_LABELS[$next] }} »</button>
        </form>
    @else
        <p class="text-muted mb-0">Ce ticket doit d'abord être assigné à un technicien.</p>
    @endif
</div></div>
@endif
@endcan

@php($totalMinutes = $ticket->totalTimeMinutes())
@if($totalMinutes > 0 || auth()->user()->can('logTime', $ticket))
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Temps passé</h5>
        <span class="badge bg-dark">Total : {{ \App\Models\TimeEntry::format($totalMinutes) }}</span>
    </div>
    @if($ticket->timeEntries->isNotEmpty())
        <ul class="list-group mb-3">
            @foreach($ticket->timeEntries as $entry)
                <li class="list-group-item d-flex justify-content-between">
                    <span>
                        <strong>{{ \App\Models\TimeEntry::format($entry->minutes) }}</strong>
                        @if($entry->note) — {{ $entry->note }} @endif
                    </span>
                    <small class="text-muted">{{ $entry->created_at->format('d/m/Y H:i') }} - {{ $entry->user?->full_name }}</small>
                </li>
            @endforeach
        </ul>
    @endif
    @can('logTime', $ticket)
        <form method="POST" action="{{ route('tickets.time-entries.store', $ticket) }}" class="row g-2">
            @csrf
            <div class="col-md-3"><input type="number" name="minutes" class="form-control" min="1" max="1440" placeholder="Minutes" required></div>
            <div class="col"><input name="note" class="form-control" maxlength="255" placeholder="Note (facultatif)"></div>
            <div class="col-auto"><button class="btn btn-primary">Ajouter</button></div>
        </form>
    @endcan
</div></div>
@endif

<div class="row">
    <div class="col-md-8">
        <h4>Commentaires</h4>
        @foreach($ticket->comments as $c)
            <div class="card mb-2"><div class="card-body">
                <strong>{{ $c->user?->full_name }}</strong> <small class="text-muted">{{ $c->created_at->format('d/m/Y H:i') }}</small>
                <p>{{ $c->message }}</p>
                @if($c->attachment)<a href="{{ asset('storage/' . $c->attachment) }}">Fichier</a>@endif
            </div></div>
        @endforeach
        <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-2"><textarea name="message" class="form-control" required></textarea></div>
            <div class="mb-2"><input type="file" name="attachment" class="form-control"></div>
            <button class="btn btn-primary">Commenter</button>
        </form>
    </div>
    <div class="col-md-4">
        <h4>Historique</h4>
        <ul class="list-group">
            @foreach($ticket->activities as $a)
                <li class="list-group-item">
                    <strong>{{ $a->action_label }}</strong><br>
                    <small>{{ $a->description }}</small><br>
                    <small class="text-muted">{{ $a->created_at->format('d/m/Y H:i') }} - {{ $a->user?->full_name }}</small>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
