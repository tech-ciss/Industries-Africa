@extends('layouts.bootstrap')
@section('title', 'Tableau de bord')
@section('content')
@php($user = auth()->user())
<h1 class="mb-4">Tableau de bord</h1>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card text-bg-primary"><div class="card-body"><h5>Total</h5><h2>{{ $stats['total'] }}</h2></div></div></div>
    <div class="col-md-3"><div class="card text-bg-warning"><div class="card-body"><h5>Ouverts</h5><h2>{{ $stats['open'] }}</h2></div></div></div>
    <div class="col-md-3"><div class="card text-bg-success"><div class="card-body"><h5>Résolus</h5><h2>{{ $stats['resolved'] }}</h2></div></div></div>
    <div class="col-md-3"><div class="card text-bg-danger"><div class="card-body"><h5>Critiques ouverts</h5><h2>{{ $stats['critical'] }}</h2></div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><h5>Techniciens actifs</h5><h2>{{ $stats['technicians'] }}</h2></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><h5>Temps moyen de résolution</h5><h2>{{ $stats['avg_resolution_label'] }}</h2></div></div></div>
    <div class="col-md-3"><div class="card border-danger"><div class="card-body"><h5>En retard</h5><h2 class="text-danger">{{ $stats['overdue'] }}</h2></div></div></div>
    @if($user->isAdmin())
        <div class="col-md-3"><div class="card border-warning"><div class="card-body">
            <h5>Non assignés</h5>
            <h2>{{ $stats['unassigned'] }}</h2>
            <a href="{{ route('tickets.index', ['status' => 'new']) }}" class="small">Voir les tickets</a>
        </div></div></div>
    @elseif($user->isTechnician())
        <div class="col-md-3"><div class="card"><div class="card-body">
            <h5>Échéance sous 7 jours</h5>
            <h2>{{ $stats['due_soon'] }}</h2>
            <small class="text-muted">Temps saisi ce mois : {{ \App\Models\TimeEntry::format($stats['time_this_month']) }}</small>
        </div></div></div>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h5 class="mb-3">Répartition par statut</h5>
            @foreach(\App\Models\Ticket::STATUS_LABELS as $status => $label)
                @php($count = $stats['by_status'][$status])
                @php($percent = $stats['total'] > 0 ? round($count / $stats['total'] * 100) : 0)
                <div class="d-flex justify-content-between small"><span>{{ $label }}</span><span>{{ $count }}</span></div>
                <div class="progress mb-2" style="height: 8px"><div class="progress-bar" style="width: {{ $percent }}%"></div></div>
            @endforeach
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h5 class="mb-3">Principales catégories</h5>
            @forelse($stats['by_category'] as $category)
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span>{{ $category->name }}</span><span class="badge bg-secondary">{{ $category->tickets_count }}</span>
                </div>
            @empty
                <p class="text-muted mb-0">Aucun ticket.</p>
            @endforelse
        </div></div>
    </div>
</div>

@if($user->isAdmin())
<div class="card mb-4"><div class="card-body">
    <h5 class="mb-3">Charge des techniciens</h5>
    <table class="table table-sm mb-0">
        <thead><tr><th>Technicien</th><th class="text-end">Tickets en cours</th></tr></thead>
        <tbody>
        @forelse($stats['workload'] as $tech)
            <tr><td>{{ $tech->full_name }}</td><td class="text-end">{{ $tech->open_tickets_count }}</td></tr>
        @empty
            <tr><td colspan="2" class="text-muted">Aucun technicien actif.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endif

<h3>Derniers tickets</h3>
<table class="table table-striped">
    <thead><tr><th>ID</th><th>Titre</th><th>Statut</th><th>Priorité</th><th>Demandeur</th><th></th></tr></thead>
    <tbody>
    @forelse($recent as $t)
        <tr>
            <td>{{ $t->id }}</td>
            <td>{{ $t->title }}</td>
            <td><span class="badge bg-secondary">{{ $t->status_label }}</span></td>
            <td>{{ $t->priority_label }}</td>
            <td>{{ $t->user?->full_name }}</td>
            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('tickets.show', $t) }}">Voir</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">Aucun ticket.</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
