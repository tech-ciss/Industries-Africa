@extends('layouts.bootstrap')
@section('title', 'Nouvelle demande')
@section('content')
<x-ui.page-head title="Nouvelle demande" lead="Décrivez la panne : un technicien sera affecté dès que possible." />
<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel"><div class="panel-body">
            @include('tickets._form', ['ticket' => null, 'action' => route('tickets.store'), 'submit' => 'Envoyer la demande'])
        </div></div>
    </div>
    <div class="col-lg-4">
        <aside class="panel panel-note"><div class="panel-body">
            <h2 class="h5">Pour une prise en charge rapide</h2>
            <ul class="tips">
                <li>Indiquez le lieu précis : bâtiment, étage, bureau.</li>
                <li>Précisez depuis quand la panne existe.</li>
                <li>Ajoutez une photo si elle aide à comprendre.</li>
                <li>Réservez la priorité « Critique » aux risques pour la sécurité ou à l'arrêt complet d'une activité.</li>
            </ul>
        </div></aside>
    </div>
</div>
@endsection
