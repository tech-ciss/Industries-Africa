<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Connexion') - {{ config('app.name') }}</title>
    @include('layouts.partials.assets')
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<div class="auth">
    <div class="auth-brand">
        <div class="sidebar-tape" aria-hidden="true"></div>
        <a class="brand" href="{{ url('/') }}">
            <span class="brand-mark"><i class="bi bi-wrench-adjustable" aria-hidden="true"></i></span>
            <span class="brand-name">{{ config('app.name') }}<small>Africa Industries</small></span>
        </a>
        <div class="auth-pitch">
            <h2>Une panne ? Signalez-la, suivez-la jusqu'à la réparation.</h2>
            <p>Chaque demande est tracée de sa création à sa clôture, avec le technicien en charge et son historique.</p>
            <ol class="rail auth-rail" aria-hidden="true">
                @foreach(['Nouveau', 'Assigné', 'En cours', 'Résolu', 'Fermé'] as $i => $label)
                    <li class="rail-step {{ $i < 2 ? 'is-done' : ($i === 2 ? 'is-current' : '') }}">
                        <span class="rail-dot">@if($i < 2)<i class="bi bi-check-lg"></i>@endif</span>
                        <span class="rail-label">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
    <main id="main" class="auth-form">
        <div class="auth-card">
            <h1>@yield('heading')</h1>
            <p class="lead-text">@yield('lead')</p>

            @if(session('status'))
                <div class="flash flash-success" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><div>{{ session('status') }}</div></div>
            @endif

            @yield('content')
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/maintenance.js') }}?v={{ filemtime(public_path('js/maintenance.js')) }}"></script>
</body>
</html>
