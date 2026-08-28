<?php

namespace Tests\Feature\Auth;

use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    /**
     * Registrarse acá no crea solo un usuario -crea un negocio completo al
     * mismo tiempo (empresa + usuario dueño), por eso el formulario real
     * pide mucho más que nombre/correo/clave.
     */
    public function test_new_users_can_register(): void
    {
        // RegisteredUserController::store() exige que el rol "cliente" ya
        // exista -en la app real lo crea el seeder, acá hay que sembrarlo
        // a mano como en cualquier otro test de este proyecto.
        Rol::firstOrCreate(['nombre' => 'cliente']);

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '3000000000',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_name' => 'Licorera de Prueba',
            'nit' => '900123456',
            'business_type' => 'Licorera',
            'department' => 'Antioquia',
            'city' => 'Medellin',
            'terms' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('cliente.dashboard', absolute: false));

        $user = User::where('correo', 'test@example.com')->firstOrFail();
        $this->assertSame('Licorera de Prueba', Empresa::find($user->empresa_id)->nombre_negocio);
    }
}
