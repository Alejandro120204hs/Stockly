<x-cliente-layout title="Mi perfil">

    {{-- Mi perfil del negocio cliente -mismo patrón que admin.perfil
         (App\Http\Controllers\Admin\ProfileController), con una diferencia
         importante: acá la "foto" SÍ se guarda de verdad, porque es el
         logo de la EMPRESA (mismo storage que usa el registro público),
         no una foto personal sin backend todavía. --}}
    @php
        $iniciales = strtoupper(mb_substr($user->nombres, 0, 1).mb_substr($user->apellidos, 0, 1));
        $logoUrl = $empresa->logoUrl();
    @endphp

    <div class="cliente-page-header cliente-reveal cliente-reveal-1">
        <div>
            <p class="cliente-page-header__eyebrow">Tu cuenta</p>
            <h1 class="cliente-page-header__title">Mi perfil</h1>
        </div>
    </div>

    <div class="perfil-grid cliente-reveal cliente-reveal-2">
        <div class="panel perfil-photo-panel">
            <h2 class="panel__title" style="margin-bottom: 18px;">Logo del negocio</h2>

            @if (session('status') === 'logo-actualizado')
                <div class="cliente-form-banner cliente-form-banner--success">El logo se actualizó correctamente.</div>
            @endif

            <div class="perfil-photo">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $empresa->nombre_negocio }}" class="perfil-photo__avatar perfil-photo__avatar--img">
                @else
                    <div class="perfil-photo__avatar">{{ $iniciales }}</div>
                @endif

                <form method="POST" action="{{ route('cliente.perfil.logo') }}" enctype="multipart/form-data" id="perfilLogoForm">
                    @csrf
                    <label for="perfilLogoInput" class="perfil-photo__btn">Cambiar logo</label>
                    <input type="file" id="perfilLogoInput" name="logo" accept="image/png,image/jpeg,image/svg+xml" hidden>
                </form>
                @error('logo')
                    <span class="cliente-form-error">{{ $message }}</span>
                @enderror
                <p class="perfil-photo__hint">JPG, PNG o SVG, máx. 2MB. Aparece en el sidebar y en los recibos de venta.</p>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel__title" style="margin-bottom: 18px;">Información personal</h2>

            @if (session('status') === 'perfil-actualizado')
                <div class="cliente-form-banner cliente-form-banner--success">Tus datos se actualizaron correctamente.</div>
            @endif

            <form method="POST" action="{{ route('cliente.perfil.update') }}" novalidate>
                @csrf
                @method('PATCH')

                <div class="cliente-form-grid">
                    <div class="cliente-form-field">
                        <label for="perfilNombres" class="cliente-label">Nombres</label>
                        <input type="text" id="perfilNombres" name="nombres" class="cliente-input" value="{{ old('nombres', $user->nombres) }}">
                        @error('nombres')
                            <span class="cliente-form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="cliente-form-field">
                        <label for="perfilApellidos" class="cliente-label">Apellidos</label>
                        <input type="text" id="perfilApellidos" name="apellidos" class="cliente-input" value="{{ old('apellidos', $user->apellidos) }}">
                        @error('apellidos')
                            <span class="cliente-form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="cliente-form-field">
                    <label for="perfilCorreo" class="cliente-label">Correo electrónico</label>
                    <input type="email" id="perfilCorreo" name="correo" class="cliente-input" value="{{ old('correo', $user->correo) }}">
                    @error('correo')
                        <span class="cliente-form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="cliente-form-field">
                    <label for="perfilTelefono" class="cliente-label">Teléfono</label>
                    <input type="tel" id="perfilTelefono" name="telefono" class="cliente-input" value="{{ old('telefono', $user->telefono) }}">
                    @error('telefono')
                        <span class="cliente-form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="cliente-form-actions">
                    <button type="submit" class="cliente-btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div class="panel cliente-reveal cliente-reveal-3" style="margin-top: 20px;">
        <h2 class="panel__title" style="margin-bottom: 18px;">Cambiar contraseña</h2>

        @if (session('status') === 'password-actualizada')
            <div class="cliente-form-banner cliente-form-banner--success">Tu contraseña se actualizó correctamente.</div>
        @endif

        <form method="POST" action="{{ route('cliente.perfil.password') }}" id="perfilPasswordForm" novalidate style="max-width: 420px;">
            @csrf
            @method('PUT')

            <div class="cliente-form-field cliente-form-field--password">
                <label for="perfilClaveActual" class="cliente-label">Contraseña actual</label>
                <input type="password" id="perfilClaveActual" name="clave_actual" class="cliente-input">
                <button type="button" class="cliente-form-toggle-password" aria-label="Mostrar contraseña" aria-pressed="false">
                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3l18 18"/>
                        <path d="M10.6 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a15.6 15.6 0 0 1-3.1 4.1M6.2 6.2C3.6 8 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 4.2-.9"/>
                        <path d="M9.5 9.9A3 3 0 0 0 12 15a3 3 0 0 0 2.1-.9"/>
                    </svg>
                </button>
                @error('clave_actual')
                    <span class="cliente-form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="cliente-form-field cliente-form-field--password">
                <label for="perfilClaveNueva" class="cliente-label">Nueva contraseña</label>
                <input type="password" id="perfilClaveNueva" name="clave_nueva" class="cliente-input">
                <button type="button" class="cliente-form-toggle-password" aria-label="Mostrar contraseña" aria-pressed="false">
                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3l18 18"/>
                        <path d="M10.6 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a15.6 15.6 0 0 1-3.1 4.1M6.2 6.2C3.6 8 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 4.2-.9"/>
                        <path d="M9.5 9.9A3 3 0 0 0 12 15a3 3 0 0 0 2.1-.9"/>
                    </svg>
                </button>
                @error('clave_nueva')
                    <span class="cliente-form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="cliente-form-field cliente-form-field--password">
                <label for="perfilClaveConfirmar" class="cliente-label">Confirmar nueva contraseña</label>
                <input type="password" id="perfilClaveConfirmar" name="clave_nueva_confirmation" class="cliente-input">
                <button type="button" class="cliente-form-toggle-password" aria-label="Mostrar contraseña" aria-pressed="false">
                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3l18 18"/>
                        <path d="M10.6 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a15.6 15.6 0 0 1-3.1 4.1M6.2 6.2C3.6 8 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 4.2-.9"/>
                        <path d="M9.5 9.9A3 3 0 0 0 12 15a3 3 0 0 0 2.1-.9"/>
                    </svg>
                </button>
                <span class="cliente-form-error" id="perfilClaveMismatch" hidden>Las contraseñas no coinciden.</span>
            </div>

            <div class="cliente-form-actions">
                <button type="submit" class="cliente-btn-primary">Actualizar contraseña</button>
            </div>
        </form>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/perfil.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/cliente/perfil.js') }}" defer></script>
    @endpush

</x-cliente-layout>
