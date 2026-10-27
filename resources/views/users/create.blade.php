@extends('layouts.bootstrap')
@section('title', 'Nouvel utilisateur')
@section('content')
<x-ui.page-head title="Nouvel utilisateur" lead="Le compte est créé directement : il n'y a pas d'inscription publique." />
<div class="panel"><div class="panel-body">
    @include('users._form', ['user' => null, 'action' => route('users.store'), 'submit' => 'Créer l\'utilisateur'])
</div></div>
@endsection
