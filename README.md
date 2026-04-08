# Gym Manager — Backend (API REST)

Backend del proyecto **Gym Manager**, desarrollado con **Laravel 12** y **PostgreSQL 17**.

API RESTful stateless que gestiona la operativa de un gimnasio: autenticación por tokens, reservas de clases con control de aforo concurrente, panel de entrenador y administración con informes.

Este módulo forma parte del Trabajo de Fin de Grado del ciclo de **Desarrollo de Aplicaciones Web (DAW)** de Sergio Cuéllar Almagro.

---

## Tabla de contenidos

- [Requisitos previos](#requisitos-previos)
- [Puesta en marcha con Docker (recomendado)](#puesta-en-marcha-con-docker-recomendado)
- [Puesta en marcha sin Docker (alternativa)](#puesta-en-marcha-sin-docker-alternativa)
- [Poblar la base de datos (Seeding)](#poblar-la-base-de-datos-seeding)
- [Arquitectura de la API](#arquitectura-de-la-api)
- [Mapa de endpoints](#mapa-de-endpoints)
- [Esquema de base de datos](#esquema-de-base-de-datos)
- [Autenticación](#autenticación)
- [Sistema de roles y middlewares](#sistema-de-roles-y-middlewares)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Variables de entorno](#variables-de-entorno)
- [Comandos útiles](#comandos-útiles)
- [Tecnologías utilizadas](#tecnologías-utilizadas)

---

## Requisitos previos

### Con Docker (recomendado)

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado y en ejecución.

> Es la forma más sencilla de levantar el proyecto, ya que no hace falta instalar PHP, Composer ni PostgreSQL en local. El entorno de Docker incluye volúmenes anónimos y montajes locales que facilitan el desarrollo continuo (live-reload).

### Sin Docker (alternativa)

- PHP >= 8.2 (con las extensiones `pdo_pgsql` y `pgsql` habilitadas)
- [Composer](https://getcomposer.org/)
- [PostgreSQL](https://www.postgresql.org/) >= 15

---

## Puesta en marcha con Docker (recomendado)

### 1. Levantar los contenedores

Abre una terminal en esta carpeta (`backend`) y ejecuta:

```bash
docker compose up --build -d
```

Este comando construye la imagen del backend y levanta dos contenedores. Durante su arranque automático inicial, un script (`docker-entrypoint.sh`) instalará las dependencias de PHP, generará la clave de la aplicación y ejecutará las migraciones.

| Contenedor          | Descripción                        | Puerto                             |
| ------------------- | ---------------------------------- | ---------------------------------- |
| `gym_manager_app`   | Aplicación Laravel (API)           | `localhost:8000`                   |
| `gym_manager_pgsql` | Base de datos PostgreSQL 17        | `localhost:5433`                   |

> El contenedor de la app sincroniza tu código en tiempo real (bind mount). Cualquier archivo PHP que modifiques se reflejará al instante sin necesidad de reconstruir el contenedor.

### 2. ¡Listo!

La API debería estar funcionando en: **http://localhost:8000**

---

## Puesta en marcha sin Docker (alternativa)

> **Importante:** Si usas XAMPP u otra distribución de PHP en Windows, asegúrate de que las extensiones `pdo_pgsql` y `pgsql` están habilitadas en tu `php.ini`. Para ello, busca las líneas `;extension=pdo_pgsql` y `;extension=pgsql` y elimina el punto y coma del principio.

### 1. Instalar dependencias de PHP

```bash
composer install
```

### 2. Verificar la base de datos en el archivo `.env`

El archivo `.env` ya viene incluido en el proyecto. Solo asegúrate de que la configuración de base de datos local es correcta para tu entorno. Ejemplo:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=gym_manager_db
DB_USERNAME=gym_manager_admin
DB_PASSWORD=supersecurepassword
```

### 3. Crear la base de datos

Acceder a PostgreSQL y crear la base de datos y el usuario:

```sql
CREATE USER gym_manager_admin WITH PASSWORD 'supersecurepassword';
CREATE DATABASE gym_manager_db OWNER gym_manager_admin;
```

### 4. Ejecutar las migraciones

```bash
php artisan migrate
```

### 5. Arrancar los servidores de desarrollo

Arranca el servidor local de PHP:

```bash
php artisan serve
```

La API estará disponible en: **http://localhost:8000**

---

## Poblar la base de datos (Seeding)

El proyecto incluye seeders con datos de ejemplo para desarrollo. Se ejecutan en orden jerárquico estricto para respetar las claves foráneas:

```bash
php artisan db:seed
```

| Fase | Seeder            | Datos                                                   |
| ---- | ----------------- | ------------------------------------------------------- |
| 1    | RolSeeder         | Administrador, Entrenador, Cliente                      |
| 1    | SalaSeeder        | 5 salas (capacidades de 12 a 30)                        |
| 1    | ActividadSeeder   | Yoga, Pilates, Ciclo Indoor, Cross-Training, Zumba      |
| 2    | UsuarioSeeder     | 1 admin + 2 entrenadores + 4 clientes                   |
| 3    | ClaseSeeder       | 6 sesiones asignadas a entrenadores                      |
| 4    | ReservaSeeder     | 8 reservas (activas y canceladas)                        |

> **Contraseña de desarrollo:** Todos los usuarios usan `Password1!` (hasheada con Bcrypt).

### Credenciales de prueba

| Rol           | Email                              | Contraseña    |
| ------------- | ---------------------------------- | ------------- |
| Administrador | `admin@gymmanager.com`             | `Password1!`  |
| Entrenador    | `laura.entrenadora@gymmanager.com` | `Password1!`  |
| Entrenador    | `miguel.entrenador@gymmanager.com` | `Password1!`  |
| Cliente       | `ana.cliente@email.com`            | `Password1!`  |
| Cliente       | `david.cliente@email.com`          | `Password1!`  |
| Cliente       | `sara.cliente@email.com`           | `Password1!`  |
| Cliente       | `jorge.cliente@email.com`          | `Password1!`  |

---

## Arquitectura de la API

La API opera de forma **completamente stateless**: sin sesiones de servidor ni cookies de estado. Toda la autenticación se realiza mediante tokens **Sanctum (Personal Access Tokens)** enviados en la cabecera `Authorization: Bearer <token>`.

La lógica se organiza en capas desacopladas:

- **Form Requests** (`app/Http/Requests/`) — Validación formal de entrada, separada de los controladores.
- **Controllers** (`app/Http/Controllers/`) — Orquestación de la lógica de negocio.
- **API Resources** (`app/Http/Resources/`) — Transformación segura de la salida a JSON (ocultación de campos sensibles, formateo ISO-8601).
- **Middleware** (`app/Http/Middleware/`) — Control de acceso por roles.

---

## Mapa de endpoints

### Autenticación

| Método | Ruta              | Controlador               | Middlewares               |
| ------ | ----------------- | ------------------------- | ------------------------- |
| POST   | `/api/login`      | AuthController@login      | guest, throttle:login     |
| POST   | `/api/logout`     | AuthController@logout     | auth:sanctum              |

### Compartidos (cualquier usuario autenticado)

| Método | Ruta              | Controlador               | Middlewares               |
| ------ | ----------------- | ------------------------- | ------------------------- |
| GET    | `/api/perfil`     | UserController@profile    | auth:sanctum              |
| GET    | `/api/clases`     | ClassController@index     | auth:sanctum              |

### Cliente

| Método | Ruta                                | Controlador                          | Middlewares                |
| ------ | ----------------------------------- | ------------------------------------ | -------------------------- |
| POST   | `/api/reservas`                     | ReservationController@store          | auth:sanctum, role:cliente |
| GET    | `/api/reservas/mis-reservas`        | ReservationController@myReservations | auth:sanctum, role:cliente |
| PATCH  | `/api/reservas/{id}/cancelar`       | ReservationController@cancel         | auth:sanctum, role:cliente |

### Entrenador

| Método | Ruta                              | Controlador                  | Middlewares                      |
| ------ | --------------------------------- | ---------------------------- | -------------------------------- |
| GET    | `/api/entrenador/agenda`          | TrainerController@agenda     | auth:sanctum, role:entrenador    |
| GET    | `/api/clases/{id}/asistencia`     | TrainerController@attendance | auth:sanctum, role:entrenador    |

### Administrador

| Método | Ruta                                      | Controlador                              | Middlewares              |
| ------ | ----------------------------------------- | ---------------------------------------- | ------------------------ |
| POST   | `/api/admin/clases`                       | AdminClassController@store               | auth:sanctum, role:admin |
| PUT    | `/api/admin/clases/{id}`                  | AdminClassController@update              | auth:sanctum, role:admin |
| DELETE | `/api/admin/clases/{id}`                  | AdminClassController@destroy             | auth:sanctum, role:admin |
| PUT    | `/api/admin/usuarios/{id}/rol`            | AdminUserController@updateRole           | auth:sanctum, role:admin |
| GET    | `/api/admin/informes`                     | ReportController@kpis                    | auth:sanctum, role:admin |
| GET    | `/api/admin/reservas`                     | AdminOverviewController@reservas         | auth:sanctum, role:admin |
| GET    | `/api/admin/clases`                       | AdminOverviewController@clases           | auth:sanctum, role:admin |
| GET    | `/api/admin/usuarios`                     | AdminOverviewController@usuarios         | auth:sanctum, role:admin |
| PATCH  | `/api/admin/reservas/{id}/cancelar`       | AdminOverviewController@cancelarReserva  | auth:sanctum, role:admin |

---

## Esquema de base de datos

6 entidades con relaciones de integridad referencial:

```
ROLES (id_rol PK, nombre UNIQUE)
  └── USUARIOS (id_usuario PK, nombre, apellidos, email UNIQUE, telefono?, hash_password, id_rol FK → RESTRICT)
        ├── CLASES (id_clase PK, fecha, hora_inicio, hora_fin, cupo_maximo,
        │           id_sala FK → RESTRICT, id_usuario FK → RESTRICT, id_actividad FK → CASCADE)
        └── RESERVAS (id_reserva PK, fecha_creacion, estado, id_usuario FK → CASCADE, id_clase FK → CASCADE)
                      UNIQUE(id_usuario, id_clase)

SALAS (id_sala PK, nombre, capacidad_max)
ACTIVIDADES (id_actividad PK, nombre, descripcion)
```

---

## Autenticación

Basada en **Laravel Sanctum** con Personal Access Tokens (PAT).

**Login:** `POST /api/login` con `email` y `password`. Devuelve un token Bearer.

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@gymmanager.com", "password": "Password1!"}'
```

**Usar el token:** Incluir en todas las peticiones autenticadas:

```
Authorization: Bearer <token>
```

**Logout:** `POST /api/logout` revoca el token actual.

**Protección anti fuerza bruta:** El endpoint de login está limitado a 5 intentos por minuto por combinación de IP + email.

---

## Sistema de roles y middlewares

Se usa un middleware personalizado `CheckRole` registrado como alias `role` en `bootstrap/app.php`.

| Alias en ruta    | Valor en BD      | Descripción                        |
| ---------------- | ---------------- | ---------------------------------- |
| `role:admin`     | Administrador    | Gestión de clases, usuarios e informes |
| `role:entrenador`| Entrenador       | Agenda y control de asistencia     |
| `role:cliente`   | Cliente          | Reservas y consulta de mis reservas |

Si el rol del usuario autenticado no coincide con el requerido, se devuelve un **403 Forbidden**.

---

## Estructura del proyecto

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php          # Login / Logout
│   │   │   ├── UserController.php          # Perfil del usuario
│   │   │   ├── ClassController.php         # Listado de clases (con plazas)
│   │   │   ├── ReservationController.php   # Crear / Cancelar reserva / Mis reservas
│   │   │   ├── TrainerController.php       # Agenda / Asistencia
│   │   │   ├── AdminClassController.php    # CRUD de clases (admin)
│   │   │   ├── AdminUserController.php     # Cambiar rol (admin)
│   │   │   ├── AdminOverviewController.php # Vistas globales + cancelar reserva (admin)
│   │   │   └── ReportController.php        # KPIs estadísticos (admin)
│   │   ├── Middleware/
│   │   │   └── CheckRole.php               # Middleware de control de roles
│   │   ├── Requests/
│   │   │   ├── LoginRequest.php
│   │   │   ├── StoreReservationRequest.php
│   │   │   ├── StoreClassRequest.php
│   │   │   ├── UpdateClassRequest.php
│   │   │   └── UpdateUserRoleRequest.php
│   │   └── Resources/
│   │       ├── ReservaResource.php
│   │       ├── ClaseResource.php
│   │       ├── AsistenciaResource.php
│   │       ├── UsuarioResource.php
│   │       ├── AdminReservaResource.php
│   │       └── AdminClaseResource.php
│   ├── Models/
│   │   ├── Rol.php
│   │   ├── Sala.php
│   │   ├── Actividad.php
│   │   ├── Usuario.php
│   │   ├── Clase.php
│   │   └── Reserva.php
│   └── Providers/
│       └── AppServiceProvider.php          # Rate limiter + Scramble config
├── bootstrap/
│   └── app.php                             # Registro del middleware de roles
├── config/
├── database/
│   ├── migrations/                         # 6 tablas + tokens + cache + jobs
│   └── seeders/                            # Datos de ejemplo (7 seeders)
├── routes/
│   └── api.php                             # Todas las rutas de la API
├── docker-compose.yml
├── Dockerfile
├── docker-entrypoint.sh
└── composer.json
```

---

## Variables de entorno

Las variables más importantes del archivo `.env`:

| Variable        | Descripción                                                 | Valor por defecto     |
| --------------- | ----------------------------------------------------------- | --------------------- |
| `APP_ENV`       | Entorno de la aplicación                                    | `local`               |
| `APP_DEBUG`     | Activar modo debug                                          | `true`                |
| `APP_KEY`       | Clave de encriptación (se genera sola vía script en Docker) | —                     |
| `DB_CONNECTION` | Driver de base de datos                                     | `pgsql`               |
| `DB_HOST`       | Host de la base de datos                                    | `127.0.0.1`           |
| `DB_PORT`       | Puerto de la base de datos                                  | `5433`                |
| `DB_DATABASE`   | Nombre de la base de datos                                  | `gym_manager_db`      |
| `DB_USERNAME`   | Usuario de la base de datos                                 | `gym_manager_admin`   |
| `DB_PASSWORD`   | Contraseña de la base de datos                              | `supersecurepassword` |

> **Cuando se usa Docker**, las variables de base de datos (`DB_HOST` y `DB_PORT`) las sobreescribe el `docker-compose.yml` para que apunten al contenedor (`pgsql:5432`), por lo que no hace falta modificarlas en el `.env`.

---

## Comandos útiles

### Docker

```bash
# Levantar los contenedores en segundo plano
docker compose up -d

# Levantar y reconstruir (después de cambios en configuraciones o dependencias)
docker compose up --build -d

# Parar los contenedores
docker compose down

# Parar y eliminar los volúmenes (borra la base de datos)
docker compose down -v

# Ver los logs de la aplicación en tiempo real
docker compose logs app -f

# Ver los logs de la base de datos
docker compose logs pgsql -f

# Ejecutar cualquier comando artisan dentro del contenedor
docker compose exec app php artisan <comando>

# Abrir una terminal dentro del contenedor de la app
docker compose exec app bash

# Abrir una consola de PostgreSQL
docker compose exec pgsql psql -U gym_manager_admin -d gym_manager_db
```

### Sin Docker

```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar migraciones + seeders
php artisan migrate --seed

# Solo seeders (si las tablas ya existen)
php artisan db:seed

# Resetear BD completa (migrate:fresh + seed)
php artisan migrate:fresh --seed

# Revertir la última migración
php artisan migrate:rollback

# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Listar todas las rutas registradas
php artisan route:list

# Ejecutar los tests
php artisan test
```

---

## Tecnologías utilizadas

| Tecnología     | Versión | Uso                                           |
| -------------- | ------- | --------------------------------------------- |
| **Laravel**    | 12.x    | Framework PHP para la API REST                |
| **PHP**        | 8.3     | Lenguaje de programación del backend          |
| **PostgreSQL** | 17      | Base de datos relacional                      |
| **Sanctum**    | 4.x     | Autenticación stateless con tokens (PAT)      |
| **Scramble**   | 0.13.x  | Documentación OpenAPI automática              |
| **Docker**     | —       | Contenedorización del entorno de desarrollo   |
| **Composer**   | 2.x     | Gestor de dependencias de PHP                 |

---

## Documentación de la API (OpenAPI)

La documentación se genera automáticamente a partir del código gracias a [Scramble](https://scramble.dedoc.co/). No hace falta escribir anotaciones manuales.

| Recurso                 | URL                                           |
| ----------------------- | --------------------------------------------- |
| Visor interactivo       | `http://localhost:8000/docs/api`              |
| Especificación JSON     | `http://localhost:8000/docs/api.json`         |

El esquema de seguridad Bearer (Sanctum) ya está declarado en la spec, así que desde el visor se puede introducir el token y probar los endpoints directamente.

---

## Licencia

Este proyecto ha sido desarrollado como Trabajo de Fin de Grado (TFG) para el ciclo formativo de **Desarrollo de Aplicaciones Web (DAW)**.
