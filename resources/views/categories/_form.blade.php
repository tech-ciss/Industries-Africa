<form method="POST" action="{{ $action }}" style="max-width: 560px">
    @csrf
    @if($category) @method('PUT') @endif
    <x-ui.input name="name" label="Nom" :value="$category?->name" required minlength="3" maxlength="255" hint="Par exemple : Climatisation, Réseau, Plomberie." />
    <x-ui.textarea name="description" label="Description" :value="$category?->description" rows="3" hint="Facultative. Aide les demandeurs à choisir la bonne catégorie." />
    <div class="d-flex gap-2 pt-2">
        <button class="btn btn-primary">{{ $submit }}</button>
        <a class="btn btn-outline-secondary" href="{{ route('categories.index') }}">Annuler</a>
    </div>
</form>
