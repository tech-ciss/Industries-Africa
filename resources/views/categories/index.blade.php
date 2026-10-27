@extends('layouts.bootstrap')
@section('title', 'Catégories')
@section('content')
<x-ui.page-head title="Catégories" lead="Les types de pannes proposés lors de la création d'une demande.">
    <x-slot:actions>
        <a href="{{ route('categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nouvelle catégorie</a>
    </x-slot:actions>
</x-ui.page-head>

<section class="panel" aria-label="Liste des catégories">
    @if($categories->isEmpty())
        <x-ui.empty icon="bi-tags" title="Aucune catégorie.">
            <p>Créez au moins une catégorie pour que les employés puissent déclarer une panne.</p>
            <a class="btn btn-primary" href="{{ route('categories.create') }}">Créer une catégorie</a>
        </x-ui.empty>
    @else
        <div class="table-responsive">
            <table class="table table-flat">
                <thead><tr><th>Nom</th><th class="d-none d-md-table-cell">Description</th><th>Tickets</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                @foreach($categories as $c)
                    <tr>
                        <td><strong>{{ $c->name }}</strong></td>
                        <td class="d-none d-md-table-cell text-soft">{{ $c->description ?: '-' }}</td>
                        <td class="tabular">{{ $c->tickets_count }}</td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('categories.edit', $c) }}" class="btn btn-outline-secondary btn-icon" title="Modifier" aria-label="Modifier {{ $c->name }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                @if($c->tickets_count === 0)
                                    <form method="POST" action="{{ route('categories.destroy', $c) }}" data-confirm="Supprimer la catégorie {{ $c->name }} ?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-ghost-danger btn-icon" title="Supprimer" aria-label="Supprimer {{ $c->name }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    </form>
                                @else
                                    <span class="btn btn-icon text-soft disabled" title="Utilisée par des tickets : suppression impossible"><i class="bi bi-lock" aria-hidden="true"></i><span class="visually-hidden">Suppression impossible : catégorie utilisée</span></span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="table-foot"><span>{{ $categories->total() }} {{ $categories->total() > 1 ? 'catégories' : 'catégorie' }}</span>{{ $categories->links() }}</div>
    @endif
</section>
@endsection
