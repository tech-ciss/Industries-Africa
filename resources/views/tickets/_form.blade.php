@php
    $selectedPriority = old('priority', $ticket?->priority ?? 'normal');
    $hints = [
        'low' => 'Gêne mineure, peut attendre',
        'normal' => 'Fonctionnement dégradé',
        'high' => 'Bloque une partie du travail',
        'critical' => 'Sécurité ou arrêt d\'activité',
    ];
@endphp
<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if($ticket) @method('PUT') @endif

    <x-ui.input name="title" label="Titre" :value="$ticket?->title" required minlength="5" maxlength="255"
                hint="Résumez la panne en une phrase : quoi et où." />

    <x-ui.textarea name="description" label="Description" :value="$ticket?->description" required minlength="20" rows="6"
                   data-counter="#description-count" data-min="20"
                   hint="Quand le problème est apparu, ce qui ne fonctionne plus, l'impact sur votre travail.">
        <x-slot:extra><div id="description-count" class="form-hint" aria-live="polite"></div></x-slot:extra>
    </x-ui.textarea>

    <x-ui.select name="category_id" label="Catégorie" required>
        <option value="" disabled @selected(! old('category_id', $ticket?->category_id))>Choisir une catégorie</option>
        @foreach($categories as $c)
            <option value="{{ $c->id }}" @selected(old('category_id', $ticket?->category_id) == $c->id)>{{ $c->name }}</option>
        @endforeach
    </x-ui.select>

    <fieldset class="mb-3">
        <legend class="form-label">Priorité<span class="required-mark" aria-hidden="true">*</span></legend>
        <div class="choices">
            @foreach(\App\Models\Ticket::PRIORITY_LABELS as $value => $label)
                <label class="choice choice-{{ $value }}">
                    <input type="radio" name="priority" value="{{ $value }}" @checked($selectedPriority === $value) required>
                    <span class="choice-body"><strong>{{ $label }}</strong><small>{{ $hints[$value] }}</small></span>
                </label>
            @endforeach
        </div>
        @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </fieldset>

    <x-ui.input name="attachment" label="Pièce jointe" type="file"
                :hint="$ticket?->attachment ? 'Fichier actuel : '.basename($ticket->attachment).'. En choisir un autre le remplace. 5 Mo maximum.' : 'Photo ou document, 5 Mo maximum.'" />

    <div class="d-flex flex-wrap gap-2 pt-2">
        <button class="btn btn-primary">{{ $submit }}</button>
        <a class="btn btn-outline-secondary" href="{{ $ticket ? route('tickets.show', $ticket) : route('tickets.index') }}">Annuler</a>
    </div>
</form>
