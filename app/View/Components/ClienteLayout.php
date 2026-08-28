<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class ClienteLayout extends Component
{
    public function __construct(
        public string $title = 'Dashboard',
    ) {
    }

    /**
     * Get the view / contents that represents the component.
     *
     * El sidebar y la topbar necesitan el usuario y su empresa en TODAS
     * las páginas del panel cliente (nombre del negocio, logo, nombre de
     * quien inició sesión) -se resuelven acá en vez de repetirlo en cada
     * controlador que devuelve una vista con <x-cliente-layout>.
     */
    public function render(): View
    {
        return view('layouts.cliente-layout', [
            'authUser' => auth()->user(),
            'empresa' => auth()->user()?->empresa,
        ]);
    }
}
