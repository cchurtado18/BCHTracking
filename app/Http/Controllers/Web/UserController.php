<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->whereNull('agency_id')->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        $statsQuery = User::query()->whereNull('agency_id');
        if ($request->filled('search')) {
            $s = $request->search;
            $statsQuery->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            });
        }
        $statsTotal = $statsQuery->count();
        $statsAdmin = (clone $statsQuery)->where('is_admin', true)->count();
        $statsRegular = (clone $statsQuery)->where('is_admin', false)->count();

        return view('users.index', compact('users', 'statsTotal', 'statsAdmin', 'statsRegular'));
    }

    public function create()
    {
        return view('users.create', [
            'permissionModules' => Permission::modules(),
            'permissionActions' => Permission::actions(),
            'defaultPermissions' => Permission::operationalDefaults(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_admin' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:'.implode(',', Permission::allKeys()),
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $isAdmin = (bool) $request->boolean('is_admin');
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $isAdmin,
            'permissions' => $isAdmin ? null : Permission::sanitize($request->input('permissions', [])),
        ]);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        if ($user->isAgencyUser()) {
            return redirect()->route('agencies.users.edit', [$user->agency_id, $user]);
        }

        return view('users.edit', [
            'user' => $user,
            'permissionModules' => Permission::modules(),
            'permissionActions' => Permission::actions(),
            'defaultPermissions' => Permission::operationalDefaults(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->isAgencyUser()) {
            return redirect()->route('agencies.users.edit', [$user->agency_id, $user]);
        }
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'is_admin' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:'.implode(',', Permission::allKeys()),
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['confirmed', Password::defaults()];
        }

        $request->validate($rules, [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Ya existe otro usuario con ese correo.',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->is_admin = (bool) $request->boolean('is_admin');
        $user->permissions = $user->is_admin
            ? null
            : Permission::sanitize($request->input('permissions', []));
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        if ($user->isAgencyUser()) {
            return redirect()->route('users.index')->with('error', 'El acceso de un cliente se gestiona desde su ficha, no desde Usuarios.');
        }

        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
