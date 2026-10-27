@extends('layouts.bootstrap')
@section('title', 'Nouveau ticket')
@section('content')
<h1>Nouveau ticket</h1>
<form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="mb-3"><label class="form-label">Titre</label><input name="title" class="form-control" required minlength="5"></div>
    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" required minlength="20"></textarea></div>
    <div class="mb-3"><label class="form-label">Catégorie</label>
        <select name="category_id" class="form-select" required>
            @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="mb-3"><label class="form-label">Priorité</label>
        <select name="priority" class="form-select" required>
            @foreach(['low','normal','high','critical'] as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
        </select>
    </div>
    <div class="mb-3"><label class="form-label">Pièce jointe</label><input type="file" name="attachment" class="form-control"></div>
    <button class="btn btn-primary">Créer</button>
</form>
@endsection
