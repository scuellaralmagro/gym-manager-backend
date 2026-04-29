<?php

use App\Http\Controllers\AdminActividadController;
use App\Http\Controllers\AdminCatalogController;
use App\Http\Controllers\AdminClassController;
use App\Http\Controllers\AdminOverviewController;
use App\Http\Controllers\AdminReservationController;
use App\Http\Controllers\AdminSalaController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
* Rutas de la API
*
* Todas las rutas están bajo el prefijo /api (lo añade Laravel 11+).
* La autenticación es stateless vía tokens de Sanctum (Authorization: Bearer).
* El alias "role:xxx" es un middleware propio (App\Http\Middleware\CheckRole)
* que comprueba el rol del usuario autenticado.
*
*/

/*
* Autenticación
*
* POST /login  -> iniciar sesión y recibir un token Bearer
* POST /logout -> cerrar sesión (revoca el token actual)
*/
Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['guest', 'throttle:login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

/*
* Perfil y listado de clases (cualquier usuario autenticado)
*
* GET /perfil  -> datos del usuario autenticado
* PUT /perfil  -> actualizar nombre, apellidos, email, teléfono y contraseña
* GET /clases  -> listado público (dentro de la app) de clases con plazas
*/
Route::get('/perfil', [UserController::class, 'profile'])
    ->middleware('auth:sanctum');

Route::put('/perfil', [UserController::class, 'update'])
    ->middleware('auth:sanctum');

Route::get('/clases', [ClassController::class, 'index'])
    ->middleware('auth:sanctum');

/*
* Reservas (solo rol Cliente)
*
* POST   /reservas                          -> crea una reserva
* GET    /reservas/mis-reservas             -> lista las reservas propias
* PATCH  /reservas/{id_reserva}/cancelar    -> cancela una reserva propia
*/
Route::post('/reservas', [ReservationController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:cliente']);

Route::get('/reservas/mis-reservas', [ReservationController::class, 'myReservations'])
    ->middleware(['auth:sanctum', 'role:cliente']);

Route::patch('/reservas/{id_reserva}/cancelar', [ReservationController::class, 'cancel'])
    ->middleware(['auth:sanctum', 'role:cliente']);

/*
* Entrenador (solo rol Entrenador)
*
* GET /entrenador/agenda           -> clases asignadas (soporta ?desde=&hasta=)
* GET /entrenador/especialidades   -> actividades que imparte (solo lectura)
* GET /clases/{id_clase}/asistencia -> listado de clientes reservados
*/
Route::get('/entrenador/agenda', [TrainerController::class, 'agenda'])
    ->middleware(['auth:sanctum', 'role:entrenador']);

Route::get('/entrenador/especialidades', [TrainerController::class, 'especialidades'])
    ->middleware(['auth:sanctum', 'role:entrenador']);

Route::get('/clases/{id_clase}/asistencia', [TrainerController::class, 'attendance'])
    ->middleware(['auth:sanctum', 'role:entrenador']);

/*
* Administrador (solo rol Administrador)
*
* Gestión de clases, usuarios, salas, actividades, reservas e informes.
*/
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // Gestión de clases
    Route::post('/clases', [AdminClassController::class, 'store']);
    Route::put('/clases/{id_clase}', [AdminClassController::class, 'update']);
    Route::delete('/clases/{id_clase}', [AdminClassController::class, 'destroy']);

    // Gestión de usuarios
    Route::post('/usuarios', [AdminUserController::class, 'store']);
    Route::put('/usuarios/{id_usuario}', [AdminUserController::class, 'update']);
    Route::delete('/usuarios/{id_usuario}', [AdminUserController::class, 'destroy']);

    // Catálogos (selectores del panel)
    Route::get('/entrenadores', [AdminCatalogController::class, 'entrenadores']);
    Route::get('/salas', [AdminCatalogController::class, 'salas']);
    Route::get('/actividades', [AdminCatalogController::class, 'actividades']);

    // Gestión de salas
    Route::post('/salas', [AdminSalaController::class, 'store']);
    Route::put('/salas/{id_sala}', [AdminSalaController::class, 'update']);
    Route::delete('/salas/{id_sala}', [AdminSalaController::class, 'destroy']);

    // Gestión de actividades
    Route::post('/actividades', [AdminActividadController::class, 'store']);
    Route::put('/actividades/{id_actividad}', [AdminActividadController::class, 'update']);
    Route::delete('/actividades/{id_actividad}', [AdminActividadController::class, 'destroy']);

    // Informes y resúmenes
    Route::get('/informes', [ReportController::class, 'kpis']);
    Route::get('/dashboard-summary', [AdminOverviewController::class, 'dashboardSummary']);

    // Vistas globales (paginadas y filtrables)
    Route::get('/reservas', [AdminOverviewController::class, 'reservas']);
    Route::get('/clases', [AdminOverviewController::class, 'clases']);
    Route::get('/usuarios', [AdminOverviewController::class, 'usuarios']);

    // Acciones sobre reservas
    Route::post('/reservas', [AdminReservationController::class, 'store']);
    Route::patch('/reservas/{id_reserva}/cancelar', [AdminOverviewController::class, 'cancelarReserva']);
});
