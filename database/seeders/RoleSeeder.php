<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Roles del sistema. "admin" es el Super Admin de DevSec -se asigna
     * manualmente en la base de datos, nunca desde el formulario público.
     * "cliente" es el único rol que puede salir del registro público.
     */
    public function run(): void
    {
        foreach (['admin', 'cliente'] as $nombre) {
            Rol::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
