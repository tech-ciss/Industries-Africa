@extends('layouts.bootstrap')
@section('title', 'Modifier le ticket #'.$ticket->id)
@section('content')
<x-ui.page-head :title="'Modifier le ticket #'.$ticket->id" lead="Les champs modifiés sont enregistrés dans l'historique." />
<div class="row"><div class="col-lg-8">
    <div class="panel"><div class="panel-body">
        @include('tickets._form', ['action' => route('tickets.update', $ticket), 'submit' => 'Enregistrer les modifications'])
    </div></div>
</div></div>
@endsection
