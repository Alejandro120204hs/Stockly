<?php

namespace Tests\Feature\Cliente;

use App\Models\Empresa;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileCrudTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioCliente(): User
    {
        $rol = Rol::firstOrCreate(['nombre' => 'cliente']);

        $usuario = User::factory()->create([
            'rol_id' => $rol->id,
            'empresa_id' => Empresa::factory()->create()->id,
        ]);

        $this->actingAs($usuario);

        return $usuario;
    }

    public function test_ver_mi_perfil_muestra_los_datos_reales_del_usuario_y_su_empresa(): void
    {
        $usuario = $this->crearUsuarioCliente();

        $this->get('/cliente/perfil')
            ->assertOk()
            ->assertSee($usuario->nombres)
            ->assertSee($usuario->empresa->nombre_negocio);
    }

    public function test_actualizar_informacion_personal_guarda_los_nuevos_datos(): void
    {
        $usuario = $this->crearUsuarioCliente();

        $response = $this->patch('/cliente/perfil', [
            'nombres' => 'Nuevo Nombre',
            'apellidos' => 'Nuevo Apellido',
            'correo' => 'nuevo@correo.com',
            'telefono' => '3009998888',
        ]);

        $response->assertRedirect();
        $usuario->refresh();
        $this->assertSame('Nuevo Nombre', $usuario->nombres);
        $this->assertSame('nuevo@correo.com', $usuario->correo);
    }

    public function test_no_se_puede_cambiar_el_correo_al_de_otro_usuario_ya_existente(): void
    {
        $otro = User::factory()->create(['correo' => 'ocupado@correo.com']);
        $this->crearUsuarioCliente();

        $response = $this->patch('/cliente/perfil', [
            'nombres' => 'Alguien',
            'apellidos' => 'Cualquiera',
            'correo' => 'ocupado@correo.com',
            'telefono' => '3000000000',
        ]);

        $response->assertSessionHasErrors('correo');
    }

    public function test_actualizar_password_requiere_la_actual_correcta(): void
    {
        $usuario = $this->crearUsuarioCliente();
        $usuario->password = Hash::make('ClaveVieja123!');
        $usuario->save();

        $response = $this->put('/cliente/perfil/password', [
            'clave_actual' => 'claveIncorrecta',
            'clave_nueva' => 'ClaveNueva123!',
            'clave_nueva_confirmation' => 'ClaveNueva123!',
        ]);

        $response->assertSessionHasErrors('clave_actual');
    }

    public function test_actualizar_password_con_la_actual_correcta_la_cambia(): void
    {
        $usuario = $this->crearUsuarioCliente();
        $usuario->password = Hash::make('ClaveVieja123!');
        $usuario->save();

        $response = $this->put('/cliente/perfil/password', [
            'clave_actual' => 'ClaveVieja123!',
            'clave_nueva' => 'ClaveNueva123!',
            'clave_nueva_confirmation' => 'ClaveNueva123!',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('ClaveNueva123!', $usuario->fresh()->password));
    }

    public function test_subir_logo_lo_guarda_en_la_empresa_del_usuario(): void
    {
        Storage::fake('public');
        $usuario = $this->crearUsuarioCliente();

        $archivo = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->post('/cliente/perfil/logo', ['logo' => $archivo]);

        $response->assertRedirect();
        $empresa = $usuario->empresa->fresh();
        $this->assertNotNull($empresa->logo_path);
        Storage::disk('public')->assertExists($empresa->logo_path);
    }

    public function test_subir_un_logo_nuevo_borra_el_archivo_del_logo_anterior(): void
    {
        Storage::fake('public');
        $usuario = $this->crearUsuarioCliente();

        $this->post('/cliente/perfil/logo', ['logo' => UploadedFile::fake()->image('logo1.png')]);
        $rutaAnterior = $usuario->empresa->fresh()->logo_path;

        $this->post('/cliente/perfil/logo', ['logo' => UploadedFile::fake()->image('logo2.png')]);
        $rutaNueva = $usuario->empresa->fresh()->logo_path;

        $this->assertNotSame($rutaAnterior, $rutaNueva);
        Storage::disk('public')->assertMissing($rutaAnterior);
        Storage::disk('public')->assertExists($rutaNueva);
    }

    public function test_no_se_puede_subir_un_archivo_que_no_sea_imagen_como_logo(): void
    {
        Storage::fake('public');
        $this->crearUsuarioCliente();

        $response = $this->post('/cliente/perfil/logo', [
            'logo' => UploadedFile::fake()->create('documento.pdf', 500, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('logo');
    }

    public function test_un_admin_sin_empresa_no_puede_entrar_al_perfil_de_cliente(): void
    {
        $rolAdmin = Rol::firstOrCreate(['nombre' => 'admin']);
        $admin = User::factory()->create(['rol_id' => $rolAdmin->id, 'empresa_id' => null]);
        $this->actingAs($admin);

        $this->get('/cliente/perfil')->assertForbidden();
    }
}
