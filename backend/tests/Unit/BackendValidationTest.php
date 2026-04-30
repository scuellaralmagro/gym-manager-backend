<?php

namespace Tests\Unit;

use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\StoreAdminReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Actividad;
use App\Models\Clase;
use App\Models\Rol;
use App\Models\Sala;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Pruebas unitarias de validación del backend.
 *
 * Comprueban las reglas de los FormRequests directamente, sin ejecutar los
 * controladores ni simular peticiones HTTP completas.
 */
class BackendValidationTest extends TestCase
{
    use RefreshDatabase;

    private Rol $trainerRole;
    private Rol $clientRole;
    private Sala $sala;
    private Actividad $actividad;
    private Usuario $trainer;
    private Usuario $client;

    /**
     * Prepara el escenario base para las pruebas.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id_rol' => 1, 'nombre' => 'Administrador'],
            ['id_rol' => 2, 'nombre' => 'Entrenador'],
            ['id_rol' => 3, 'nombre' => 'Cliente'],
        ]);

        $this->trainerRole = Rol::findOrFail(2);
        $this->clientRole = Rol::findOrFail(3);

        $this->sala = Sala::create([
            'nombre' => 'Sala Backend Unit Test',
            'capacidad_max' => 30,
        ]);

        $this->actividad = Actividad::create([
            'nombre' => 'Actividad Backend Unit Test',
            'descripcion' => 'Actividad creada para testing unitario.',
        ]);

        $this->trainer = $this->makeUser('trainer.unit@gymmanager.test', $this->trainerRole);
        $this->client = $this->makeUser('client.unit@gymmanager.test', $this->clientRole);
    }

    /**
     * Comprueba que StoreClassRequest rechaza cupos negativos o no numéricos.
     */
    public function test_crear_clase_rechaza_cupo_negativo_o_texto(): void
    {
        foreach ([-1, 'muchas'] as $invalidCapacity) {
            $validator = $this->validatorForStoreClass([
                'fecha' => now()->addWeek()->toDateString(),
                'hora_inicio' => '10:00',
                'hora_fin' => '11:00',
                'cupo_maximo' => $invalidCapacity,
                'id_sala' => $this->sala->id_sala,
                'id_usuario' => $this->trainer->id_usuario,
                'id_actividad' => $this->actividad->id_actividad,
            ]);

            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('cupo_maximo'));
        }
    }

    /**
     * Comprueba que StoreClassRequest limita el cupo a la capacidad de la sala.
     */
    public function test_crear_clase_rechaza_cupo_superior_a_capacidad_de_sala(): void
    {
        $validator = $this->validatorForStoreClass([
            'fecha' => now()->addWeek()->toDateString(),
            'hora_inicio' => '10:00',
            'hora_fin' => '11:00',
            'cupo_maximo' => $this->sala->capacidad_max + 1,
            'id_sala' => $this->sala->id_sala,
            'id_usuario' => $this->trainer->id_usuario,
            'id_actividad' => $this->actividad->id_actividad,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('cupo_maximo'));
    }

    /**
     * Comprueba que StoreUserRequest rechaza contraseñas débiles.
     */
    public function test_crear_usuario_rechaza_password_debil(): void
    {
        $request = new StoreUserRequest();
        $payload = [
            'nombre' => 'Cliente',
            'apellidos' => 'Debil',
            'email' => 'cliente.debil@gymmanager.test',
            'telefono' => '+34600111222',
            'id_rol' => $this->clientRole->id_rol,
            'password' => 'abc',
        ];

        $validator = Validator::make($payload, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('password'));
    }

    /**
     * Comprueba que UpdateUserRequest permite NO enviar nueva contraseña.
     */
    public function test_actualizar_usuario_permite_password_nulo(): void
    {
        $request = UpdateUserRequest::create('/api/admin/usuarios/1', 'PUT', [
            'nombre' => 'Cliente',
            'apellidos' => 'Actualizado',
            'email' => 'cliente.actualizado@gymmanager.test',
            'telefono' => '+34600111222',
            'id_rol' => $this->clientRole->id_rol,
            'password' => null,
        ]);
        $request->setRouteResolver(fn () => new class {
            public function parameter(string $key): int
            {
                return $key === 'id_usuario' ? 1 : 0;
            }
        });

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /**
     * Comprueba que StoreClassRequest exige rol Entrenador en id_usuario.
     */
    public function test_crear_clase_rechaza_usuario_que_no_es_entrenador(): void
    {
        $client = $this->makeUser('cliente.asignado@gymmanager.test', $this->clientRole);

        $validator = $this->validatorForStoreClass([
            'fecha' => now()->addWeek()->toDateString(),
            'hora_inicio' => '10:00',
            'hora_fin' => '11:00',
            'cupo_maximo' => 10,
            'id_sala' => $this->sala->id_sala,
            'id_usuario' => $client->id_usuario,
            'id_actividad' => $this->actividad->id_actividad,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('id_usuario'));
    }

    /**
     * Comprueba que StoreReservationRequest exige una clase existente.
     */
    public function test_crear_reserva_rechaza_clase_inexistente_o_no_entera(): void
    {
        $request = new StoreReservationRequest();

        foreach ([999, 'abc'] as $invalidClassId) {
            $validator = Validator::make(
                ['id_clase' => $invalidClassId],
                $request->rules(),
            );

            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('id_clase'));
        }
    }

    /**
     * Comprueba que StoreReservationRequest acepta una clase existente.
     */
    public function test_crear_reserva_acepta_clase_existente(): void
    {
        $class = $this->makeClass($this->trainer);
        $request = new StoreReservationRequest();

        $validator = Validator::make(
            ['id_clase' => $class->id_clase],
            $request->rules(),
        );

        $this->assertFalse($validator->fails());
    }

    /**
     * Comprueba que StoreAdminReservationRequest solo permite reservar clientes.
     */
    public function test_crear_reserva_admin_rechaza_usuario_que_no_es_cliente(): void
    {
        $class = $this->makeClass($this->trainer);
        $validator = $this->validatorForStoreAdminReservation([
            'id_usuario' => $this->trainer->id_usuario,
            'id_clase' => $class->id_clase,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('id_usuario'));
    }

    /**
     * Comprueba que StoreAdminReservationRequest acepta clientes y clases válidas.
     */
    public function test_crear_reserva_admin_acepta_cliente_y_clase_validos(): void
    {
        $class = $this->makeClass($this->trainer);
        $validator = $this->validatorForStoreAdminReservation([
            'id_usuario' => $this->client->id_usuario,
            'id_clase' => $class->id_clase,
        ]);

        $this->assertFalse($validator->fails());
    }

    /**
     * Comprueba que UpdateClassRequest rechaza una hora de fin anterior.
     */
    public function test_actualizar_clase_rechaza_hora_fin_anterior_a_inicio(): void
    {
        $validator = $this->validatorForUpdateClass([
            'hora_inicio' => '12:00',
            'hora_fin' => '11:00',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('hora_fin'));
    }

    /**
     * Comprueba que UpdateClassRequest acepta actualizaciones parciales válidas.
     */
    public function test_actualizar_clase_acepta_actualizacion_parcial_valida(): void
    {
        $validator = $this->validatorForUpdateClass([
            'cupo_maximo' => 20,
            'id_sala' => $this->sala->id_sala,
        ]);

        $this->assertFalse($validator->fails());
    }

    // FUNCIONES AUXILIARES

    /**
     * Construye el validador completo de StoreClassRequest.
     *
     * @param  array<string, mixed>  $payload  Datos de entrada de la clase.
     * @return \Illuminate\Validation\Validator Validador listo para evaluarse.
     */
    private function validatorForStoreClass(array $payload): \Illuminate\Validation\Validator
    {
        $request = StoreClassRequest::create('/api/admin/clases', 'POST', $payload);
        $request->setContainer($this->app);

        $validator = Validator::make($payload, $request->rules(), $request->messages());

        foreach ($request->after() as $afterValidation) {
            $validator->after($afterValidation);
        }

        return $validator;
    }

    /**
     * Construye el validador completo de StoreAdminReservationRequest.
     *
     * @param  array<string, mixed>  $payload  Datos de entrada de la reserva.
     * @return \Illuminate\Validation\Validator Validador listo para evaluarse.
     */
    private function validatorForStoreAdminReservation(array $payload): \Illuminate\Validation\Validator
    {
        $request = StoreAdminReservationRequest::create('/api/admin/reservas', 'POST', $payload);
        $request->setContainer($this->app);

        $validator = Validator::make($payload, $request->rules());

        foreach ($request->after() as $afterValidation) {
            $validator->after($afterValidation);
        }

        return $validator;
    }

    /**
     * Construye el validador completo de UpdateClassRequest.
     *
     * @param  array<string, mixed>  $payload  Datos parciales de la clase.
     * @return \Illuminate\Validation\Validator Validador listo para evaluarse.
     */
    private function validatorForUpdateClass(array $payload): \Illuminate\Validation\Validator
    {
        $request = UpdateClassRequest::create('/api/admin/clases/1', 'PUT', $payload);
        $request->setContainer($this->app);

        $validator = Validator::make($payload, $request->rules(), $request->messages());

        foreach ($request->after() as $afterValidation) {
            $validator->after($afterValidation);
        }

        return $validator;
    }

    /**
     * Crea un usuario de prueba con el rol indicado.
     */
    private function makeUser(string $email, Rol $role): Usuario
    {
        return Usuario::create([
            'nombre' => 'Usuario Unit',
            'apellidos' => 'QA',
            'email' => $email,
            'hash_password' => 'Password1!',
            'id_rol' => $role->id_rol,
        ]);
    }

    /**
     * Crea una clase válida para reglas exists sobre id_clase.
     */
    private function makeClass(Usuario $trainer): Clase
    {
        return Clase::create([
            'fecha' => now()->addWeek()->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'cupo_maximo' => 10,
            'id_sala' => $this->sala->id_sala,
            'id_usuario' => $trainer->id_usuario,
            'id_actividad' => $this->actividad->id_actividad,
        ]);
    }
}
