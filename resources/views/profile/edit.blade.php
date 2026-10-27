@extends('layouts.bootstrap')
@section('title', 'Mon profil')
@section('content')
<x-ui.page-head title="Mon profil" lead="Vos informations personnelles et votre mot de passe." />

<div class="stack">
    <section class="panel"><div class="panel-body row g-4">
        <div class="col-lg-4">
            <h2 class="h5">Informations personnelles</h2>
            <p class="text-soft mb-0">Ces informations apparaissent sur vos demandes et dans l'historique.</p>
        </div>
        <div class="col-lg-8">@include('profile.partials.update-profile-information-form')</div>
    </div></section>

    <section class="panel"><div class="panel-body row g-4">
        <div class="col-lg-4">
            <h2 class="h5">Mot de passe</h2>
            <p class="text-soft mb-0">Choisissez un mot de passe long et unique pour protéger votre compte.</p>
        </div>
        <div class="col-lg-8">@include('profile.partials.update-password-form')</div>
    </div></section>
</div>
@endsection
