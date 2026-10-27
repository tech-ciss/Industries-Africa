@extends('layouts.bootstrap')
@section('title', 'Modifier catégorie')
@section('content')
<h1>Modifier catégorie</h1>
<form method="POST" action="{{ route('categories.update', $category) }}">
@csrf @method('PUT')
<div class="mb-3"><label>Nom</label><input name="name" value="{{ $category->name }}" class="form-control" required></div>
<div class="mb-3"><label>Description</label><textarea name="description" class="form-control">{{ $category->description }}</textarea></div>
<button class="btn btn-primary">Mettre à jour</button>
</form>
@endsection
