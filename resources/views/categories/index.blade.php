@extends('layouts.bootstrap')
@section('title', 'Catégories')
@section('content')
<div class="d-flex justify-content-between mb-3"><h1>Catégories</h1><a href="{{ route('categories.create') }}" class="btn btn-primary">Nouvelle</a></div>
<table class="table table-striped"><thead><tr><th>Nom</th><th>Description</th><th>Tickets</th><th></th></tr></thead><tbody>
@foreach($categories as $c)
<tr><td>{{ $c->name }}</td><td>{{ $c->description }}</td><td>{{ $c->tickets_count }}</td>
<td><a href="{{ route('categories.edit', $c) }}" class="btn btn-sm btn-outline-secondary">Modifier</a></td></tr>
@endforeach
</tbody></table>
{{ $categories->links() }}
@endsection
