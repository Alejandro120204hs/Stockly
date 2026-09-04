<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * Quien se registra por este formulario público siempre queda con el
     * rol "cliente" -el rol "admin" (Super Admin) se asigna manualmente en
     * la base de datos, nunca por aquí.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:usuarios,correo'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            'company_name' => ['required', 'string', 'max:255'],
            'nit' => ['required', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg', 'max:2048'],
            'business_type' => ['required', 'string', 'max:100'],
            'business_type_other' => ['required_if:business_type,Otro', 'nullable', 'string', 'max:100'],
            'department' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],

            'terms' => ['accepted'],
        ]);

        $clienteRol = Rol::where('nombre', 'cliente')->first();

        if (! $clienteRol) {
            throw ValidationException::withMessages([
                'email' => 'No se pudo completar el registro: falta configurar los roles del sistema.',
            ]);
        }

        $tipoNegocio = $request->business_type === 'Otro'
            ? $request->business_type_other
            : $request->business_type;

        $user = DB::transaction(function () use ($request, $clienteRol, $tipoNegocio) {
            $empresa = Empresa::create([
                'nombre_negocio' => $request->company_name,
                'logo_path' => $request->hasFile('logo') ? $request->file('logo')->store('empresas/logos', 'public') : null,
                'tipo_negocio' => $tipoNegocio,
                'nit' => $request->nit,
                // El correo y el teléfono de contacto de la empresa son
                // los mismos personales de quien se registra -no se piden
                // aparte.
                'correo_contacto' => $request->email,
                'telefono_contacto' => $request->phone,
                'departamento' => $request->department,
                'ciudad' => $request->city,
            ]);

            return User::create([
                'nombres' => $request->first_name,
                'apellidos' => $request->last_name,
                'correo' => $request->email,
                'telefono' => $request->phone,
                'password' => Hash::make($request->password),
                'empresa_id' => $empresa->id,
                'rol_id' => $clienteRol->id,
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('cliente.dashboard', absolute: false));
    }
}
