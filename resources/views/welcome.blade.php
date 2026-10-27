@extends('layouts.guest')
@section('title', 'Accueil')
@section('heading', 'Bienvenue')
@section('lead', 'Connectez-vous pour signaler une panne ou suivre vos interventions.')
@section('content')
    @auth
        <a class="btn btn-primary w-100" href="{{ route('dashboard') }}">Ouvrir le tableau de bord</a>
    @else
        <a class="btn btn-primary w-100" href="{{ route('login') }}">Se connecter</a>
    @endauth
@endsection
