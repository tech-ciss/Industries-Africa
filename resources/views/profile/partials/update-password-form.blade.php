<form method="post" action="{{ route('password.update') }}" style="max-width: 480px">
    @csrf
    @method('put')

    <x-ui.input id="update_password_current_password" name="current_password" bag="updatePassword" label="Mot de passe actuel" type="password" autocomplete="current-password" />
    <x-ui.input id="update_password_password" name="password" bag="updatePassword" label="Nouveau mot de passe" type="password" autocomplete="new-password" hint="8 caractères minimum." />
    <x-ui.input id="update_password_password_confirmation" name="password_confirmation" bag="updatePassword" label="Confirmer le nouveau mot de passe" type="password" autocomplete="new-password" />

    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-primary">Changer le mot de passe</button>
        @if (session('status') === 'password-updated')
            <span class="text-success" role="status"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>Mot de passe modifié.</span>
        @endif
    </div>
</form>
