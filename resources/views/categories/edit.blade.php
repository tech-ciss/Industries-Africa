@extends('layouts.bootstrap')
@section('title', 'Modifier la catégorie')
@section('content')
<x-ui.page-head :title="$category->name" lead="Modifier la catégorie." />
<div class="panel"><div class="panel-body">
    @include('categories._form', ['action' => route('categories.update', $category), 'submit' => 'Enregistrer'])
</div></div>
@endsection
