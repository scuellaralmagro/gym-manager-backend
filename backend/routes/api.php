<?php

use App\Http\Controllers\AdminClassController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


// Rutas de autenticación
// /login POST: inicio de sesión
// /logout POST: cierre de sesión
Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['guest', 'throttle:login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

// Rutas de perfil y clases
// /perfil GET: información del usuario activo autenticado
// /clases GET: listado de clases disponibles para reservar
Route::get('/perfil', [UserController::class, 'profile'])
    ->middleware('auth:sanctum');

Route::get('/clases', [ClassController::class, 'index'])
    ->middleware('auth:sanctum');

// Rutas de reservas
// /reservas POST: crea una nueva reserva para el usuario activo
// /reservas/mis-reservas GET: lista las reservas del usuario activo
Route::post('/reservas', [ReservationController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:cliente']);

Route::get('/reservas/mis-reservas', [ReservationController::class, 'myReservations'])
    ->middleware(['auth:sanctum', 'role:cliente']);

// Rutas de entrenador
// /entrenador/agenda GET: listado de clases asignadas al entrenador activo
// /clases/{id_clase}/asistencia GET: listado de asistencias a una clase específica
Route::get('/entrenador/agenda', [TrainerController::class, 'agenda'])
    ->middleware(['auth:sanctum', 'role:entrenador']);

Route::get('/clases/{id_clase}/asistencia', [TrainerController::class, 'attendance'])
    ->middleware(['auth:sanctum', 'role:entrenador']);

// Rutas de administrador
// /admin/clases POST: crea una nueva clase
// /admin/usuarios/{id_usuario}/rol PUT: cambia el rol de un usuario
Route::post('/admin/clases', [AdminClassController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::put('/admin/usuarios/{id_usuario}/rol', [AdminUserController::class, 'updateRole'])
    ->middleware(['auth:sanctum', 'role:admin']);
