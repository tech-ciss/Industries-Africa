@extends('layouts.guest')
@section('title', 'Connexion')
@section('heading', 'Connexion')
@section('lead', 'Accédez à vos demandes de maintenance.')
@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf
    <x-ui.input name="email" label="Adresse email" type="email" required autofocus autocomplete="username" />
    <x-ui.input name="password" label="Mot de passe" type="password" required autocomplete="current-password" />

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label" for="remember">Rester connecté</label>
        </div>
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}">Mot de passe oublié ?</a>
        @endif
    </div>

    <button class="btn btn-primary w-100">Se connecter</button>
</form>
@endsection
