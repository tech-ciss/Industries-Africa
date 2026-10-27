@extends('layouts.bootstrap')
@section('title', 'Nouvelle catégorie')
@section('content')
<x-ui.page-head title="Nouvelle catégorie" />
<div class="panel"><div class="panel-body">
    @include('categories._form', ['category' => null, 'action' => route('categories.store'), 'submit' => 'Créer la catégorie'])
</div></div>
@endsection
