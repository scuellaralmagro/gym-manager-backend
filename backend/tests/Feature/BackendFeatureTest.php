<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Clase;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\Sala;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pruebas de integración HTTP del backend.
 *
 * Cubren flujos completos con autenticación, middleware, controladores,
 * respuestas JSON y cambios reales sobre la base de datos de testing.
 */
class BackendFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Rol $adminRole;
    private Rol $trainerRole;
    private Rol $clientRole;
    private Sala $sala;
    private Actividad $actividad;
    private Usuario $admin;
    private Usuario $trainer;

    /**
     * Prepara el escenario base para las pruebas de integración.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id_rol' => 1, 'nombre' => 'Administrador'],
            ['id_rol' => 2, 'nombre' => 'Entrenador'],
            ['id_rol' => 3, 'nombre' => 'Cliente'],
        ]);

        $this->adminRole = Rol::findOrFail(1);
        $this->trainerRole = Rol::findOrFail(2);
        $this->clientRole = Rol::findOrFail(3);

        $this->sala = Sala::create([
            'nombre' => 'Sala Backend Feature Test',
            'capacidad_max' => 30,
        ]);

        $this->actividad = Actividad::create([
            'nombre' => 'Actividad Backend Feature Test',
            'descripcion' => 'Actividad creada para testing.',
        ]);

        $this->admin = $this->makeUser('admin.base@gymmanager.test', $this->adminRole);
        $this->trainer = $this->makeUser('trainer.base@gymmanager.test', $this->trainerRole);
    }

    /**
     * Comprueba que un cliente autenticado puede reservar una clase disponible.
     */
    public function test_cliente_autenticado_puede_reservar_clase_disponible(): void
    {
        $client = $this->makeUser('cliente.happy@gymmanager.test', $this->clientRole);
        $class = $this->makeClass($this->trainer, ['cupo_maximo' => 5]);

        $response = $this
            ->actingAs($client, 'sanctum')
            ->postJson('/api/reservas', ['id_clase' => $class->id_clase]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reservas', [
            'estado' => 'Activa',
            'id_usuario' => $client->id_usuario,
            'id_clase' => $class->id_clase,
        ]);
    }

    /**
     * Comprueba que una clase llena rechaza nuevas reservas.
     */
    public function test_cliente_no_puede_reservar_clase_con_cupo_lleno(): void
    {
        $existingClient = $this->makeUser('cliente.ocupado@gymmanager.test', $this->clientRole);
        $newClient = $this->makeUser('cliente.rechazado@gymmanager.test', $this->clientRole);
        $class = $this->makeClass($this->trainer, ['cupo_maximo' => 1]);

        Reserva::create([
            'estado' => 'Activa',
            'id_usuario' => $existingClient->id_usuario,
            'id_clase' => $class->id_clase,
        ]);

        $response = $this
            ->actingAs($newClient, 'sanctum')
            ->postJson('/api/reservas', ['id_clase' => $class->id_clase]);

        $response->assertStatus(422);
    }

    /**
     * Comprueba que un cliente no puede duplicar una reserva activa.
     */
    public function test_cliente_no_puede_duplicar_reserva_activa(): void
    {
        $client = $this->makeUser('cliente.duplicada@gymmanager.test', $this->clientRole);
        $class = $this->makeClass($this->trainer);

        Reserva::create([
            'estado' => 'Activa',
            'id_usuario' => $client->id_usuario,
            'id_clase' => $class->id_clase,
        ]);

        $response = $this
            ->actingAs($client, 'sanctum')
            ->postJson('/api/reservas', ['id_clase' => $class->id_clase]);

        $response->assertStatus(409);
    }

    /**
     * Comprueba que una reserva cancelada se reactiva sin crear otra fila.
     */
    public function test_cliente_reactiva_reserva_cancelada(): void
    {
        $client = $this->makeUser('cliente.reactivar@gymmanager.test', $this->clientRole);
        $class = $this->makeClass($this->trainer);

        $cancelled = Reserva::create([
            'estado' => 'Cancelada',
            'id_usuario' => $client->id_usuario,
            'id_clase' => $class->id_clase,
        ]);

        $response = $this
            ->actingAs($client, 'sanctum')
            ->postJson('/api/reservas', ['id_clase' => $class->id_clase]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('reserva.id_reserva', $cancelled->id_reserva);
    }

    /**
     * Comprueba que no se permiten reservas sobre clases pasadas.
     */
    public function test_cliente_no_puede_reservar_clase_pasada(): void
    {
        $client = $this->makeUser('cliente.pasada@gymmanager.test', $this->clientRole);
        $class = $this->makeClass($this->trainer, [
            'fecha' => now()->subDay()->toDateString(),
            'hora_inicio' => '08:00:00',
            'hora_fin' => '09:00:00',
        ]);

        $response = $this
            ->actingAs($client, 'sanctum')
            ->postJson('/api/reservas', ['id_clase' => $class->id_clase]);

        $response->assertStatus(422);
    }

    /**
     * Comprueba que un entrenador no puede leer asistencia de clases ajenas.
     */
    public function test_entrenador_no_puede_ver_asistencia_de_clase_ajena(): void
    {
        $ownerTrainer = $this->makeUser('trainer.owner@gymmanager.test', $this->trainerRole);
        $otherTrainer = $this->makeUser('trainer.other@gymmanager.test', $this->trainerRole);
        $class = $this->makeClass($ownerTrainer);

        $response = $this
            ->actingAs($otherTrainer, 'sanctum')
            ->getJson("/api/clases/{$class->id_clase}/asistencia");

        $response->assertStatus(403);
    }

    /**
     * Comprueba que un cliente no puede cancelar reservas de otro usuario.
     */
    public function test_cliente_no_puede_cancelar_reserva_ajena(): void
    {
        $ownerClient = $this->makeUser('cliente.owner@gymmanager.test', $this->clientRole);
        $otherClient = $this->makeUser('cliente.other@gymmanager.test', $this->clientRole);
        $class = $this->makeClass($this->trainer);

        $reservation = Reserva::create([
            'estado' => 'Activa',
            'id_usuario' => $ownerClient->id_usuario,
            'id_clase' => $class->id_clase,
        ]);

        $response = $this
            ->actingAs($otherClient, 'sanctum')
            ->patchJson("/api/reservas/{$reservation->id_reserva}/cancelar");

        $response->assertStatus(404);
    }

    /**
     * Comprueba que los KPIs responden con ceros cuando no hay reservas.
     */
    public function test_admin_recibe_kpis_en_cero_cuando_no_hay_reservas(): void
    {
        $this->makeClass($this->trainer, [
            'fecha' => '2099-01-10',
            'cupo_maximo' => 12,
        ]);

        $response = $this
            ->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/informes?mes=1&anio=2099');

        $response
            ->assertOk()
            ->assertJsonPath('kpis.tasa_ocupacion_promedio', 0)
            ->assertJsonPath('kpis.indice_cancelaciones', 0);
    }

    /**
     * Comprueba la creación forzada de reservas y el conflicto por duplicado.
     */
    public function test_admin_crea_reserva_forzada_y_bloquea_duplicado(): void
    {
        $client = $this->makeUser('cliente.forzada@gymmanager.test', $this->clientRole);
        $class = $this->makeClass($this->trainer);

        $createResponse = $this
            ->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reservas', [
                'id_usuario' => $client->id_usuario,
                'id_clase' => $class->id_clase,
            ]);

        $createResponse->assertStatus(201);

        $duplicateResponse = $this
            ->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reservas', [
                'id_usuario' => $client->id_usuario,
                'id_clase' => $class->id_clase,
            ]);

        $duplicateResponse->assertStatus(409);
    }

    /**
     * Comprueba que una reserva forzada reactiva reservas canceladas.
     */
    public function test_admin_reactiva_reserva_cancelada_forzada(): void
    {
        $client = $this->makeUser('cliente.reactivate@gymmanager.test', $this->clientRole);
        $class = $this->makeClass($this->trainer);

        $cancelled = Reserva::create([
            'estado' => 'Cancelada',
            'id_usuario' => $client->id_usuario,
            'id_clase' => $class->id_clase,
        ]);

        $response = $this
            ->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/reservas', [
                'id_usuario' => $client->id_usuario,
                'id_clase' => $class->id_clase,
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.id_reserva', $cancelled->id_reserva);
    }

    // FUNCIONES AUXILIARES

    /**
     * Crea un usuario autenticable para el rol indicado.
     */
    private function makeUser(string $email, Rol $role): Usuario
    {
        $roleSuffix = strtolower($role->nombre);

        return Usuario::create([
            'nombre' => "Usuario {$roleSuffix}",
            'apellidos' => 'QA Flow',
            'email' => $email,
            'hash_password' => 'Password1!',
            'id_rol' => $role->id_rol,
        ]);
    }

    /**
     * Crea una clase válida permitiendo sobrescribir campos concretos.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makeClass(Usuario $trainer, array $overrides = []): Clase
    {
        return Clase::create(array_merge([
            'fecha' => now()->addWeek()->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'cupo_maximo' => 10,
            'id_sala' => $this->sala->id_sala,
            'id_usuario' => $trainer->id_usuario,
            'id_actividad' => $this->actividad->id_actividad,
        ], $overrides));
    }
}
