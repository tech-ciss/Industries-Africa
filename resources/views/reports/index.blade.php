@extends('layouts.bootstrap')
@section('title', 'Rapports')
@section('content')
<h1 class="mb-4">Rapports</h1>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100"><div class="card-body">
            <h5>Rapport mensuel (PDF)</h5>
            <p class="text-muted small">Synthèse du mois : indicateurs, répartitions, charge des techniciens et liste des tickets créés pendant le mois.</p>
            <form method="GET" action="{{ route('reports.pdf') }}" class="row g-2 align-items-end">
                <div class="col">
                    <label class="form-label">Mois</label>
                    <input type="month" name="month" class="form-control" value="{{ now()->format('Y-m') }}" max="{{ now()->format('Y-m') }}" required>
                </div>
                <div class="col-auto"><button class="btn btn-danger">Générer le PDF</button></div>
            </form>
        </div></div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100"><div class="card-body">
            <h5>Export Excel</h5>
            <p class="text-muted small">Deux feuilles : « Incidents » (liste filtrée) et « Statistiques » (calculées sur les mêmes tickets). Sans filtre, tous les tickets sont exportés.</p>
            <form method="GET" action="{{ route('reports.excel') }}" class="row g-2">
                <div class="col-md-6"><label class="form-label">Du</label><input type="date" name="date_from" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Au</label><input type="date" name="date_to" class="form-control"></div>
                <div class="col-md-6">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous</option>
                        @foreach(\App\Models\Ticket::STATUS_LABELS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Priorité</label>
                    <select name="priority" class="form-select">
                        <option value="">Toutes</option>
                        @foreach(\App\Models\Ticket::PRIORITY_LABELS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Catégorie</label>
                    <select name="category_id" class="form-select">
                        <option value="">Toutes</option>
                        @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Technicien</label>
                    <select name="technician_id" class="form-select">
                        <option value="">Tous</option>
                        @foreach($technicians as $technician)<option value="{{ $technician->id }}">{{ $technician->full_name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12"><button class="btn btn-success">Exporter en Excel</button></div>
            </form>
        </div></div>
    </div>
</div>
@endsection
