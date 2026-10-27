@extends('layouts.guest')
@section('title', 'Confirmation')
@section('heading', 'Confirmer votre mot de passe')
@section('lead', 'Cette action est sensible : saisissez votre mot de passe pour continuer.')
@section('content')
<form method="POST" action="{{ route('password.confirm') }}">
    @csrf
    <x-ui.input name="password" label="Mot de passe" type="password" required autofocus autocomplete="current-password" />
    <button class="btn btn-primary w-100">Confirmer</button>
</form>
@endsection
