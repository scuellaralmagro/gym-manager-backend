# Pruebas Automatizadas del Backend

Este documento describe la batería de pruebas automatizadas del backend de GymManager. Las pruebas están implementadas con PHPUnit y el framework de testing de Laravel en estos archivos:

```text
tests/Unit/BackendValidationTest.php
tests/Feature/BackendFeatureTest.php
```

La suite usa `RefreshDatabase`, por lo que cada test parte de una base de datos limpia. En el método `setUp()` de cada clase se crean explícitamente los datos mínimos necesarios: roles, sala, actividad y usuarios de prueba. Esto evita depender de los seeders de desarrollo y hace que los escenarios sean repetibles.

La clasificación física ahora coincide con la intención de las pruebas:

- **Pruebas unitarias/de validación**: ubicadas en `tests/Unit/BackendValidationTest.php`, comprueban reglas de FormRequests con `Validator`.
- **Pruebas de integración**: ubicadas en `tests/Feature/BackendFeatureTest.php`, simulan flujos completos con autenticación, endpoints, cambios en base de datos y reglas de negocio.

## Preparación común

Antes de cada prueba se crea el escenario base:

- Rol `Administrador` con `id_rol = 1`.
- Rol `Entrenador` con `id_rol = 2`.
- Rol `Cliente` con `id_rol = 3`.
- Una sala con capacidad máxima suficiente para crear clases.
- Una actividad de prueba.
- Un usuario administrador autenticable.
- Un usuario entrenador autenticable.

También existen dos helpers internos:

- `makeUser(string $email, Rol $role)`: crea un usuario válido con el rol indicado.
- `makeClass(Usuario $trainer, array $overrides = [])`: crea una clase futura válida, permitiendo sobrescribir campos concretos para escenarios específicos.

## Pruebas Unitarias / Validación

Estas pruebas validan que las reglas de entrada del backend rechazan datos inválidos antes de ejecutar lógica de negocio. Se ejecutan directamente contra los FormRequests usando `Validator`, sin pasar por controladores ni endpoints HTTP completos.

### `test_crear_clase_rechaza_cupo_negativo_o_texto`

Comprueba la validación del campo `cupo_maximo` al crear una clase desde el panel de administración.

Componente probado:

```text
StoreClassRequest
```

Escenario:

- Se construye un payload de clase con todos los campos válidos excepto `cupo_maximo`.
- El test prueba dos valores inválidos:
  - `-1`: entero negativo.
  - `"muchas"`: texto en un campo que debe ser entero.

Resultado esperado:

- Error de validación sobre `cupo_maximo`.
- El validador falla antes de que ningún controlador pueda crear la clase.

Reglas cubiertas:

- `required`
- `integer`
- `min:1`

Importancia:

Evita que entren clases con capacidad imposible o datos no numéricos, lo que podría romper cálculos de ocupación, disponibilidad y reservas.

### `test_crear_clase_rechaza_cupo_superior_a_capacidad_de_sala`

Comprueba que el cupo de una clase no puede superar la capacidad máxima de la sala seleccionada.

Componente probado:

```text
StoreClassRequest
```

Escenario:

- Se crea una sala con una capacidad máxima conocida.
- Se valida un payload de clase cuyo `cupo_maximo` es superior a esa capacidad.

Resultado esperado:

- Error de validación sobre `cupo_maximo`.

Regla cubierta:

- Regla dinámica `max:{capacidad_sala}` calculada a partir de `id_sala`.

Importancia:

Evita crear clases con más plazas que las físicamente permitidas por la sala, manteniendo coherentes las métricas de aforo y disponibilidad.

### `test_crear_usuario_rechaza_password_debil`

Comprueba que el backend rechaza contraseñas débiles al crear usuarios desde administración.

Componente probado:

```text
StoreUserRequest
```

Escenario:

- Se intenta crear un cliente con contraseña `abc`.
- El resto de campos del usuario son válidos.

Resultado esperado:

- Error de validación sobre `password`.

Reglas cubiertas:

- Longitud mínima.
- Presencia de mayúsculas.
- Presencia de minúsculas.
- Presencia de números.
- Presencia de símbolos.

Importancia:

Garantiza que las cuentas creadas desde administración cumplan la política mínima de seguridad definida en `StoreUserRequest`.

### `test_actualizar_usuario_permite_password_nulo`

Comprueba que la edición de usuarios no exige cambiar la contraseña.

Componente probado:

```text
UpdateUserRequest
```

Escenario:

- Se valida un payload completo de edición de usuario.
- El campo `password` se envía como `null`.

Resultado esperado:

- El validador no falla.

Regla cubierta:

- `password` es `nullable` en edición.

Importancia:

Garantiza que el administrador pueda modificar datos de un usuario sin verse obligado a redefinir su contraseña.

### `test_crear_clase_rechaza_usuario_que_no_es_entrenador`

Comprueba que una clase solo puede asignarse a un usuario con rol `Entrenador`.

Componente probado:

```text
StoreClassRequest
```

Escenario:

- Se crea un usuario con rol `Cliente`.
- Se valida un payload de clase asignando ese cliente como `id_usuario`.

Resultado esperado:

- Error de validación sobre `id_usuario`.

Regla cubierta:

- Validación adicional de `StoreClassRequest::after()`, que comprueba que el usuario asignado tiene `id_rol = 2`.

Importancia:

Evita que clientes o administradores sean asignados por error como responsables de clases. Esto protege las agendas de entrenador y los permisos de asistencia.

### `test_crear_reserva_rechaza_clase_inexistente_o_no_entera`

Comprueba que una reserva normal solo puede apuntar a una clase válida.

Componente probado:

```text
StoreReservationRequest
```

Escenario:

- Se valida un payload con `id_clase = 999`, que no existe.
- Se valida un payload con `id_clase = "abc"`, que no es entero.

Resultado esperado:

- Error de validación sobre `id_clase`.

Reglas cubiertas:

- `required`
- `integer`
- `exists:clases,id_clase`

Importancia:

Evita reservas huérfanas o mal formadas antes de que el controlador intente aplicar reglas de cupo o duplicados.

### `test_crear_reserva_acepta_clase_existente`

Comprueba el caso válido mínimo de `StoreReservationRequest`.

Componente probado:

```text
StoreReservationRequest
```

Escenario:

- Se crea una clase válida.
- Se valida un payload con el `id_clase` de esa clase.

Resultado esperado:

- El validador no falla.

Importancia:

Confirma que la regla `exists` acepta clases reales y que el FormRequest no bloquea entradas correctas.

### `test_crear_reserva_admin_rechaza_usuario_que_no_es_cliente`

Comprueba que las reservas forzadas desde administración solo pueden hacerse para clientes.

Componente probado:

```text
StoreAdminReservationRequest
```

Escenario:

- Se crea una clase válida.
- Se intenta validar una reserva forzada para un usuario con rol `Entrenador`.

Resultado esperado:

- Error de validación sobre `id_usuario`.

Regla cubierta:

- Validación adicional de `StoreAdminReservationRequest::after()`, que exige `id_rol = 3`.

Importancia:

Evita que el panel admin cree reservas para administradores o entrenadores, que no son usuarios reservables.

### `test_crear_reserva_admin_acepta_cliente_y_clase_validos`

Comprueba el caso válido mínimo de reserva forzada desde administración.

Componente probado:

```text
StoreAdminReservationRequest
```

Escenario:

- Se crea una clase válida.
- Se valida una reserva forzada para un usuario con rol `Cliente`.

Resultado esperado:

- El validador no falla.

Importancia:

Confirma que la restricción por rol no bloquea reservas administrativas correctas.

### `test_actualizar_clase_rechaza_hora_fin_anterior_a_inicio`

Comprueba la coherencia horaria en la edición parcial de clases.

Componente probado:

```text
UpdateClassRequest
```

Escenario:

- Se valida una actualización parcial con `hora_inicio = 12:00`.
- Se envía `hora_fin = 11:00`.

Resultado esperado:

- Error de validación sobre `hora_fin`.

Regla cubierta:

- `after:hora_inicio`

Importancia:

Evita guardar clases con rangos horarios imposibles.

### `test_actualizar_clase_acepta_actualizacion_parcial_valida`

Comprueba que `UpdateClassRequest` permite cambios parciales correctos.

Componente probado:

```text
UpdateClassRequest
```

Escenario:

- Se validan solo `cupo_maximo` e `id_sala`.
- El cupo enviado está dentro de la capacidad de la sala.

Resultado esperado:

- El validador no falla.

Importancia:

Confirma que las reglas `sometimes` permiten ediciones parciales sin exigir todos los campos de creación.

## Pruebas de Integración

Estas pruebas recorren flujos completos del backend: autenticación con `actingAs`, ejecución de endpoints, validación de respuesta HTTP y comprobación de persistencia en base de datos.

### `test_cliente_autenticado_puede_reservar_clase_disponible`

Comprueba el caso feliz de creación de una reserva.

Endpoint probado:

```text
POST /api/reservas
```

Escenario:

- Se crea un cliente.
- Se crea una clase futura con cupo disponible.
- El cliente autenticado envía el `id_clase`.

Resultado esperado:

- HTTP `201 Created`.
- Se crea una reserva en base de datos.
- La reserva queda con estado `Activa`.
- La reserva pertenece al usuario autenticado.
- La reserva apunta a la clase solicitada.

Importancia:

Valida el flujo principal de negocio para usuarios cliente: apuntarse a una clase disponible.

### `test_cliente_no_puede_reservar_clase_con_cupo_lleno`

Comprueba que el backend rechaza reservas cuando una clase ya está completa.

Endpoint probado:

```text
POST /api/reservas
```

Escenario:

- Se crea una clase con `cupo_maximo = 1`.
- Se crea una reserva activa previa de otro cliente para ocupar la única plaza.
- Un segundo cliente intenta reservar la misma clase.

Resultado esperado:

- HTTP `422 Unprocessable Entity`.
- No se crea una nueva reserva para el segundo cliente.

Regla de negocio cubierta:

- El controlador cuenta las reservas activas de la clase.
- Si el número de reservas activas es mayor o igual al cupo máximo, rechaza la operación.

Importancia:

Evita sobreventa de plazas y valida el control de ocupación de clases.

### `test_cliente_no_puede_duplicar_reserva_activa`

Comprueba que un cliente no puede reservar dos veces la misma clase si ya tiene una reserva activa.

Endpoint probado:

```text
POST /api/reservas
```

Escenario:

- Se crea un cliente.
- Se crea una clase futura.
- Se crea manualmente una reserva activa para ese cliente y esa clase.
- El mismo cliente intenta reservar de nuevo la misma clase.

Resultado esperado:

- HTTP `409 Conflict`.

Reglas cubiertas:

- Control funcional de duplicados.
- Protección sobre la restricción única `(id_usuario, id_clase)`.

Importancia:

Evita errores SQL por duplicados y garantiza que el usuario recibe una respuesta de negocio controlada.

### `test_cliente_reactiva_reserva_cancelada`

Comprueba que una reserva cancelada se reactiva en lugar de crear una fila nueva.

Endpoint probado:

```text
POST /api/reservas
```

Escenario:

- Se crea un cliente.
- Se crea una clase futura.
- Se crea una reserva previa con estado `Cancelada`.
- El cliente vuelve a reservar esa clase.

Resultado esperado:

- HTTP `201 Created`.
- La respuesta contiene el mismo `id_reserva` que la reserva cancelada.
- La reserva pasa a estar activa.

Regla cubierta:

- Reutilización de la fila existente cuando ya existe la pareja `(id_usuario, id_clase)`.

Importancia:

Respeta la restricción única de base de datos y permite al usuario volver a apuntarse tras cancelar.

### `test_cliente_no_puede_reservar_clase_pasada`

Comprueba que no se pueden hacer reservas sobre clases que ya han pasado.

Endpoint probado:

```text
POST /api/reservas
```

Escenario:

- Se crea un cliente.
- Se crea una clase con fecha del día anterior.
- El cliente intenta reservarla.

Resultado esperado:

- HTTP `422 Unprocessable Entity`.

Regla cubierta:

- El backend calcula la fecha y hora de inicio de la clase.
- Si ya está en el pasado, rechaza la reserva.

Importancia:

Evita reservas retroactivas y mantiene la integridad del historial.

### `test_entrenador_no_puede_ver_asistencia_de_clase_ajena`

Comprueba una protección RBAC/IDOR sobre la asistencia de clases.

Endpoint probado:

```text
GET /api/clases/{id_clase}/asistencia
```

Escenario:

- Se crean dos entrenadores.
- Se crea una clase asignada al primer entrenador.
- El segundo entrenador intenta consultar la asistencia de esa clase.

Resultado esperado:

- HTTP `403 Forbidden`.

Regla cubierta:

- El entrenador autenticado solo puede consultar asistencia de clases cuyo `id_usuario` coincide con su propio `id_usuario`.

Importancia:

Evita una vulnerabilidad IDOR, donde un entrenador podría acceder a datos de clases que no le pertenecen cambiando el ID de la URL.

### `test_cliente_no_puede_cancelar_reserva_ajena`

Comprueba que un cliente no puede cancelar reservas de otro cliente.

Endpoint probado:

```text
PATCH /api/reservas/{id_reserva}/cancelar
```

Escenario:

- Se crean dos clientes.
- Se crea una reserva activa perteneciente al primer cliente.
- El segundo cliente intenta cancelar esa reserva.

Resultado esperado:

- HTTP `404 Not Found`.

Regla cubierta:

- El controlador busca la reserva filtrando por:
  - `id_reserva`
  - `id_usuario` del usuario autenticado

Importancia:

Protege la propiedad de las reservas. Aunque el usuario conozca el ID de una reserva ajena, no puede modificarla.

### `test_admin_recibe_kpis_en_cero_cuando_no_hay_reservas`

Comprueba que el endpoint de informes no falla cuando no hay reservas.

Endpoint probado:

```text
GET /api/admin/informes?mes=1&anio=2099
```

Escenario:

- Se crea una clase en enero de 2099.
- No se crean reservas para ese periodo.
- El administrador consulta los KPIs filtrando por ese mes y año.

Resultado esperado:

- HTTP `200 OK`.
- `kpis.tasa_ocupacion_promedio` devuelve `0`.
- `kpis.indice_cancelaciones` devuelve `0`.

Reglas cubiertas:

- Protección contra división por cero.
- Respuesta estructurada aunque no haya reservas activas ni canceladas.

Importancia:

Evita errores en el dashboard de métricas cuando se consulta un periodo vacío o sin actividad de reservas.

### `test_admin_crea_reserva_forzada_y_bloquea_duplicado`

Comprueba el flujo de creación de reservas desde administración y el rechazo posterior de duplicados.

Endpoint probado:

```text
POST /api/admin/reservas
```

Escenario:

- Se crea un cliente.
- Se crea una clase futura.
- El administrador crea una reserva forzada para ese cliente.
- El administrador intenta crear otra reserva igual para el mismo cliente y clase.

Resultado esperado:

- Primera petición: HTTP `201 Created`.
- Segunda petición: HTTP `409 Conflict`.

Reglas cubiertas:

- Creación de reservas por administrador.
- Prevención de duplicados activos.

Importancia:

Garantiza que el panel de administración puede forzar reservas válidas, pero no puede duplicar reservas activas para la misma clase.

### `test_admin_reactiva_reserva_cancelada_forzada`

Comprueba que el administrador reactiva reservas canceladas en vez de crear duplicados.

Endpoint probado:

```text
POST /api/admin/reservas
```

Escenario:

- Se crea un cliente.
- Se crea una clase futura.
- Existe una reserva previa con estado `Cancelada`.
- El administrador crea una reserva forzada para el mismo cliente y clase.

Resultado esperado:

- HTTP `201 Created`.
- La respuesta contiene el mismo `id_reserva` de la reserva cancelada.
- La reserva se reactiva.

Regla cubierta:

- Reutilización de la reserva existente cuando ya existe la pareja `(id_usuario, id_clase)`.

Importancia:

Evita duplicados en base de datos y permite al administrador restaurar reservas canceladas de forma controlada.

## Ejecución Recomendada

Para ejecutar la suite usando una base de datos PostgreSQL aislada de pruebas:

```bash
docker exec gym_manager_pgsql psql -U gym_manager_admin -d postgres \
  -c "DROP DATABASE IF EXISTS gym_manager_test;" \
  -c "CREATE DATABASE gym_manager_test;"

docker compose exec \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=pgsql \
  -e DB_PORT=5432 \
  -e DB_DATABASE=gym_manager_test \
  -e DB_USERNAME=gym_manager_admin \
  -e DB_PASSWORD=supersecurepassword \
  app php artisan test --filter=Backend
```

También puede ejecutarse toda la suite con:

```bash
php artisan test
```

## Cobertura Funcional

La batería cubre actualmente:

- Validación de datos de entrada en creación de clases.
- Validación del cupo máximo contra la capacidad de la sala.
- Validación de contraseñas en creación de usuarios.
- Validación de edición de usuarios sin cambio obligatorio de contraseña.
- Validación de rol al asignar entrenadores a clases.
- Validación de clase existente al crear reservas.
- Validación de rol Cliente en reservas forzadas por administración.
- Validación de rangos horarios en edición de clases.
- Validación de actualizaciones parciales de clases.
- Creación correcta de reservas.
- Rechazo de reservas con cupo lleno.
- Rechazo de reservas duplicadas activas.
- Reactivación de reservas canceladas.
- Bloqueo de reservas sobre clases pasadas.
- Protección RBAC/IDOR en asistencia de entrenador.
- Protección contra cancelación de reservas ajenas.
- KPIs sin reservas y sin divisiones por cero.
- Creación de reservas forzadas por administración.
- Reactivación de reservas canceladas desde administración.

## Requisitos No Cubiertos

No se incluyen pruebas de DNI porque el backend actual no tiene campo `dni` ni reglas asociadas. Añadir esa prueba requeriría cambiar el dominio, la migración y los FormRequests, lo cual queda fuera del alcance de esta batería.