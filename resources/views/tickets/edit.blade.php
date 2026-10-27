@extends('layouts.bootstrap')
@section('title', 'Modifier ticket')
@section('content')
<h1>Modifier ticket</h1>
<form method="POST" action="{{ route('tickets.update', $ticket) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="mb-3"><label class="form-label">Titre</label><input name="title" class="form-control" value="{{ $ticket->title }}" required minlength="5"></div>
    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" required minlength="20">{{ $ticket->description }}</textarea></div>
    <div class="mb-3"><label class="form-label">Catégorie</label>
        <select name="category_id" class="form-select" required>
            @foreach($categories as $c)<option value="{{ $c->id }}" @selected($ticket->category_id==$c->id)>{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="mb-3"><label class="form-label">Priorité</label>
        <select name="priority" class="form-select" required>
            @foreach(['low','normal','high','critical'] as $p)<option value="{{ $p }}" @selected($ticket->priority==$p)>{{ $p }}</option>@endforeach
        </select>
    </div>
    <div class="mb-3"><label class="form-label">Pièce jointe</label><input type="file" name="attachment" class="form-control"></div>
    <button class="btn btn-primary">Mettre à jour</button>
</form>
@endsection
