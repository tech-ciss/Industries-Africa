@extends('layouts.bootstrap')
@section('title', 'Nouvelle catégorie')
@section('content')
<h1>Nouvelle catégorie</h1>
<form method="POST" action="{{ route('categories.store') }}">
@csrf
<div class="mb-3"><label>Nom</label><input name="name" class="form-control" required></div>
<div class="mb-3"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
<button class="btn btn-primary">Créer</button>
</form>
@endsection
