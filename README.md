# Gym Manager — API (Backend)

API REST del proyecto **Gym Manager**, desarrollada en **Laravel 12** con base de datos **PostgreSQL 17** y autenticación mediante **Laravel Sanctum** (Personal Access Tokens).

Este módulo forma parte del Trabajo de Fin de Grado del ciclo **Desarrollo de Aplicaciones Web (DAW)** de Sergio Cuéllar Almagro.

---

## Tabla de contenidos

- [Stack tecnológico](#stack-tecnológico)
- [Arquitectura](#arquitectura)
- [Puesta en marcha con Docker](#puesta-en-marcha-con-docker)
- [Puesta en marcha sin Docker](#puesta-en-marcha-sin-docker)
- [Migraciones y seeders](#migraciones-y-seeders)
- [Credenciales de prueba](#credenciales-de-prueba)
- [Documentación de la API (Scramble)](#documentación-de-la-api-scramble)
- [Comandos útiles](#comandos-útiles)
- [Estructura del proyecto](#estructura-del-proyecto)

---

## Stack tecnológico

| Tecnología      | Versión  | Uso                                              |
| --------------- | -------- | ------------------------------------------------ |
| **Laravel**     | 12.x     | Framework PHP para construir la API REST         |
| **PHP**         | 8.2+     | Lenguaje del backend                             |
| **PostgreSQL**  | 17       | Base de datos relacional                         |
| **Sanctum**     | 4.x      | Autenticación stateless vía tokens Bearer        |
| **Scramble**    | 0.13.x   | Generación automática de la documentación OpenAPI |
| **Docker**      | —        | Contenedores para la aplicación y la base de datos |
| **Composer**    | 2.x      | Gestor de dependencias de PHP                    |

---

## Arquitectura

La API sigue un diseño **stateless REST**: no hay sesiones en servidor, ni cookies de estado. Cada petición autenticada se identifica mediante un token de Sanctum enviado en la cabecera:

```
Authorization: Bearer <token>
```

El código está separado en capas bien definidas para que sea fácil de entender y mantener:

| Capa            | Carpeta                        | Responsabilidad                                                   |
| --------------- | ------------------------------ | ----------------------------------------------------------------- |
| Rutas           | `routes/api.php`               | Define los endpoints y aplica los middlewares de auth y rol.      |
| Controladores   | `app/Http/Controllers/`        | Orquestan la lógica de cada endpoint.                             |
| FormRequests    | `app/Http/Requests/`           | Validan los datos de entrada fuera del controlador.               |
| API Resources   | `app/Http/Resources/`          | Transforman los modelos a JSON (filtran campos sensibles).        |
| Middleware      | `app/Http/Middleware/CheckRole.php` | Control de acceso por rol (admin / entrenador / cliente).   |
| Modelos         | `app/Models/`                  | Representación Eloquent de las 6 tablas del dominio.              |

Los roles (`role:admin`, `role:entrenador`, `role:cliente`) se aplican como middlewares; si el usuario autenticado no tiene el rol requerido, la API devuelve **403 Forbidden**.

---

## Puesta en marcha con Docker

Es la forma recomendada, ya que no hace falta instalar PHP ni PostgreSQL en el equipo.

### Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado y en ejecución.

### Pasos

Desde la carpeta `backend/`, ejecuta:

```bash
docker-compose up -d
```

La primera vez, la imagen se construye sola; el `docker-entrypoint.sh` instala las dependencias de Composer, genera la `APP_KEY` y ejecuta las migraciones automáticamente.

Se levantan dos contenedores:

| Contenedor          | Descripción             | Puerto expuesto       |
| ------------------- | ----------------------- | --------------------- |
| `gym_manager_app`   | Aplicación Laravel      | `http://localhost:8000` |
| `gym_manager_pgsql` | PostgreSQL 17           | `localhost:5433`      |

Para cargar los datos de ejemplo (seeders), ejecuta:

```bash
docker-compose exec app php artisan db:seed
```

> Los bind mounts del `docker-compose.yml` hacen live-reload: cualquier cambio en el código PHP se refleja al instante sin reconstruir el contenedor.

---

## Puesta en marcha sin Docker

### Requisitos

- PHP 8.2 o superior, con las extensiones `pdo_pgsql` y `pgsql` habilitadas.
- Composer 2.x
- PostgreSQL 15 o superior (16/17 recomendado).

### Pasos

1. Instalar dependencias:

```bash
composer install
```

2. Copiar el archivo `.env.example` a `.env` y ajustar las variables de base de datos si fuera necesario:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gym_manager_db
DB_USERNAME=gym_manager_admin
DB_PASSWORD=supersecurepassword
```

3. Generar la clave de la aplicación:

```bash
php artisan key:generate
```

4. Crear la base de datos en PostgreSQL:

```sql
CREATE USER gym_manager_admin WITH PASSWORD 'supersecurepassword';
CREATE DATABASE gym_manager_db OWNER gym_manager_admin;
```

5. Ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

6. Arrancar el servidor de desarrollo:

```bash
php artisan serve
```

La API queda disponible en `http://localhost:8000`.

---

## Migraciones y seeders

Los seeders se ejecutan por fases para respetar las claves foráneas:

| Fase | Seeder            | Datos                                                |
| ---- | ----------------- | ---------------------------------------------------- |
| 1    | `RolSeeder`       | Administrador, Entrenador, Cliente                   |
| 1    | `SalaSeeder`      | 5 salas con capacidades de 12 a 30                   |
| 1    | `ActividadSeeder` | Yoga, Pilates, Ciclo Indoor, Cross-Training, Zumba   |
| 2    | `UsuarioSeeder`   | 1 admin + 2 entrenadores + 4 clientes                |
| 3    | `ClaseSeeder`     | 6 sesiones repartidas entre los entrenadores          |
| 4    | `ReservaSeeder`   | 8 reservas (activas y canceladas)                     |

Con Docker:

```bash
docker-compose exec app php artisan migrate --seed
```

Sin Docker:

```bash
php artisan migrate --seed
```

Para un reseteo completo (borra todo y vuelve a sembrar):

```bash
php artisan migrate:fresh --seed
```

---

## Credenciales de prueba

Todos los usuarios creados por los seeders usan la misma contraseña: `Password1!` (almacenada con Bcrypt).

| Rol           | Email                              | Contraseña   |
| ------------- | ---------------------------------- | ------------ |
| Administrador | `admin@gymmanager.com`             | `Password1!` |
| Entrenador    | `laura.entrenadora@gymmanager.com` | `Password1!` |
| Entrenador    | `miguel.entrenador@gymmanager.com` | `Password1!` |
| Cliente       | `ana.cliente@email.com`            | `Password1!` |
| Cliente       | `david.cliente@email.com`          | `Password1!` |
| Cliente       | `sara.cliente@email.com`           | `Password1!` |
| Cliente       | `jorge.cliente@email.com`          | `Password1!` |

Ejemplo de login con `curl`:

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@gymmanager.com", "password": "Password1!"}'
```

El endpoint devuelve un token Bearer que hay que incluir en las cabeceras del resto de peticiones autenticadas.

---

## Documentación de la API (Scramble)

La documentación OpenAPI se genera **automáticamente** con [Scramble](https://scramble.dedoc.co/) a partir de:

- Los tipos de retorno de cada controlador.
- Los FormRequests (reglas de validación).
- Los bloques PHPDoc de los métodos públicos.

Con la API corriendo, la documentación queda disponible en:

| Recurso                         | URL                                          |
| ------------------------------- | -------------------------------------------- |
| Visor interactivo (Scramble UI) | `http://localhost:8000/docs/api`             |
| Especificación OpenAPI JSON     | `http://localhost:8000/docs/api.json`        |

Desde el visor se puede introducir el token Bearer (esquema `sanctum`) y probar los endpoints directamente sin herramientas externas.

> En entornos distintos de `local` / `development`, el Gate `viewApiDocs` (definido en `AppServiceProvider`) bloquea el acceso a la documentación para que no quede expuesta públicamente en producción.

---

## Comandos útiles

### Docker

```bash
docker-compose up -d                 # Levanta los contenedores en segundo plano
docker-compose up --build -d         # Reconstruye tras cambios en Dockerfile/Composer
docker-compose down                  # Para los contenedores
docker-compose down -v               # Para y borra los volúmenes (elimina la BBDD)
docker-compose logs app -f           # Sigue los logs de la aplicación
docker-compose exec app bash         # Abre un shell dentro del contenedor
docker-compose exec app php artisan <comando>   # Ejecuta cualquier comando artisan
```

### Artisan (dentro o fuera de Docker)

```bash
php artisan migrate                   # Ejecuta migraciones
php artisan migrate --seed            # Migra y siembra datos de ejemplo
php artisan migrate:fresh --seed      # Reseteo completo de la BBDD
php artisan db:seed                   # Solo seeders
php artisan route:list                # Lista todas las rutas registradas
php artisan config:clear              # Limpia la caché de configuración
php artisan test                      # Ejecuta los tests de PHPUnit
```

---

## Estructura del proyecto

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/    # Lógica de cada endpoint (AuthController, ReservationController, etc.)
│   │   ├── Middleware/     # CheckRole (control por rol)
│   │   ├── Requests/       # FormRequests con las reglas de validación
│   │   └── Resources/      # API Resources (formato JSON de salida)
│   ├── Models/             # Rol, Sala, Actividad, Usuario, Clase, Reserva
│   └── Providers/          # AppServiceProvider (rate-limit + Scramble)
├── bootstrap/
│   └── app.php             # Registro del alias 'role' y middlewares globales
├── config/                 # Configuración de Laravel (auth, database, sanctum, etc.)
├── database/
│   ├── migrations/         # Migraciones de las 6 tablas + tokens/cache/jobs
│   └── seeders/            # Datos de ejemplo en 4 fases
├── routes/
│   └── api.php             # Todas las rutas bajo /api
├── docker-compose.yml      # Servicios Docker (app + pgsql)
├── Dockerfile              # Imagen PHP-CLI con extensiones pgsql
└── docker-entrypoint.sh    # Script de arranque del contenedor de la app
```

---

## Licencia

Proyecto desarrollado como Trabajo de Fin de Grado del ciclo formativo **Desarrollo de Aplicaciones Web (DAW)**.
