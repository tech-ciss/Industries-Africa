@extends('layouts.guest')
@section('title', 'Nouveau mot de passe')
@section('heading', 'Nouveau mot de passe')
@section('lead', 'Choisissez un mot de passe d\'au moins 8 caractères.')
@section('content')
<form method="POST" action="{{ route('password.store') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <x-ui.input name="email" label="Adresse email" type="email" :value="$request->email" required autofocus autocomplete="username" />
    <x-ui.input name="password" label="Nouveau mot de passe" type="password" required autocomplete="new-password" />
    <x-ui.input name="password_confirmation" label="Confirmer le mot de passe" type="password" required autocomplete="new-password" />
    <button class="btn btn-primary w-100">Enregistrer le mot de passe</button>
</form>
@endsection
