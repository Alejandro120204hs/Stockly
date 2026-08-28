<?php

namespace Tests\Feature\Auth;

use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        // RolFactory elige "cliente" o "admin" al azar -para probar el
        // redirect de forma determinística hay que fijar el rol a mano,
        // en vez de dejarlo en manos de User::factory()->create().
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);
        $user = User::factory()->create([
            'rol_id' => $rol->id,
            'empresa_id' => Empresa::factory()->create()->id,
        ]);

        $response = $this->post('/login', [
            'email' => $user->correo,
            'password' => 'password',
        ]);

        // AuthenticatedSessionController redirige según el rol -un usuario
        // "cliente" cae en su dashboard propio, nunca en el genérico
        // "/dashboard".
        $this->assertAuthenticated();
        $response->assertRedirect(route('cliente.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->correo,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
