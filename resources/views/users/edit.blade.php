@extends('layouts.bootstrap')
@section('title', 'Modifier '.$user->full_name)
@section('content')
<x-ui.page-head :title="$user->full_name" lead="Modifier le compte, le rôle ou le statut." />
<div class="panel"><div class="panel-body">
    @include('users._form', ['action' => route('users.update', $user), 'submit' => 'Enregistrer'])
</div></div>
@endsection
