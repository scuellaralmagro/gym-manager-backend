<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Configuración de Scramble para la generación automática de la documentación OpenAPI
 */

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

        // En producción bloqueo el acceso a /docs/api para evitar exponer la documentación OpenAPI a usuarios anónimos
        Gate::define('viewApiDocs', function () {
            return app()->environment('local', 'development');
        });

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer', 'bearer')
            );
        });
    }
}
