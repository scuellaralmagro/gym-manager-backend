<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['guest', 'throttle:login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');
