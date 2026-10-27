@extends('layouts.bootstrap')
@section('title', 'Tableau de bord')
@section('content')
@php
    $user = auth()->user();
    $lead = match (true) {
        $user->isAdmin() => 'Vue d\'ensemble de l\'activité de maintenance.',
        $user->isTechnician() => 'Vos interventions en cours et à venir.',
        default => 'L\'avancement de vos demandes.',
    };
    $recentTitle = match (true) {
        $user->isAdmin() => 'Derniers tickets',
        $user->isTechnician() => 'Mes interventions récentes',
        default => 'Mes dernières demandes',
    };
    $maxCategory = max(1, (int) ($stats['by_category']->max('tickets_count') ?? 1));
    $maxLoad = max(1, (int) ($stats['workload']?->max('open_tickets_count') ?? 1));
@endphp

<x-ui.page-head :title="'Bonjour, '.($user->first_name ?: $user->name)" :lead="$lead">
    <x-slot:actions>
        <a class="btn btn-primary" href="{{ route('tickets.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nouvelle demande</a>
    </x-slot:actions>
</x-ui.page-head>

<section class="panel mb-3" aria-labelledby="pipeline-title">
    <div class="panel-head">
        <h2 id="pipeline-title">Répartition par statut</h2>
        <a href="{{ route('tickets.index') }}" class="small">Voir tous les tickets</a>
    </div>
    <div class="panel-body">
        @if($stats['total'] === 0)
            <x-ui.empty icon="bi-inbox" title="Aucun ticket.">
                <p>Les demandes de maintenance apparaîtront ici dès leur création.</p>
            </x-ui.empty>
        @else
            <div class="pipeline">
                @foreach(\App\Models\Ticket::STATUS_LABELS as $status => $label)
                    @php($count = $stats['by_status'][$status])
                    <a class="pipe pipe-{{ $status }} {{ $count === 0 ? 'is-empty' : '' }}" href="{{ route('tickets.index', ['status' => $status]) }}"
                       style="flex-grow: {{ max($count, 0.2) }}">
                        <strong>{{ $count }}</strong><span>{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

<div class="ledger mb-4">
    <div class="ledger-item"><div class="ledger-value">{{ $stats['total'] }}</div><div class="ledger-label">Tickets au total</div></div>
    <div class="ledger-item"><div class="ledger-value">{{ $stats['open'] }}</div><div class="ledger-label">Ouverts</div></div>
    <div class="ledger-item"><div class="ledger-value">{{ $stats['resolved'] }}</div><div class="ledger-label">Résolus</div></div>
    <div class="ledger-item {{ $stats['critical'] > 0 ? 'is-alert' : 'is-zero' }}"><div class="ledger-value">{{ $stats['critical'] }}</div><div class="ledger-label">Critiques ouverts</div></div>
    <div class="ledger-item {{ $stats['overdue'] > 0 ? 'is-alert' : 'is-zero' }}"><div class="ledger-value">{{ $stats['overdue'] }}</div><div class="ledger-label">En retard</div></div>
    <div class="ledger-item"><div class="ledger-value">{{ $stats['avg_resolution_label'] }}</div><div class="ledger-label">Temps moyen de résolution</div></div>
    <div class="ledger-item"><div class="ledger-value">{{ $stats['technicians'] }}</div><div class="ledger-label">Techniciens actifs</div></div>
    @if($user->isAdmin())
        <div class="ledger-item {{ $stats['unassigned'] > 0 ? 'is-alert' : 'is-zero' }}">
            <div class="ledger-value">{{ $stats['unassigned'] }}</div>
            <div class="ledger-label">Non assignés</div>
            @if($stats['unassigned'] > 0)<a class="ledger-hint" href="{{ route('tickets.index', ['status' => 'new']) }}">À affecter</a>@endif
        </div>
    @elseif($user->isTechnician())
        <div class="ledger-item">
            <div class="ledger-value">{{ $stats['due_soon'] }}</div>
            <div class="ledger-label">Échéance sous 7 jours</div>
            <div class="ledger-hint">Temps saisi ce mois : {{ \App\Models\TimeEntry::format($stats['time_this_month']) }}</div>
        </div>
    @endif
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <section class="panel h-100" aria-labelledby="recent-title">
            <div class="panel-head"><h2 id="recent-title">{{ $recentTitle }}</h2></div>
            @if($recent->isEmpty())
                <x-ui.empty icon="bi-card-checklist" title="Aucun ticket." />
            @else
                <div class="table-responsive">
                    <table class="table table-flat">
                        <thead><tr><th>N°</th><th>Ticket</th><th>Statut</th><th class="d-none d-md-table-cell">Priorité</th><th class="d-none d-lg-table-cell">Créé</th></tr></thead>
                        <tbody>
                        @foreach($recent as $t)
                            <tr class="row-pr-{{ $t->priority }}">
                                <td class="num">#{{ $t->id }}</td>
                                <td class="title-cell">
                                    <a href="{{ route('tickets.show', $t) }}">{{ $t->title }}</a>
                                    <span class="sub">{{ $t->category?->name }}@if($t->isOverdue()) <span class="chip-late"><i class="bi bi-clock-history" aria-hidden="true"></i>En retard</span>@endif</span>
                                </td>
                                <td><x-ui.status :status="$t->status" /></td>
                                <td class="d-none d-md-table-cell"><x-ui.priority :priority="$t->priority" /></td>
                                <td class="d-none d-lg-table-cell"><x-ui.when :date="$t->created_at" /></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="col-xl-4">
        <div class="stack">
            <section class="panel" aria-labelledby="cat-title">
                <div class="panel-head"><h2 id="cat-title">Principales catégories</h2></div>
                <div class="panel-body py-2">
                    @forelse($stats['by_category'] as $category)
                        <div class="bar-row">
                            <span>{{ $category->name }}</span><strong class="tabular">{{ $category->tickets_count }}</strong>
                            <span class="meter"><i style="width: {{ round($category->tickets_count / $maxCategory * 100) }}%"></i></span>
                        </div>
                    @empty
                        <p class="text-soft my-3">Aucun ticket.</p>
                    @endforelse
                </div>
            </section>

            @if($user->isAdmin())
                <section class="panel" aria-labelledby="load-title">
                    <div class="panel-head"><h2 id="load-title">Charge des techniciens</h2></div>
                    <div class="panel-body py-2">
                        @forelse($stats['workload'] as $tech)
                            <div class="bar-row">
                                <span class="person"><x-ui.avatar :user="$tech" size="sm" /><span>{{ $tech->full_name }}</span></span>
                                <strong class="tabular" title="Tickets assignés ou en cours">{{ $tech->open_tickets_count }}</strong>
                                <span class="meter"><i style="width: {{ round($tech->open_tickets_count / $maxLoad * 100) }}%"></i></span>
                            </div>
                        @empty
                            <p class="text-soft my-3">Aucun technicien actif.</p>
                        @endforelse
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
@endsection
