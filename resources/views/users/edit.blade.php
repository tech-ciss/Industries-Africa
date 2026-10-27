@extends('layouts.bootstrap')
@section('title', 'Modifier utilisateur')
@section('content')
<h1>Modifier utilisateur</h1>
<form method="POST" action="{{ route('users.update', $user) }}">
@csrf @method('PUT')
<div class="mb-3"><label>Nom</label><input name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
<div class="mb-3"><label>Prénom</label><input name="first_name" value="{{ old('first_name', $user->first_name) }}" class="form-control" required></div>
<div class="mb-3"><label>Email</label><input type="email" name="email" value="{{ $user->email }}" class="form-control" required></div>
<div class="mb-3"><label>Téléphone</label><input name="phone" value="{{ $user->phone }}" class="form-control"></div>
<div class="mb-3"><label>Mot de passe (laisser vide)</label><input type="password" name="password" class="form-control"></div>
<div class="mb-3"><label>Confirmation</label><input type="password" name="password_confirmation" class="form-control"></div>
<div class="mb-3"><label>Rôle</label><select name="role" class="form-select" required>@foreach(['employee','technician','admin'] as $r)<option value="{{ $r }}" @selected($user->role==$r)>{{ $r }}</option>@endforeach</select></div>
<div class="mb-3"><label>Statut</label><select name="status" class="form-select" required>@foreach(['active','inactive'] as $s)<option value="{{ $s }}" @selected($user->status==$s)>{{ $s }}</option>@endforeach</select></div>
<button class="btn btn-primary">Mettre à jour</button>
</form>
@endsection
