@extends('layouts.bootstrap')
@section('title', 'Utilisateurs')
@section('content')
<x-ui.page-head title="Utilisateurs" lead="Comptes, rôles et statuts. Désactivez un compte plutôt que de le supprimer pour conserver l'historique.">
    <x-slot:actions>
        <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>Nouvel utilisateur</a>
    </x-slot:actions>
</x-ui.page-head>

<section class="panel" aria-label="Liste des utilisateurs">
    <div class="table-responsive">
        <table class="table table-flat">
            <thead><tr><th>Utilisateur</th><th>Rôle</th><th class="d-none d-md-table-cell">Téléphone</th><th>Statut</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td>
                        <span class="person"><x-ui.avatar :user="$u" />
                            <span class="title-cell"><strong>{{ $u->full_name }}</strong><span class="sub">{{ $u->email }}</span></span>
                        </span>
                    </td>
                    <td>{{ $u->role_label }}</td>
                    <td class="d-none d-md-table-cell">{{ $u->phone ?: '-' }}</td>
                    <td><span class="status {{ $u->status === 'active' ? 'status-resolved' : 'status-new' }}">{{ $u->status_label }}</span></td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('users.edit', $u) }}" class="btn btn-outline-secondary btn-icon" title="Modifier" aria-label="Modifier {{ $u->full_name }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @if(! $u->is(auth()->user()))
                                <form method="POST" action="{{ route('users.destroy', $u) }}" data-confirm="Supprimer {{ $u->full_name }} ? Un utilisateur lié à des tickets ne peut pas être supprimé.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost-danger btn-icon" title="Supprimer" aria-label="Supprimer {{ $u->full_name }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="table-foot"><span>{{ $users->total() }} {{ $users->total() > 1 ? 'utilisateurs' : 'utilisateur' }}</span>{{ $users->links() }}</div>
</section>
@endsection
