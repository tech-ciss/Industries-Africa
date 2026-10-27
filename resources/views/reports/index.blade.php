@extends('layouts.bootstrap')
@section('title', 'Rapports')
@section('content')
<x-ui.page-head title="Rapports" lead="Exportez les tickets et leurs statistiques, ou générez le rapport d'un mois." />

<div class="row g-4">
    <div class="col-lg-5">
        <section class="panel h-100" aria-labelledby="pdf-title">
            <div class="panel-head"><h2 id="pdf-title">Rapport mensuel (PDF)</h2><i class="bi bi-file-earmark-pdf fs-4 text-danger" aria-hidden="true"></i></div>
            <div class="panel-body">
                <p class="text-soft">Synthèse du mois : indicateurs, répartitions, charge des techniciens et liste des tickets créés pendant le mois.</p>
                <form method="GET" action="{{ route('reports.pdf') }}" class="row g-2 align-items-end">
                    <div class="col">
                        <label class="form-label" for="month">Mois</label>
                        <input id="month" type="month" name="month" class="form-control" value="{{ now()->format('Y-m') }}" max="{{ now()->format('Y-m') }}" required>
                    </div>
                    <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-download me-1" aria-hidden="true"></i>Générer le PDF</button></div>
                </form>
            </div>
        </section>
    </div>

    <div class="col-lg-7">
        <section class="panel h-100" aria-labelledby="xls-title">
            <div class="panel-head"><h2 id="xls-title">Export Excel</h2><i class="bi bi-file-earmark-excel fs-4 text-success" aria-hidden="true"></i></div>
            <div class="panel-body">
                <p class="text-soft">Deux feuilles : « Incidents » (liste filtrée) et « Statistiques » (calculées sur les mêmes tickets). Sans filtre, tous les tickets sont exportés.</p>
                <form method="GET" action="{{ route('reports.excel') }}" class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="date_from">Du</label><input id="date_from" type="date" name="date_from" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label" for="date_to">Au</label><input id="date_to" type="date" name="date_to" class="form-control"></div>
                    <div class="col-md-6">
                        <label class="form-label" for="status">Statut</label>
                        <select id="status" name="status" class="form-select"><option value="">Tous</option>
                            @foreach(\App\Models\Ticket::STATUS_LABELS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="priority">Priorité</label>
                        <select id="priority" name="priority" class="form-select"><option value="">Toutes</option>
                            @foreach(\App\Models\Ticket::PRIORITY_LABELS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category_id">Catégorie</label>
                        <select id="category_id" name="category_id" class="form-select"><option value="">Toutes</option>
                            @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="technician_id">Technicien</label>
                        <select id="technician_id" name="technician_id" class="form-select"><option value="">Tous</option>
                            @foreach($technicians as $technician)<option value="{{ $technician->id }}">{{ $technician->full_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-download me-1" aria-hidden="true"></i>Exporter en Excel</button></div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
