<form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

<form method="post" action="{{ route('profile.update') }}" style="max-width: 480px">
    @csrf
    @method('patch')

    <div class="row">
        <div class="col-sm-6"><x-ui.input name="first_name" label="Prénom" :value="$user->first_name" required autocomplete="given-name" /></div>
        <div class="col-sm-6"><x-ui.input name="name" label="Nom" :value="$user->name" required autocomplete="family-name" /></div>
    </div>
    <x-ui.input name="email" label="Adresse email" type="email" :value="$user->email" required autocomplete="username" />

    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <p class="form-hint mb-3">
            Votre adresse email n'est pas vérifiée.
            <button form="send-verification" class="btn btn-link p-0 align-baseline">Renvoyer l'email de vérification</button>
        </p>
        @if (session('status') === 'verification-link-sent')
            <p class="text-success">Un nouveau lien de vérification vient d'être envoyé.</p>
        @endif
    @endif

    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-primary">Enregistrer</button>
        @if (session('status') === 'profile-updated')
            <span class="text-success" role="status"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>Profil enregistré.</span>
        @endif
    </div>
</form>
