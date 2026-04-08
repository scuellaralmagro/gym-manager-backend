<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Limito los intentos de login a 5 por minuto por IP+email para mitigar fuerza bruta
        RateLimiter::for('login', function (Request $request) {
            $key = $request->ip() . '|' . $request->string('email')->lower();

            return Limit::perMinute(5)->by($key);
        });
    }
}
