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


/**
 * Rutas de autenticación
 * /login POST: inicio de sesión
 * /logout POST: cierre de sesión
 */
Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['guest', 'throttle:login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

/**
 * Rutas de perfil y clases
 * /perfil GET: información del usuario activo autenticado
 * /clases GET: listado de clases disponibles para reservar
 */
Route::get('/perfil', [UserController::class, 'profile'])
    ->middleware('auth:sanctum');

Route::put('/perfil', [UserController::class, 'update'])
    ->middleware('auth:sanctum');

Route::get('/clases', [ClassController::class, 'index'])
    ->middleware('auth:sanctum');

/**
 * Rutas de reservas
 * /reservas POST: crea una nueva reserva para el usuario activo
 * /reservas/mis-reservas GET: lista las reservas del usuario activo
 * /reservas/{id_reserva}/cancelar PATCH: cancela una reserva del usuario activo
 */
Route::post('/reservas', [ReservationController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:cliente']);

Route::get('/reservas/mis-reservas', [ReservationController::class, 'myReservations'])
    ->middleware(['auth:sanctum', 'role:cliente']);

Route::patch('/reservas/{id_reserva}/cancelar', [ReservationController::class, 'cancel'])
    ->middleware(['auth:sanctum', 'role:cliente']);

/**
 * Rutas de entrenador
 * /entrenador/agenda GET: listado de clases asignadas al entrenador activo
 * /clases/{id_clase}/asistencia GET: listado de asistencias a una clase específica
 */
Route::get('/entrenador/agenda', [TrainerController::class, 'agenda'])
    ->middleware(['auth:sanctum', 'role:entrenador']);

Route::get('/clases/{id_clase}/asistencia', [TrainerController::class, 'attendance'])
    ->middleware(['auth:sanctum', 'role:entrenador']);

/**
 * Rutas de administrador
 * /admin/clases POST: crea una nueva clase
 * /admin/clases/{id_clase} PUT: actualiza una clase
 * /admin/clases/{id_clase} DELETE: elimina una clase
 * /admin/usuarios/{id_usuario}/rol PUT: cambia el rol de un usuario
 * /admin/informes GET: KPIs de estadísticas de uso
 * /admin/reservas GET: listado de reservas
 * /admin/clases GET: listado de clases
 * /admin/usuarios GET: listado de usuarios
 * /admin/reservas/{id_reserva}/cancelar PATCH: cancela una reserva
 */
Route::post('/admin/clases', [AdminClassController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::put('/admin/clases/{id_clase}', [AdminClassController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::delete('/admin/clases/{id_clase}', [AdminClassController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::post('/admin/usuarios', [AdminUserController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::put('/admin/usuarios/{id_usuario}/rol', [AdminUserController::class, 'updateRole'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::put('/admin/usuarios/{id_usuario}', [AdminUserController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::delete('/admin/usuarios/{id_usuario}', [AdminUserController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::get('/admin/entrenadores', [AdminCatalogController::class, 'entrenadores'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::get('/admin/salas', [AdminCatalogController::class, 'salas'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::post('/admin/salas', [AdminSalaController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::put('/admin/salas/{id_sala}', [AdminSalaController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::delete('/admin/salas/{id_sala}', [AdminSalaController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::get('/admin/actividades', [AdminCatalogController::class, 'actividades'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::post('/admin/actividades', [AdminActividadController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::put('/admin/actividades/{id_actividad}', [AdminActividadController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::delete('/admin/actividades/{id_actividad}', [AdminActividadController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::get('/admin/informes', [ReportController::class, 'kpis'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::get('/admin/dashboard-summary', [AdminOverviewController::class, 'dashboardSummary'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::get('/admin/reservas', [AdminOverviewController::class, 'reservas'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::post('/admin/reservas', [AdminReservationController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::get('/admin/clases', [AdminOverviewController::class, 'clases'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::get('/admin/usuarios', [AdminOverviewController::class, 'usuarios'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::patch('/admin/reservas/{id_reserva}/cancelar', [AdminOverviewController::class, 'cancelarReserva'])
    ->middleware(['auth:sanctum', 'role:admin']);
