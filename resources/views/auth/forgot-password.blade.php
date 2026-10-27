@extends('layouts.guest')
@section('title', 'Mot de passe oublié')
@section('heading', 'Mot de passe oublié')
@section('lead', 'Indiquez votre adresse email : nous vous envoyons un lien pour choisir un nouveau mot de passe.')
@section('content')
<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <x-ui.input name="email" label="Adresse email" type="email" required autofocus />
    <button class="btn btn-primary w-100 mb-3">Envoyer le lien</button>
    <a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Retour à la connexion</a>
</form>
@endsection
