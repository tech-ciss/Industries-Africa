<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);
        $users = User::latest()->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create', User::class);

        return view('users.create');
    }

    public function store(StoreUserRequest $request)
    {
        User::create($request->validated());

        return redirect()->route('users.index')->with('success', 'Utilisateur créé.');
    }

    public function show(User $user)
    {
        return redirect()->route('users.edit', $user);
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        // Empêche un admin de se retirer lui-même l'accès (verrouillage de l'application).
        if ($user->is($request->user()) && ($data['role'] !== 'admin' || $data['status'] !== 'active')) {
            return back()->withInput()->withErrors([
                'role' => 'Vous ne pouvez pas retirer votre propre accès administrateur ni désactiver votre compte.',
            ]);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        // Les tickets sont supprimés en cascade : on bloque plutôt que de perdre l'historique.
        if ($user->tickets()->exists() || $user->assignedTickets()->exists()) {
            return back()->withErrors([
                'user' => 'Cet utilisateur est lié à des tickets : désactivez-le plutôt que de le supprimer.',
            ]);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé.');
    }
}
