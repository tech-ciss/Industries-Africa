@extends('layouts.bootstrap')
@section('title', 'Utilisateurs')
@section('content')
<div class="d-flex justify-content-between mb-3"><h1>Utilisateurs</h1><a href="{{ route('users.create') }}" class="btn btn-primary">Nouveau</a></div>
<table class="table table-striped"><thead><tr><th>Nom</th><th>Prénom</th><th>Email</th><th>Rôle</th><th>Statut</th><th></th></tr></thead><tbody>
@foreach($users as $u)
<tr><td>{{ $u->name }}</td><td>{{ $u->first_name }}</td><td>{{ $u->email }}</td><td>{{ $u->role }}</td><td>{{ $u->status }}</td>
<td><a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-outline-secondary">Modifier</a></td></tr>
@endforeach
</tbody></table>
{{ $users->links() }}
@endsection
