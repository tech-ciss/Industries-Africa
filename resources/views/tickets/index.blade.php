@extends('layouts.bootstrap')
@section('title', 'Tickets')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1>Tickets</h1>
    <a href="{{ route('tickets.create') }}" class="btn btn-primary">Nouveau ticket</a>
</div>
<form class="row g-2 mb-3" method="GET">
    <div class="col"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Recherche"></div>
    <div class="col"><select name="status" class="form-select"><option value="">Statut</option>@foreach(\App\Models\Ticket::STATUS_LABELS as $s => $label)<option value="{{ $s }}" @selected(request('status')==$s)>{{ $label }}</option>@endforeach</select></div>
    <div class="col"><select name="priority" class="form-select"><option value="">Priorité</option>@foreach(\App\Models\Ticket::PRIORITY_LABELS as $p => $label)<option value="{{ $p }}" @selected(request('priority')==$p)>{{ $label }}</option>@endforeach</select></div>
    <div class="col"><select name="category_id" class="form-select"><option value="">Catégorie</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
    <div class="col"><select name="technician_id" class="form-select"><option value="">Technicien</option>@foreach($technicians as $t)<option value="{{ $t->id }}" @selected(request('technician_id')==$t->id)>{{ $t->full_name }}</option>@endforeach</select></div>
    <div class="col"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
    <div class="col"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
    <div class="col-auto"><button class="btn btn-secondary">Filtrer</button></div>
</form>
<table class="table table-striped">
    <thead><tr><th>ID</th><th>Titre</th><th>Statut</th><th>Priorité</th><th>Catégorie</th><th>Demandeur</th><th>Technicien</th><th></th></tr></thead>
    <tbody>
    @foreach($tickets as $t)
        <tr>
            <td>{{ $t->id }}</td>
            <td>{{ $t->title }}</td>
            <td>{{ $t->status_label }}</td>
            <td>{{ $t->priority_label }}</td>
            <td>{{ $t->category?->name }}</td>
            <td>{{ $t->user?->full_name }}</td>
            <td>{{ $t->assignee?->full_name }}</td>
            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('tickets.show', $t) }}">Voir</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $tickets->links() }}
@endsection
