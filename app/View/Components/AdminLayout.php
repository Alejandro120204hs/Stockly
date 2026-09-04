<?php

namespace App\View\Components;

use App\Models\PagoSuscripcion;
use Illuminate\View\Component;
use Illuminate\View\View;

class AdminLayout extends Component
{
    public function __construct(
        public string $title = 'Dashboard',
    ) {
    }

    /**
     * Get the view / contents that represents the component.
     *
     * El conteo de pagos pendientes se resuelve acá -así el badge del
     * sidebar (junto a "Pagos y suscripciones") aparece en TODAS las
     * páginas del panel admin, no solo en la de Pagos.
     */
    public function render(): View
    {
        return view('layouts.admin-layout', [
            'pagosPendientes' => PagoSuscripcion::where('estado', 'pago_recibido')->count(),
        ]);
    }
}
