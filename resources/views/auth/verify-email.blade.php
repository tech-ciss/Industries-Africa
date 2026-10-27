@extends('layouts.guest')
@section('title', 'Vérification de l\'email')
@section('heading', 'Vérifiez votre adresse email')
@section('lead', 'Cliquez sur le lien que nous venons de vous envoyer par email. Vous ne l\'avez pas reçu ? Nous pouvons vous en envoyer un autre.')
@section('content')
@if (session('status') == 'verification-link-sent')
    <div class="flash flash-success" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><div>Un nouveau lien de vérification vient d'être envoyé.</div></div>
@endif
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
    <form method="POST" action="{{ route('verification.send') }}">@csrf
        <button class="btn btn-primary">Renvoyer le lien</button>
    </form>
    <form method="POST" action="{{ route('logout') }}">@csrf
        <button class="btn btn-link p-0">Se déconnecter</button>
    </form>
</div>
@endsection
