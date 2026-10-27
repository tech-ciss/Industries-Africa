@extends('layouts.bootstrap')
@section('title', 'Nouvel utilisateur')
@section('content')
<h1>Nouvel utilisateur</h1>
<form method="POST" action="{{ route('users.store') }}">
@csrf
<div class="mb-3"><label>Nom</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
<div class="mb-3"><label>Prénom</label><input name="first_name" value="{{ old('first_name') }}" class="form-control" required></div>
<div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
<div class="mb-3"><label>Téléphone</label><input name="phone" class="form-control"></div>
<div class="mb-3"><label>Mot de passe</label><input type="password" name="password" class="form-control" required></div>
<div class="mb-3"><label>Confirmation</label><input type="password" name="password_confirmation" class="form-control" required></div>
<div class="mb-3"><label>Rôle</label><select name="role" class="form-select" required><option value="employee">Employé</option><option value="technician">Technicien</option><option value="admin">Admin</option></select></div>
<div class="mb-3"><label>Statut</label><select name="status" class="form-select" required><option value="active">Actif</option><option value="inactive">Inactif</option></select></div>
<button class="btn btn-primary">Créer</button>
</form>
@endsection
