@extends('layouts.bootstrap')
@section('title', 'Ticket #'.$ticket->id)
@section('content')
@php
    $next = match ($ticket->status) {
        'assigned' => ['status' => 'in_progress', 'label' => 'Démarrer l\'intervention'],
        'in_progress' => ['status' => 'resolved', 'label' => 'Marquer comme résolu'],
        'resolved' => ['status' => 'closed', 'label' => 'Fermer le ticket'],
        default => null,
    };
    $totalMinutes = $ticket->totalTimeMinutes();
    $canAssign = auth()->user()->can('assign', $ticket) && in_array($ticket->status, ['new', 'assigned']);
    $canChangeStatus = auth()->user()->can('changeStatus', $ticket) && ($next || $ticket->status === 'new');
@endphp

<div class="page-head">
    <div style="min-width: 0">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <a href="{{ route('tickets.index') }}" class="small"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Tickets</a>
            <span class="ticket-no">#{{ $ticket->id }}</span>
            <x-ui.status :status="$ticket->status" />
            <x-ui.priority :priority="$ticket->priority" />
            @if($ticket->isOverdue())<span class="chip-late"><i class="bi bi-clock-history" aria-hidden="true"></i>En retard</span>@endif
        </div>
        <h1>{{ $ticket->title }}</h1>
    </div>
    <div class="page-actions">
        @can('update', $ticket)
            <a class="btn btn-outline-secondary" href="{{ route('tickets.edit', $ticket) }}"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Modifier</a>
        @endcan
        @can('delete', $ticket)
            <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" data-confirm="Supprimer définitivement ce ticket, ses commentaires et son historique ?">
                @csrf @method('DELETE')
                <button class="btn btn-ghost-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i>Supprimer</button>
            </form>
        @endcan
    </div>
</div>

<section class="panel mb-4" aria-label="Avancement"><div class="panel-body pb-3"><x-ui.rail :ticket="$ticket" /></div></section>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="stack">
            <section class="panel" aria-labelledby="desc-title">
                <div class="panel-head"><h2 id="desc-title">Description</h2></div>
                <div class="panel-body">
                    <p class="description mb-0">{{ $ticket->description }}</p>
                    @if($ticket->attachment)
                        <p class="mt-3 mb-0"><a class="attachment" href="{{ asset('storage/'.$ticket->attachment) }}" target="_blank" rel="noopener">
                            <i class="bi bi-paperclip" aria-hidden="true"></i>{{ basename($ticket->attachment) }}
                        </a></p>
                    @endif
                </div>
            </section>

            @if($totalMinutes > 0 || auth()->user()->can('logTime', $ticket))
                <section class="panel" aria-labelledby="time-title">
                    <div class="panel-head">
                        <h2 id="time-title">Temps passé</h2>
                        <span class="fw-semibold tabular">Total : {{ \App\Models\TimeEntry::format($totalMinutes) }}</span>
                    </div>
                    <div class="panel-body">
                        @if($ticket->timeEntries->isNotEmpty())
                            <ul class="time-list">
                                @foreach($ticket->timeEntries as $entry)
                                    <li>
                                        <span><strong class="tabular">{{ \App\Models\TimeEntry::format($entry->minutes) }}</strong>@if($entry->note) <span class="text-soft">{{ $entry->note }}</span>@endif</span>
                                        <small class="text-soft">{{ $entry->user?->full_name }}, <x-ui.when :date="$entry->created_at" /></small>
                                    </li>
                                @endforeach
                            </ul>
                        @elseif(auth()->user()->can('logTime', $ticket))
                            <p class="text-soft">Aucun temps enregistré pour le moment.</p>
                        @endif

                        @can('logTime', $ticket)
                            <form method="POST" action="{{ route('tickets.time-entries.store', $ticket) }}" class="row g-2 align-items-start">
                                @csrf
                                <div class="col-sm-4">
                                    <label class="form-label" for="minutes">Durée en minutes</label>
                                    <input id="minutes" type="number" name="minutes" class="form-control @error('minutes') is-invalid @enderror" min="1" max="1440" value="{{ old('minutes') }}" required>
                                    @error('minutes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-sm-5">
                                    <label class="form-label" for="note">Note (facultatif)</label>
                                    <input id="note" name="note" class="form-control @error('note') is-invalid @enderror" maxlength="255" value="{{ old('note') }}">
                                    @error('note')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-sm-3 d-flex align-items-end" style="min-height: 62px"><button class="btn btn-primary w-100">Enregistrer</button></div>
                            </form>
                        @endcan
                    </div>
                </section>
            @endif

            <section class="panel" aria-labelledby="chat-title">
                <div class="panel-head"><h2 id="chat-title">Discussion</h2><span class="text-soft">{{ $ticket->comments->count() }} {{ $ticket->comments->count() > 1 ? 'messages' : 'message' }}</span></div>
                <div class="panel-body">
                    @if($ticket->comments->isNotEmpty())
                        <div class="thread mb-4">
                            @foreach($ticket->comments as $c)
                                <article>
                                    @if($c->user)<x-ui.avatar :user="$c->user" />@endif
                                    <div class="bubble">
                                        <div class="who">
                                            <strong>{{ $c->user?->full_name ?? 'Utilisateur supprimé' }}</strong>
                                            <time datetime="{{ $c->created_at->toIso8601String() }}" title="{{ $c->created_at->format('d/m/Y à H:i') }}">{{ $c->created_at->format('d/m/Y H:i') }}</time>
                                            @can('delete', $c)
                                                <form method="POST" action="{{ route('comments.destroy', $c) }}" class="ms-auto" data-confirm="Supprimer ce commentaire ?">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-ghost-danger btn-sm" aria-label="Supprimer ce commentaire"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                                </form>
                                            @endcan
                                        </div>
                                        <p>{{ $c->message }}</p>
                                        @if($c->attachment)
                                            <a class="attachment mt-2" href="{{ asset('storage/'.$c->attachment) }}" target="_blank" rel="noopener"><i class="bi bi-paperclip" aria-hidden="true"></i>{{ basename($c->attachment) }}</a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" enctype="multipart/form-data">
                        @csrf
                        <x-ui.textarea name="message" label="Votre message" rows="3" required minlength="3" />
                        <x-ui.input name="attachment" label="Pièce jointe (facultatif)" type="file" />
                        <button class="btn btn-primary">Publier le commentaire</button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="stack">
            @if($canAssign)
                <section class="panel next-action" aria-labelledby="assign-title">
                    <div class="panel-head"><h2 id="assign-title">{{ $ticket->assigned_to ? 'Réaffecter le technicien' : 'Affecter un technicien' }}</h2></div>
                    <div class="panel-body">
                        <form method="POST" action="{{ route('tickets.assign', $ticket) }}">
                            @csrf
                            <x-ui.select name="technician_id" label="Technicien" required>
                                <option value="" disabled @selected(! old('technician_id', $ticket->assigned_to))>Choisir un technicien</option>
                                @foreach($technicians as $t)
                                    <option value="{{ $t->id }}" @selected(old('technician_id', $ticket->assigned_to) == $t->id)>{{ $t->full_name }}</option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.input name="deadline" label="Date limite" type="date" :value="$ticket->deadline?->format('Y-m-d')" :min="now()->toDateString()" hint="Facultative. Le technicien est prévenu par email." />
                            <button class="btn btn-primary w-100">{{ $ticket->assigned_to ? 'Réaffecter' : 'Affecter' }}</button>
                        </form>
                    </div>
                </section>
            @endif

            @if($canChangeStatus)
                <section class="panel next-action" aria-labelledby="status-title">
                    <div class="panel-head"><h2 id="status-title">Prochaine étape</h2></div>
                    <div class="panel-body">
                        @if($next)
                            <form method="POST" action="{{ route('tickets.status', $ticket) }}">
                                @csrf
                                <input type="hidden" name="status" value="{{ $next['status'] }}">
                                <button class="btn btn-primary w-100">{{ $next['label'] }}</button>
                                <p class="form-hint mb-0">Le ticket passera au statut « {{ \App\Models\Ticket::STATUS_LABELS[$next['status']] }} ».</p>
                            </form>
                            @error('status')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror
                        @else
                            <p class="mb-0 text-soft">Ce ticket doit d'abord être assigné à un technicien.</p>
                        @endif
                    </div>
                </section>
            @endif

            <section class="panel" aria-labelledby="facts-title">
                <div class="panel-head"><h2 id="facts-title">Détails</h2></div>
                <div class="panel-body py-2">
                    <dl class="facts">
                        <div><dt>Catégorie</dt><dd>{{ $ticket->category?->name }}</dd></div>
                        <div><dt>Demandeur</dt><dd><x-ui.person :user="$ticket->user" /></dd></div>
                        <div><dt>Technicien</dt><dd><x-ui.person :user="$ticket->assignee" /></dd></div>
                        <div><dt>Échéance</dt><dd>{{ $ticket->deadline ? $ticket->deadline->format('d/m/Y') : 'Non définie' }}@if($ticket->isOverdue()) <span class="chip-late d-block"><i class="bi bi-clock-history" aria-hidden="true"></i>Échéance dépassée</span>@endif</dd></div>
                        <div><dt>Créé</dt><dd>{{ $ticket->created_at->format('d/m/Y à H:i') }}</dd></div>
                        @if($ticket->resolved_at)<div><dt>Résolu</dt><dd>{{ $ticket->resolved_at->format('d/m/Y à H:i') }}</dd></div>@endif
                    </dl>
                </div>
            </section>

            <section class="panel" aria-labelledby="history-title">
                <div class="panel-head"><h2 id="history-title">Historique</h2></div>
                <div class="panel-body">
                    <ol class="timeline">
                        @foreach($ticket->activities as $a)
                            <li>
                                <strong>{{ $a->action_label }}</strong>
                                <small>{{ $a->description }}</small>
                                <small>{{ $a->user?->full_name }}, <x-ui.when :date="$a->created_at" /></small>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
