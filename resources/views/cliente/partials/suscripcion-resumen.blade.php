{{-- Tarjeta del plan vigente -usada tanto en el estado 100% activo (sola,
     sin formulario) como arriba del formulario de renovación cuando está
     "por vencer". Espera $planActual, $empresa, $planes, $diasRestantes en
     scope (ver App\Http\Controllers\Cliente\SuscripcionController::index()).

     Reusa el mismo lenguaje visual de las stat-card del Dashboard admin
     (icono en círculo de color + valor en Fraunces + etiqueta) -tres
     tarjetas independientes en fila, no una sola tarjeta con todo
     apilado, para que la página de Suscripción se sienta parte del mismo
     sistema en vez de un componente inventado aparte. --}}
@php
    $planNombre = $planActual ? ($planes[$planActual->plan]['label'] ?? $planActual->plan) : '—';
    $fechaVenceCorta = $empresa->fecha_vencimiento->locale('es')->translatedFormat('d M Y');
@endphp
<div class="suscripcion-stats">
    <div class="stat-card stat-card--sage">
        <div class="stat-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3 21 8v8l-9 5-9-5V8Z"/>
                <path d="M3 8l9 5 9-5M12 13v8"/>
            </svg>
        </div>
        <span class="stat-card__value">{{ $planNombre }}</span>
        <span class="stat-card__label">Plan actual</span>
    </div>

    <div class="stat-card stat-card--sand">
        <div class="stat-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="8.5"/>
                <path d="M12 7.5V12l3 2"/>
            </svg>
        </div>
        <span class="stat-card__value">{{ $diasRestantes }}</span>
        <span class="stat-card__label">{{ $diasRestantes === 1 ? 'día restante' : 'días restantes' }}</span>
    </div>

    <div class="stat-card stat-card--mist">
        <div class="stat-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3.5" y="5" width="17" height="16" rx="2"/>
                <path d="M8 3v4M16 3v4M3.5 10h17"/>
            </svg>
        </div>
        <span class="stat-card__value">{{ $fechaVenceCorta }}</span>
        <span class="stat-card__label">Vence el</span>
    </div>
</div>
