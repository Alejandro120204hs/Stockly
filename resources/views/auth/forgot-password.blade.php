<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña — Stockly</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|work-sans:400,500,600,700" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}">
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

            <h1 class="auth-card__title auth-reveal auth-reveal-2">Recupera tu contraseña</h1>
            <p class="auth-card__subtitle auth-reveal auth-reveal-3">
                Escribe tu correo y te enviaremos un enlace para elegir una contraseña nueva.
            </p>

            @if (session('status'))
                <div class="auth-status auth-reveal auth-reveal-3">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="auth-form" novalidate>
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
                    >
                    <label for="email" class="form-label">Correo electrónico</label>
                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="auth-button auth-button--primary auth-reveal auth-reveal-5">
                    Enviar instrucciones
                </button>

                <p class="auth-card__switch auth-reveal auth-reveal-6">
                    <a href="{{ route('login') }}">Volver a iniciar sesión</a>
                </p>
            </form>
        </div>
    </div>

    <script src="{{ asset('assets/js/auth.js') }}" defer></script>
</body>
</html>
