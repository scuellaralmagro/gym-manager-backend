<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['guest', 'throttle:login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

Route::post('/reservas', [ReservationController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:cliente']);

Route::get('/reservas/mis-reservas', [ReservationController::class, 'myReservations'])
    ->middleware(['auth:sanctum', 'role:cliente']);
