<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta — Stockly</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|work-sans:400,500,600,700" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset_v('assets/css/auth.css') }}">
</head>
<body class="auth-body">

    <div class="auth-background" aria-hidden="true">
        <canvas id="authCanvas" class="auth-canvas"></canvas>
    </div>

    @php
        // Departamento y Ciudad ya no se escriben a mano acá: los llena
        // register-wizard.js a partir de public/assets/js/colombia-locations.js
        // (dataset real de departamentos/municipios), para que ambos selects
        // usen exactamente la misma fuente de datos.
        $tiposNegocio = ['Licorera', 'Ropa y accesorios', 'Comestibles/víveres', 'Ferretería', 'Farmacia', 'Otro'];
    @endphp

    <div class="auth-page">
        <div class="auth-card auth-card--wizard wizard">
            <a href="{{ url('/') }}" class="auth-card__brand auth-reveal auth-reveal-1">
                <svg class="auth-card__brand-mark" viewBox="0 0 32 32" fill="none">
                    <path d="M16 3 27 9v14L16 29 5 23V9Z" stroke="#4A7C6F" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M5 9 16 15 27 9M16 15v14" stroke="#C9B99A" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
                Stockly
            </a>

            <!-- ==========================================================
                 INDICADOR DE PROGRESO
                 ========================================================== -->
            <div class="wizard-progress auth-reveal auth-reveal-2">
                <button type="button" class="wizard-progress__step is-active" data-step-index="0">
                    <span class="wizard-progress__circle">
                        <span class="step-number">1</span>
                        <svg class="icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m5 12 5 5 9-9"/>
                        </svg>
                    </span>
                    <span class="wizard-progress__label">Datos personales</span>
                </button>

                <span class="wizard-progress__connector"></span>

                <button type="button" class="wizard-progress__step" data-step-index="1">
                    <span class="wizard-progress__circle">
                        <span class="step-number">2</span>
                        <svg class="icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m5 12 5 5 9-9"/>
                        </svg>
                    </span>
                    <span class="wizard-progress__label">Empresa</span>
                </button>

                <span class="wizard-progress__connector"></span>

                <button type="button" class="wizard-progress__step" data-step-index="2">
                    <span class="wizard-progress__circle">
                        <span class="step-number">3</span>
                        <svg class="icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m5 12 5 5 9-9"/>
                        </svg>
                    </span>
                    <span class="wizard-progress__label">Confirmación</span>
                </button>
            </div>

            <form method="POST" action="{{ route('register') }}" class="auth-form" enctype="multipart/form-data" novalidate>
                @csrf

                <!-- ======================================================
                     PASO 1: DATOS PERSONALES
                     ====================================================== -->
                <section class="wizard-step is-active" data-step="1">
                    <h1 class="wizard-step__title">Datos personales</h1>
                    <p class="wizard-step__subtitle">Empecemos por saber quién eres.</p>

                    <div class="form-row">
                        <div class="form-field auth-reveal auth-reveal-3">
                            <input id="first_name" name="first_name" type="text" class="form-input" placeholder=" " value="{{ old('first_name') }}" required autofocus autocomplete="given-name">
                            <label for="first_name" class="form-label">Nombres</label>
                        </div>

                        <div class="form-field auth-reveal auth-reveal-3">
                            <input id="last_name" name="last_name" type="text" class="form-input" placeholder=" " value="{{ old('last_name') }}" required autocomplete="family-name">
                            <label for="last_name" class="form-label">Apellidos</label>
                        </div>
                    </div>

                    <div class="form-field auth-reveal auth-reveal-4 {{ $errors->has('email') ? 'has-error' : '' }}">
                        <input id="email" name="email" type="email" class="form-input" placeholder=" " value="{{ old('email') }}" required autocomplete="username">
                        <label for="email" class="form-label">Correo electrónico</label>
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field auth-reveal auth-reveal-4">
                        <input id="phone" name="phone" type="tel" class="form-input" placeholder=" " value="{{ old('phone') }}" required autocomplete="tel">
                        <label for="phone" class="form-label">Número de teléfono</label>
                    </div>

                    <div class="form-field form-field--password auth-reveal auth-reveal-5 {{ $errors->has('password') ? 'has-error' : '' }}">
                        <input id="password" name="password" type="password" class="form-input" placeholder=" " required autocomplete="new-password">
                        <label for="password" class="form-label">Contraseña</label>
                        <button type="button" class="auth-form__toggle-password" aria-label="Mostrar contraseña" aria-pressed="false">
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
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field form-field--password auth-reveal auth-reveal-6">
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" placeholder=" " required autocomplete="new-password">
                        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                        <button type="button" class="auth-form__toggle-password" aria-label="Mostrar contraseña" aria-pressed="false">
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
                    </div>

                    <div class="wizard-nav auth-reveal auth-reveal-7">
                        <button type="button" class="auth-button auth-button--primary" data-wizard-next>Siguiente</button>
                    </div>
                </section>

                <!-- ======================================================
                     PASO 2: DATOS DE LA EMPRESA
                     ====================================================== -->
                <section class="wizard-step" data-step="2" hidden>
                    <h1 class="wizard-step__title">Datos de la empresa</h1>
                    <p class="wizard-step__subtitle">Cuéntanos sobre el negocio que vas a administrar.</p>

                    <div class="form-field">
                        <input id="company_name" name="company_name" type="text" class="form-input" placeholder=" " value="{{ old('company_name') }}" required autocomplete="organization">
                        <label for="company_name" class="form-label">Nombre de la empresa</label>
                    </div>

                    <div class="form-field">
                        <input id="nit" name="nit" type="text" class="form-input" placeholder=" " value="{{ old('nit') }}" required inputmode="numeric">
                        <label for="nit" class="form-label">NIT</label>
                    </div>

                    <div class="form-field form-field--file">
                        <label class="form-static-label">Logo del negocio (opcional)</label>
                        <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/svg+xml" class="form-file-input">
                        <label for="logo" class="form-file-picker">
                            <span id="logo-file-name">Elegir imagen (JPG, PNG o SVG, máx. 2MB)</span>
                        </label>
                        @error('logo')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-field form-field--select">
                        <label for="business_type" class="form-static-label">Tipo de negocio</label>
                        <select id="business_type" name="business_type" class="form-select" required>
                            <option value="" disabled {{ old('business_type') ? '' : 'selected' }}>Selecciona una opción</option>
                            @foreach ($tiposNegocio as $tipo)
                                <option value="{{ $tipo }}" @selected(old('business_type') === $tipo)>{{ $tipo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field" style="display: {{ old('business_type') === 'Otro' ? '' : 'none' }};">
                        <input id="business_type_other" name="business_type_other" type="text" class="form-input" placeholder=" " value="{{ old('business_type_other') }}" {{ old('business_type') === 'Otro' ? 'required' : '' }}>
                        <label for="business_type_other" class="form-label">Especifica el tipo de negocio</label>
                    </div>

                    <!-- Select dependiente: register-wizard.js llena "department"
                         desde colombia-locations.js, y llena "city" según el
                         departamento elegido. data-old-* conserva el valor
                         si la página vuelve a cargar con errores del servidor. -->
                    <div class="form-row" data-old-department="{{ old('department') }}" data-old-city="{{ old('city') }}">
                        <div class="form-field form-field--select">
                            <label for="department" class="form-static-label">Departamento</label>
                            <select id="department" name="department" class="form-select" required>
                                <option value="" disabled selected>Selecciona</option>
                            </select>
                        </div>

                        <div class="form-field form-field--select">
                            <label for="city" class="form-static-label">Ciudad / Municipio</label>
                            <select id="city" name="city" class="form-select" required disabled>
                                <option value="" disabled selected>Elige un departamento</option>
                            </select>
                        </div>
                    </div>

                    <div class="wizard-nav">
                        <button type="button" class="auth-button auth-button--ghost" data-wizard-back>Atrás</button>
                        <button type="button" class="auth-button auth-button--primary" data-wizard-next>Siguiente</button>
                    </div>
                </section>

                <!-- ======================================================
                     PASO 3: CONFIRMACIÓN
                     ====================================================== -->
                <section class="wizard-step" data-step="3" hidden>
                    <h1 class="wizard-step__title">Confirmación</h1>
                    <p class="wizard-step__subtitle">Revisa que todo esté correcto antes de crear tu cuenta.</p>

                    <div class="wizard-summary">
                        <div class="wizard-summary__group">
                            <h2 class="wizard-summary__group-title">Datos personales</h2>
                            <dl class="wizard-summary__list">
                                <div class="wizard-summary__row"><dt>Nombre</dt><dd id="summary-full-name">—</dd></div>
                                <div class="wizard-summary__row"><dt>Correo</dt><dd id="summary-email">—</dd></div>
                                <div class="wizard-summary__row"><dt>Teléfono</dt><dd id="summary-phone">—</dd></div>
                            </dl>
                        </div>

                        <div class="wizard-summary__group">
                            <h2 class="wizard-summary__group-title">Datos de la empresa</h2>
                            <dl class="wizard-summary__list">
                                <div class="wizard-summary__row"><dt>Empresa</dt><dd id="summary-company">—</dd></div>
                                <div class="wizard-summary__row"><dt>NIT</dt><dd id="summary-nit">—</dd></div>
                                <div class="wizard-summary__row"><dt>Tipo de negocio</dt><dd id="summary-business-type">—</dd></div>
                                <div class="wizard-summary__row"><dt>Ubicación</dt><dd id="summary-location">—</dd></div>
                            </dl>
                        </div>
                    </div>

                    <label class="wizard-terms">
                        <input type="checkbox" id="terms" name="terms" class="auth-form__checkbox" required>
                        <span class="auth-form__checkbox-box"></span>
                        <span>Acepto los <a href="#">términos y condiciones</a> de Stockly.</span>
                    </label>

                    <div class="wizard-nav">
                        <button type="button" class="auth-button auth-button--ghost" data-wizard-back>Atrás</button>
                        <button type="submit" class="auth-button auth-button--primary">Crear cuenta</button>
                    </div>
                </section>

                <p class="auth-card__switch auth-reveal auth-reveal-9">
                    ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>
                </p>
            </form>
        </div>
    </div>

    <script src="{{ asset_v('assets/js/auth.js') }}" defer></script>
    <script src="{{ asset_v('assets/js/colombia-locations.js') }}" defer></script>
    <script src="{{ asset_v('assets/js/register-wizard.js') }}" defer></script>
</body>
</html>
