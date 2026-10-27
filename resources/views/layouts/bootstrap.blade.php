@php
    $user = auth()->user();
    $ticketsLabel = match (true) {
        $user?->isAdmin() => 'Tickets',
        $user?->isTechnician() => 'Mes interventions',
        default => 'Mes demandes',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Accueil') - {{ config('app.name') }}</title>
    @include('layouts.partials.assets')
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<div class="shell">
    <aside class="sidebar" id="sidebar" aria-label="Navigation principale">
        <div class="sidebar-tape" aria-hidden="true"></div>
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="brand-mark"><i class="bi bi-wrench-adjustable" aria-hidden="true"></i></span>
            <span class="brand-name">{{ config('app.name') }}<small>Africa Industries</small></span>
        </a>

        <a href="{{ route('tickets.create') }}" class="btn btn-signal side-cta"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nouvelle demande</a>

        <nav class="side-nav">
            <a class="side-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                <i class="bi bi-speedometer2" aria-hidden="true"></i>Tableau de bord
            </a>
            <a class="side-link {{ request()->routeIs('tickets.*') ? 'is-active' : '' }}" href="{{ route('tickets.index') }}" @if(request()->routeIs('tickets.*')) aria-current="page" @endif>
                <i class="bi bi-card-checklist" aria-hidden="true"></i>{{ $ticketsLabel }}
            </a>

            @if($user->isAdmin())
                <div class="side-group">Administration</div>
                <a class="side-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}" href="{{ route('users.index') }}">
                    <i class="bi bi-people" aria-hidden="true"></i>Utilisateurs
                </a>
                <a class="side-link {{ request()->routeIs('categories.*') ? 'is-active' : '' }}" href="{{ route('categories.index') }}">
                    <i class="bi bi-tags" aria-hidden="true"></i>Catégories
                </a>
                <a class="side-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}" href="{{ route('reports.index') }}">
                    <i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i>Rapports
                </a>
            @endif
        </nav>

        <div class="side-user">
            <x-ui.avatar :user="$user" />
            <div class="who"><strong>{{ $user->full_name }}</strong><span>{{ $user->role_label }}</span></div>
            <a href="{{ route('profile.edit') }}" class="btn btn-icon" title="Mon profil" aria-label="Mon profil"><i class="bi bi-person-gear" aria-hidden="true"></i></a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button type="submit" class="btn btn-icon" title="Se déconnecter" aria-label="Se déconnecter"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></button>
            </form>
        </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

    <div class="main-col">
        <header class="topbar d-lg-none">
            <button type="button" class="btn btn-icon" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Ouvrir le menu"><i class="bi bi-list fs-4" aria-hidden="true"></i></button>
            <a class="brand" href="{{ route('dashboard') }}"><span class="brand-name">{{ config('app.name') }}</span></a>
        </header>

        <main id="main" class="content">
            @if(session('success'))
                <div class="flash flash-success" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><div>{{ session('success') }}</div></div>
            @endif
            @if($errors->any())
                <div class="flash flash-error" role="alert">
                    <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                    <div>
                        <strong>{{ $errors->count() > 1 ? 'Certaines informations sont à corriger.' : 'Une information est à corriger.' }}</strong>
                        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/maintenance.js') }}?v={{ filemtime(public_path('js/maintenance.js')) }}"></script>
@stack('scripts')
</body>
</html>
