<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión — Stockly</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|work-sans:400,500,600,700" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset_v('assets/css/auth.css') }}">
</head>
<body class="auth-body">

    <div class="auth-background">
        <canvas id="authCanvas" class="auth-canvas"></canvas>
    </div>

    <div class="auth-page">
        <div class="auth-card">
            <a href="{{ url('/') }}" class="auth-card__brand auth-reveal auth-reveal-1">
                <svg class="auth-card__brand-mark" viewBox="0 0 32 32" fill="none">
                    <path d="M16 3 27 9v14L16 29 5 23V9Z" stroke="#4A7C6F" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M5 9 16 15 27 9M16 15v14" stroke="#C9B99A" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
                Stockly
            </a>

            <h1 class="auth-card__title auth-reveal auth-reveal-2">Inicia sesión</h1>
            <p class="auth-card__subtitle auth-reveal auth-reveal-3">Entra a tu cuenta para seguir controlando tu inventario.</p>

            @if (session('status'))
                <div class="auth-status auth-reveal auth-reveal-3">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
                @csrf

                <div class="form-field auth-reveal auth-reveal-4 {{ $errors->has('email') ? 'has-error' : '' }}">
                    <input
                        id="email"
                        name="email"
                        type="email"
                        class="form-input"
                        placeholder=" "
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                    >
                    <label for="email" class="form-label">Correo electrónico</label>
                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-field form-field--password auth-reveal auth-reveal-5 {{ $errors->has('password') ? 'has-error' : '' }}">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="form-input"
                        placeholder=" "
                        required
                        autocomplete="current-password"
                    >
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

                <div class="auth-form__row auth-reveal auth-reveal-6">
                    <label class="auth-form__remember">
                        <input type="checkbox" name="remember" class="auth-form__checkbox">
                        <span class="auth-form__checkbox-box"></span>
                        Recordarme
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="auth-form__forgot">¿Olvidaste tu contraseña?</a>
                    @endif
                </div>

                <button type="submit" class="auth-button auth-button--primary auth-reveal auth-reveal-7">
                    Iniciar sesión
                </button>

                @if (Route::has('register'))
                    <p class="auth-card__switch auth-reveal auth-reveal-8">
                        ¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a>
                    </p>
                @endif
            </form>
        </div>
    </div>

    <script src="{{ asset_v('assets/js/auth.js') }}" defer></script>
</body>
</html>
