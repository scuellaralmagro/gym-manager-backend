# Gym Manager — Backend (API REST)

Backend del proyecto **Gym Manager**, desarrollado con **Laravel 12** y **PostgreSQL 17**.

Este módulo forma parte del Trabajo de Fin de Grado del ciclo de **Desarrollo de Aplicaciones Web (DAW)**.

---

## Tabla de contenidos

- [Requisitos previos](#requisitos-previos)
- [Puesta en marcha con Docker (recomendado)](#puesta-en-marcha-con-docker-recomendado)
- [Puesta en marcha sin Docker (alternativa)](#puesta-en-marcha-sin-docker-alternativa)
- [Comandos útiles](#comandos-útiles)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Variables de entorno](#variables-de-entorno)
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

# Revertir la última migración
php artisan migrate:rollback

# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Crear un nuevo modelo con migración
php artisan make:model NombreModelo -m

# Crear un nuevo controlador
php artisan make:controller NombreController --api

# Ejecutar los tests
php artisan test
```

---

## Estructura del proyecto

```
backend/
├── app/                    # Código fuente de la aplicación
│   ├── Http/
│   │   └── Controllers/    # Controladores de la API
│   ├── Models/             # Modelos de Eloquent
│   └── ...
├── config/                 # Archivos de configuración
├── database/
│   ├── migrations/         # Migraciones de la base de datos
│   ├── factories/          # Factories para testing
│   └── seeders/            # Seeders para datos iniciales
├── routes/                 # Definición de rutas
├── storage/                # Logs, caché, archivos subidos
├── tests/                  # Tests unitarios y de integración
├── .env                    # Variables de entorno (NO se sube a Git)
├── .env.example            # Plantilla de variables de entorno
├── docker-compose.yml      # Configuración de Docker Compose
├── Dockerfile              # Imagen Docker para la app
├── docker-entrypoint.sh    # Script de inicialización de Docker
└── composer.json           # Dependencias de PHP
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

## Tecnologías utilizadas

| Tecnología     | Versión | Uso                                       |
| -------------- | ------- | ----------------------------------------- |
| **Laravel**    | 12.x    | Framework PHP para la API REST            |
| **PHP**        | 8.3     | Lenguaje de programación del backend      |
| **PostgreSQL** | 17      | Base de datos relacional                  |
| **Docker**     | —       | Contenedorización del entorno             |
| **Composer**   | 2.x     | Gestor de dependencias de PHP             |
| **Sanctum**    | 4.x     | Autenticación basada en tokens            |

---

## Licencia

Este proyecto ha sido desarrollado como Trabajo de Fin de Grado (TFG) para el ciclo formativo de **Desarrollo de Aplicaciones Web (DAW)**.
