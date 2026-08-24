<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        User::factory()->create([
            'nombres' => 'Test',
            'apellidos' => 'User',
            'correo' => 'test@example.com',
            'rol_id' => Rol::where('nombre', 'cliente')->first()->id,
        ]);
    }
}
