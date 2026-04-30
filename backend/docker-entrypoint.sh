#!/bin/sh
set -e

echo "Instalando dependencias PHP..."
composer install --no-interaction --prefer-dist
php artisan key:generate --no-interaction --force 2>/dev/null || true

echo "Configurando permisos..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Ejecutando migraciones..."
php artisan migrate --force --no-interaction 2>/dev/null || echo "Migraciones saltadas (la base de datos podría no estar lista)"

echo "Iniciando servidor de desarrollo..."
php artisan serve --host=0.0.0.0 --port=8000
