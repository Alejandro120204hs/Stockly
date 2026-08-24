<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AdminLayout extends Component
{
    public function __construct(
        public string $title = 'Dashboard',
        // Mock por ahora: 3 pagos pendientes, igual en todas las vistas.
        // Es el valor por defecto acá -así ninguna vista tiene que
        // repetirlo (ni puede olvidarlo, como pasó con "Mi perfil").
        public int $pendingPayments = 3,
    ) {
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.admin-layout');
    }
}
