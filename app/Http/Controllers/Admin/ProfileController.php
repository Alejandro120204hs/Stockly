<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Muestra la vista "Mi perfil" con los datos reales del admin logueado.
     */
    public function edit(Request $request): View
    {
        return view('admin.perfil', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Actualiza nombres, apellidos, correo y teléfono.
     */
    public function updateInfo(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'correo' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('usuarios', 'correo')->ignore($user->id),
            ],
            'telefono' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($validated);

        return back()->with('status', 'perfil-actualizado');
    }

    /**
     * Actualiza la contraseña, verificando la actual primero.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'clave_actual' => ['required', 'current_password'],
            'clave_nueva' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ], [], [
            'clave_actual' => 'contraseña actual',
            'clave_nueva' => 'nueva contraseña',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->clave_nueva),
        ]);

        return back()->with('status', 'password-actualizada');
    }
}
