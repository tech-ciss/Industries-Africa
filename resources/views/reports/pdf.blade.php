<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Rapport mensuel - {{ $label }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
    h1 { font-size: 18px; margin: 0 0 2px; }
    h2 { font-size: 12px; margin: 16px 0 6px; border-bottom: 1px solid #999; padding-bottom: 2px; }
    .muted { color: #666; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #bbb; padding: 3px 5px; text-align: left; vertical-align: top; }
    th { background: #eee; }
    tr { page-break-inside: avoid; }
    td.num, th.num { text-align: right; width: 70px; }
    .kpi td { width: 25%; text-align: center; border: 1px solid #bbb; padding: 6px; }
    .kpi .value { font-size: 16px; font-weight: bold; }
    .col { width: 49%; display: inline-block; vertical-align: top; }
</style>
</head>
<body>
<h1>Rapport mensuel des demandes de maintenance</h1>
<div class="muted">AFRICA INDUSTRIES - {{ $label }} - généré le {{ $generated_at->format('d/m/Y à H:i') }}</div>

<h2>Synthèse</h2>
<table class="kpi">
    <tr>
        <td><div class="value">{{ $stats['total'] }}</div>tickets créés</td>
        <td><div class="value">{{ $resolved_in_period }}</div>tickets résolus dans le mois</td>
        <td><div class="value">{{ $stats['open'] }}</div>encore ouverts</td>
        <td><div class="value">{{ $stats['critical'] }}</div>critiques ouverts</td>
    </tr>
    <tr>
        <td><div class="value">{{ $stats['overdue'] }}</div>en retard</td>
        <td><div class="value">{{ $avg_resolution_period_label }}</div>temps moyen de résolution (résolus du mois)</td>
        <td><div class="value">{{ $stats['time_spent_label'] }}</div>temps passé</td>
        <td><div class="value">{{ $stats['resolved'] }}</div>résolus ou fermés (créés ce mois)</td>
    </tr>
</table>

<h2>Répartition</h2>
<table>
    <tr>
        <th>Statut</th><th class="num">Nombre</th>
        <th>Priorité</th><th class="num">Nombre</th>
    </tr>
    @php($statuses = array_keys(\App\Models\Ticket::STATUS_LABELS))
    @php($priorities = array_keys(\App\Models\Ticket::PRIORITY_LABELS))
    @for($i = 0; $i < max(count($statuses), count($priorities)); $i++)
        <tr>
            @if(isset($statuses[$i]))
                <td>{{ \App\Models\Ticket::STATUS_LABELS[$statuses[$i]] }}</td><td class="num">{{ $stats['by_status'][$statuses[$i]] }}</td>
            @else
                <td></td><td></td>
            @endif
            @if(isset($priorities[$i]))
                <td>{{ \App\Models\Ticket::PRIORITY_LABELS[$priorities[$i]] }}</td><td class="num">{{ $stats['by_priority'][$priorities[$i]] }}</td>
            @else
                <td></td><td></td>
            @endif
        </tr>
    @endfor
</table>

<h2>Par catégorie</h2>
<table>
    <tr><th>Catégorie</th><th class="num">Tickets</th></tr>
    @forelse($stats['by_category'] as $name => $count)
        <tr><td>{{ $name }}</td><td class="num">{{ $count }}</td></tr>
    @empty
        <tr><td colspan="2" class="muted">Aucun ticket ce mois-ci.</td></tr>
    @endforelse
</table>

<h2>Charge des techniciens</h2>
<table>
    <tr><th>Technicien</th><th class="num">Assignés</th><th class="num">Terminés</th></tr>
    @forelse($stats['by_technician'] as $tech)
        <tr><td>{{ $tech['name'] }}</td><td class="num">{{ $tech['assigned'] }}</td><td class="num">{{ $tech['done'] }}</td></tr>
    @empty
        <tr><td colspan="3" class="muted">Aucun ticket assigné.</td></tr>
    @endforelse
</table>

<h2>Liste des tickets créés en {{ $label }}</h2>
<table>
    <tr>
        <th class="num" style="width:25px">ID</th><th>Titre</th><th>Catégorie</th><th>Priorité</th><th>Statut</th>
        <th>Demandeur</th><th>Technicien</th><th>Créé le</th><th>Résolu le</th>
    </tr>
    @forelse($tickets as $t)
        <tr>
            <td class="num">{{ $t->id }}</td>
            <td>{{ $t->title }}</td>
            <td>{{ $t->category?->name }}</td>
            <td>{{ $t->priority_label }}</td>
            <td>{{ $t->status_label }}</td>
            <td>{{ $t->user?->full_name }}</td>
            <td>{{ $t->assignee?->full_name }}</td>
            <td>{{ $t->created_at->format('d/m/Y') }}</td>
            <td>{{ $t->resolved_at?->format('d/m/Y') }}</td>
        </tr>
    @empty
        <tr><td colspan="9" class="muted">Aucun ticket créé pendant ce mois.</td></tr>
    @endforelse
</table>
</body>
</html>
