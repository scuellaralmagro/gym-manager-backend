<?php

use App\Http\Controllers\AdminClassController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TrainerController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['guest', 'throttle:login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

Route::post('/reservas', [ReservationController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:cliente']);

Route::get('/reservas/mis-reservas', [ReservationController::class, 'myReservations'])
    ->middleware(['auth:sanctum', 'role:cliente']);

Route::get('/entrenador/agenda', [TrainerController::class, 'agenda'])
    ->middleware(['auth:sanctum', 'role:entrenador']);

Route::get('/clases/{id_clase}/asistencia', [TrainerController::class, 'attendance'])
    ->middleware(['auth:sanctum', 'role:entrenador']);

Route::post('/admin/clases', [AdminClassController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::put('/admin/usuarios/{id_usuario}/rol', [AdminUserController::class, 'updateRole'])
    ->middleware(['auth:sanctum', 'role:admin']);
