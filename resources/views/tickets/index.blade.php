@extends('layouts.bootstrap')
@section('title', 'Tickets')
@section('content')
@php
    $user = auth()->user();
    $title = match (true) {
        $user->isAdmin() => 'Tickets',
        $user->isTechnician() => 'Mes interventions',
        default => 'Mes demandes',
    };
    $activeFilters = collect(['status', 'priority', 'category_id', 'technician_id', 'date_from', 'date_to'])
        ->filter(fn ($key) => request()->filled($key))->count();
    $hasFilter = $activeFilters > 0 || request()->filled('search');
@endphp

<x-ui.page-head :title="$title" :lead="$user->isAdmin() ? 'Toutes les demandes de maintenance.' : null">
    <x-slot:actions>
        <a class="btn btn-primary" href="{{ route('tickets.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nouvelle demande</a>
    </x-slot:actions>
</x-ui.page-head>

<form method="GET" class="panel filters mb-3" role="search">
    <div class="d-flex flex-wrap gap-2">
        <div class="flex-grow-1" style="min-width: 220px">
            <label class="visually-hidden" for="search">Rechercher dans les titres</label>
            <input id="search" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Rechercher dans les titres">
        </div>
        <button class="btn btn-primary">Rechercher</button>
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#more-filters"
                aria-expanded="{{ $activeFilters ? 'true' : 'false' }}" aria-controls="more-filters">
            <i class="bi bi-sliders me-1" aria-hidden="true"></i>Filtres @if($activeFilters)<span class="filters-count">{{ $activeFilters }}</span>@endif
        </button>
    </div>
    <div class="collapse {{ $activeFilters ? 'show' : '' }}" id="more-filters">
        <div class="row g-3 pt-3">
            <div class="col-sm-6 col-lg-3">
                <label class="form-label" for="f-status">Statut</label>
                <select id="f-status" name="status" class="form-select"><option value="">Tous</option>
                    @foreach(\App\Models\Ticket::STATUS_LABELS as $s => $label)<option value="{{ $s }}" @selected(request('status') == $s)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label" for="f-priority">Priorité</label>
                <select id="f-priority" name="priority" class="form-select"><option value="">Toutes</option>
                    @foreach(\App\Models\Ticket::PRIORITY_LABELS as $p => $label)<option value="{{ $p }}" @selected(request('priority') == $p)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label" for="f-category">Catégorie</label>
                <select id="f-category" name="category_id" class="form-select"><option value="">Toutes</option>
                    @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            @if($user->isAdmin())
            <div class="col-sm-6 col-lg-3">
                <label class="form-label" for="f-tech">Technicien</label>
                <select id="f-tech" name="technician_id" class="form-select"><option value="">Tous</option>
                    @foreach($technicians as $t)<option value="{{ $t->id }}" @selected(request('technician_id') == $t->id)>{{ $t->full_name }}</option>@endforeach
                </select>
            </div>
            @endif
            <div class="col-sm-6 col-lg-3"><label class="form-label" for="f-from">Créé à partir du</label><input id="f-from" type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
            <div class="col-sm-6 col-lg-3"><label class="form-label" for="f-to">Créé jusqu'au</label><input id="f-to" type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">Appliquer les filtres</button>
                @if($hasFilter)<a class="btn btn-outline-secondary" href="{{ route('tickets.index') }}">Réinitialiser</a>@endif
            </div>
        </div>
    </div>
</form>

<section class="panel" aria-label="Liste des tickets">
    @if($tickets->isEmpty())
        @if($hasFilter)
            <x-ui.empty icon="bi-funnel" title="Aucun ticket ne correspond à ces filtres.">
                <p>Essayez d'élargir la recherche ou de retirer un filtre.</p>
                <a class="btn btn-outline-secondary" href="{{ route('tickets.index') }}">Réinitialiser les filtres</a>
            </x-ui.empty>
        @else
            <x-ui.empty icon="bi-inbox" title="Aucun ticket pour le moment.">
                <p>Une machine, une prise ou une connexion en panne ? Signalez-la en quelques lignes.</p>
                <a class="btn btn-primary" href="{{ route('tickets.create') }}">Créer une demande</a>
            </x-ui.empty>
        @endif
    @else
        <div class="table-responsive">
            <table class="table table-flat">
                <thead>
                <tr>
                    <th>N°</th><th>Ticket</th><th>Statut</th><th class="d-none d-md-table-cell">Priorité</th>
                    @unless($user->isEmployee())<th class="d-none d-xl-table-cell">Demandeur</th>@endunless
                    @unless($user->isTechnician())<th class="d-none d-lg-table-cell">Technicien</th>@endunless
                    <th class="d-none d-lg-table-cell">Échéance</th><th class="d-none d-xl-table-cell">Créé</th>
                </tr>
                </thead>
                <tbody>
                @foreach($tickets as $t)
                    <tr class="row-pr-{{ $t->priority }}">
                        <td class="num">#{{ $t->id }}</td>
                        <td class="title-cell">
                            <a href="{{ route('tickets.show', $t) }}">{{ $t->title }}</a>
                            <span class="sub">{{ $t->category?->name }}</span>
                        </td>
                        <td><x-ui.status :status="$t->status" /></td>
                        <td class="d-none d-md-table-cell"><x-ui.priority :priority="$t->priority" /></td>
                        @unless($user->isEmployee())<td class="d-none d-xl-table-cell"><x-ui.person :user="$t->user" /></td>@endunless
                        @unless($user->isTechnician())<td class="d-none d-lg-table-cell"><x-ui.person :user="$t->assignee" /></td>@endunless
                        <td class="d-none d-lg-table-cell">
                            @if($t->deadline)
                                {{ $t->deadline->format('d/m/Y') }}
                                @if($t->isOverdue())<span class="chip-late d-block"><i class="bi bi-clock-history" aria-hidden="true"></i>En retard</span>@endif
                            @else
                                <span class="text-soft">-</span>
                            @endif
                        </td>
                        <td class="d-none d-xl-table-cell"><x-ui.when :date="$t->created_at" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="table-foot">
            <span>{{ $tickets->total() }} {{ $tickets->total() > 1 ? 'tickets' : 'ticket' }}</span>
            {{ $tickets->links() }}
        </div>
    @endif
</section>
@endsection
