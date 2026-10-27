@php($self = $user && $user->is(auth()->user()))
<form method="POST" action="{{ $action }}" style="max-width: 640px">
    @csrf
    @if($user) @method('PUT') @endif

    <div class="row">
        <div class="col-sm-6"><x-ui.input name="first_name" label="Prénom" :value="$user?->first_name" required autocomplete="off" /></div>
        <div class="col-sm-6"><x-ui.input name="name" label="Nom" :value="$user?->name" required autocomplete="off" /></div>
        <div class="col-sm-6"><x-ui.input name="email" label="Adresse email" type="email" :value="$user?->email" required autocomplete="off" /></div>
        <div class="col-sm-6"><x-ui.input name="phone" label="Téléphone" type="tel" :value="$user?->phone" autocomplete="off" /></div>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <x-ui.select name="role" label="Rôle" required :disabled="$self">
                @foreach(\App\Models\User::ROLE_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $user?->role ?? 'employee') === $value)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="col-sm-6">
            <x-ui.select name="status" label="Statut" required :disabled="$self"
                         :hint="$self ? 'Vous ne pouvez pas modifier votre propre rôle ni désactiver votre compte.' : 'Un compte inactif ne peut plus se connecter.'">
                @foreach(\App\Models\User::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $user?->status ?? 'active') === $value)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </div>
    </div>
    @if($self)
        <input type="hidden" name="role" value="{{ $user->role }}">
        <input type="hidden" name="status" value="{{ $user->status }}">
    @endif

    <div class="row">
        <div class="col-sm-6"><x-ui.input name="password" label="Mot de passe" type="password" :required="! $user" autocomplete="new-password"
                :hint="$user ? 'Laissez vide pour conserver le mot de passe actuel.' : '8 caractères minimum.'" /></div>
        <div class="col-sm-6"><x-ui.input name="password_confirmation" label="Confirmer le mot de passe" type="password" :required="! $user" autocomplete="new-password" /></div>
    </div>

    <div class="d-flex gap-2 pt-2">
        <button class="btn btn-primary">{{ $submit }}</button>
        <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Annuler</a>
    </div>
</form>
