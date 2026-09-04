<x-cliente-layout title="Suscripción">

    {{-- No hay pasarela de pagos -el cliente transfiere por fuera del
         sistema y sube el comprobante; queda pendiente hasta que el admin
         lo revisa (App\Http\Controllers\Cliente\SuscripcionController).
         Esta es la ÚNICA página /cliente/* que sigue accesible cuando la
         empresa está bloqueada (vencida o suspendida) -ver
         App\Http\Middleware\EnsureSuscripcionActiva.

         Estados posibles (sin contar un pago pendiente de validar):
         activa de sobra -> resumen + botón opcional "Renovar antes" que
         despliega el formulario (empieza oculto); por vencer -> resumen +
         formulario ya abierto; vencida/suspendida -> mensaje del motivo +
         formulario ya abierto. El historial de pagos se ve siempre que
         haya alguno, sin importar el estado. --}}
    @php
        $mensajeEstado = match (true) {
            $estado === 'suspendido' => 'Tu cuenta fue suspendida. Puedes reportar un pago para que el administrador la reactive.',
            $estado === 'vencido' && ! $empresa->fecha_vencimiento => 'Tu cuenta todavía no tiene un plan activo.',
            $estado === 'vencido' => 'Tu suscripción venció el '.$empresa->fecha_vencimiento->format('d/m/Y').'.',
            $estado === 'por_vencer' => 'Tu plan vence el '.$empresa->fecha_vencimiento->format('d/m/Y').'. Puedes renovarlo desde ya.',
            default => 'Estás al día.',
        };
        $bloqueada = in_array($estado, ['vencido', 'suspendido'], true);
        $puedeRenovarAntes = ! $pagoPendiente && $estado === 'activo';
        $formularioAbierto = ! $pagoPendiente && in_array($estado, ['por_vencer', 'vencido', 'suspendido'], true);
        $muestraFormulario = $formularioAbierto || $puedeRenovarAntes;
    @endphp

    <div class="cliente-page-header cliente-reveal cliente-reveal-1">
        <div>
            <p class="cliente-page-header__eyebrow">Tu cuenta</p>
            <h1 class="cliente-page-header__title">Tu plan</h1>
        </div>
    </div>

    <div class="panel suscripcion-estado suscripcion-estado--{{ $bloqueada ? 'bloqueada' : 'ok' }} cliente-reveal cliente-reveal-2">
        {{ $mensajeEstado }}
    </div>

    @if (session('status') === 'pago-reportado')
        <div class="cliente-form-banner cliente-form-banner--success">Tu comprobante fue enviado. En cuanto el administrador lo revise, tu plan queda activo.</div>
    @elseif (session('status') === 'pago-ya-pendiente')
        <div class="cliente-form-banner">Ya tienes un pago pendiente de revisión -espera a que el administrador lo valide.</div>
    @endif

    @if ($pagoPendiente)
        <div class="panel cliente-reveal cliente-reveal-3">
            <h2 class="panel__title" style="margin-bottom: 18px;">Tu pago está siendo validado</h2>
            <p class="suscripcion-pendiente__hint">Ya recibimos tu comprobante -el administrador lo va a revisar pronto. No hace falta que hagas nada más.</p>

            <div class="cliente-form-grid" style="margin-top: 18px;">
                <div class="slide-over__field"><span>Plan reportado</span><strong>{{ $planes[$pagoPendiente->plan]['label'] ?? $pagoPendiente->plan }}</strong></div>
                <div class="slide-over__field"><span>Monto</span><strong>${{ number_format($pagoPendiente->monto, 0, ',', '.') }}</strong></div>
                <div class="slide-over__field"><span>Fecha del reporte</span><strong>{{ $pagoPendiente->fecha_pago->locale('es')->translatedFormat('d M Y, g:i a') }}</strong></div>
            </div>
        </div>
    @elseif (in_array($estado, ['activo', 'por_vencer'], true))
        <div class="suscripcion-stats-header cliente-reveal cliente-reveal-3">
            <h2 class="panel__title">Tu suscripción</h2>
            @if ($puedeRenovarAntes)
                <button type="button" class="cliente-btn-ghost" id="renovarAntesBtn">Renovar antes de tiempo</button>
            @endif
        </div>
        <div class="cliente-reveal cliente-reveal-3">
            @include('cliente.partials.suscripcion-resumen')
        </div>
    @endif

    @if ($muestraFormulario)
        @if ($motivoRechazo)
            <div class="panel suscripcion-rechazo cliente-reveal cliente-reveal-3">
                <h2 class="panel__title" style="margin-bottom: 10px;">Tu comprobante anterior fue rechazado</h2>
                <p>{{ $motivoRechazo }}</p>
                <p class="suscripcion-rechazo__hint">Puedes intentar de nuevo abajo.</p>
            </div>
        @endif

        <div class="panel cliente-reveal cliente-reveal-4" id="suscripcionFormPanel" {{ $puedeRenovarAntes ? 'hidden' : '' }}>
            <h2 class="panel__title" style="margin-bottom: 18px;">1. Elige tu plan</h2>

            <form method="POST" action="{{ route('cliente.suscripcion.store') }}" enctype="multipart/form-data" id="suscripcionForm">
                @csrf

                <div class="plan-grid">
                    @foreach ($planes as $id => $plan)
                        <label class="plan-card">
                            <input type="radio" name="plan" value="{{ $id }}" class="plan-card__radio" {{ old('plan', array_key_first($planes)) === $id ? 'checked' : '' }} required>
                            <span class="plan-card__name">{{ $plan['label'] }}</span>
                            <span class="plan-card__price">${{ number_format($plan['precio'], 0, ',', '.') }}</span>
                            <span class="plan-card__duration">{{ $plan['meses'] === 1 ? '1 mes' : $plan['meses'].' meses' }}</span>
                        </label>
                    @endforeach
                </div>
                @error('plan')
                    <span class="cliente-form-error">{{ $message }}</span>
                @enderror

                <h2 class="panel__title" style="margin: 28px 0 14px;">2. Realiza la transferencia</h2>
                <div class="pago-metodos">
                    <div class="pago-metodo-card">
                        <div class="pago-metodo-card__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="5" y="2.5" width="14" height="19" rx="2.5"/>
                                <path d="M9 18h6"/>
                            </svg>
                        </div>
                        <span class="pago-metodo-card__label">Nequi</span>
                        <div class="pago-metodo-card__valor-row">
                            <strong class="pago-metodo-card__valor" id="pagoValorNequi">321 391 9596</strong>
                            <button type="button" class="pago-metodo-card__copy" data-copy="3213919596" data-target="pagoValorNequi" aria-label="Copiar número de Nequi">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="9" y="9" width="12" height="12" rx="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="pago-metodo-card">
                        <div class="pago-metodo-card__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="8" cy="15.5" r="4.5"/>
                                <path d="M11.5 12 20 3.5M17 7l2 2M14 10l2 2"/>
                            </svg>
                        </div>
                        <span class="pago-metodo-card__label">Llave</span>
                        <div class="pago-metodo-card__valor-row">
                            <strong class="pago-metodo-card__valor" id="pagoValorLlave">1070942496</strong>
                            <button type="button" class="pago-metodo-card__copy" data-copy="1070942496" data-target="pagoValorLlave" aria-label="Copiar llave">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="9" y="9" width="12" height="12" rx="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <p class="pago-metodos__nombre">A nombre de <strong>Diego Alejandro Hernandez Sierra</strong></p>

                <h2 class="panel__title" style="margin: 28px 0 12px;">3. Sube el comprobante</h2>
                <div class="suscripcion-upload">
                    <label for="suscripcionComprobante" class="cliente-btn-primary suscripcion-upload__btn">Elegir archivo</label>
                    <input type="file" id="suscripcionComprobante" name="comprobante" accept="image/jpeg,image/png,application/pdf" hidden>
                    <span class="suscripcion-upload__filename" id="suscripcionComprobanteNombre">Ningún archivo elegido</span>
                </div>
                @error('comprobante')
                    <span class="cliente-form-error">{{ $message }}</span>
                @enderror
                <p class="suscripcion-upload__hint">Imagen (JPG/PNG) o PDF, máx. 4MB.</p>

                <div class="cliente-form-actions" style="margin-top: 20px;">
                    <button type="submit" class="cliente-btn-primary">Enviar comprobante</button>
                </div>
            </form>
        </div>
    @endif

    @include('cliente.partials.suscripcion-historial')

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/suscripcion.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/cliente/suscripcion.js') }}" defer></script>
    @endpush

</x-cliente-layout>
