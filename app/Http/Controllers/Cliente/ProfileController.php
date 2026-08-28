<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Muestra "Mi perfil" con los datos reales del usuario Y de su empresa
     * (el logo vive en la empresa, no en el usuario -lo comparten todas
     * las cuentas de ese mismo negocio).
     */
    public function edit(Request $request): View
    {
        return view('cliente.perfil', [
            'user' => $request->user(),
            'empresa' => $request->user()->empresa,
        ]);
    }

    /**
     * Actualiza nombres, apellidos, correo y teléfono. Mismo patrón exacto
     * que Admin\ProfileController.
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

    /**
     * Reemplaza el logo de la empresa (mismo disco/carpeta que usa el
     * registro público). Borra el archivo anterior para no ir dejando
     * huérfanos cada vez que cambian el logo.
     */
    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,svg', 'max:2048'],
        ]);

        $empresa = $request->user()->empresa;
        $logoAnterior = $empresa->logo_path;

        $empresa->update([
            'logo_path' => $request->file('logo')->store('empresas/logos', 'public'),
        ]);

        if ($logoAnterior) {
            Storage::disk('public')->delete($logoAnterior);
        }

        return back()->with('status', 'logo-actualizado');
    }
}
