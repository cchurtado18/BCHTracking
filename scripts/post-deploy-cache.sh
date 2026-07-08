#!/usr/bin/env bash
# Refresca cachés de Laravel tras git pull. Ejecutar desde la raíz del proyecto en el servidor.
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Limpiando cachés..."
php artisan route:clear
php artisan view:clear
php artisan config:clear

echo "==> Recompilando cachés..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Verificando rutas de notas de entrega..."
php artisan route:list --name=deliveries.notes

echo "==> Estado migración invoice_number (ejecutar migrate --force si aparece Pending):"
php artisan migrate:status 2>/dev/null | grep -i invoice_number || echo "    (revisar migrate:status manualmente)"

echo "==> Reiniciando PHP-FPM (OPcache)..."
if systemctl restart php8.4-fpm 2>/dev/null; then
    echo "    php8.4-fpm reiniciado"
elif systemctl restart php8.3-fpm 2>/dev/null; then
    echo "    php8.3-fpm reiniciado"
elif systemctl restart php-fpm 2>/dev/null; then
    echo "    php-fpm reiniciado"
else
    echo "    (no se encontró servicio php-fpm; reinicia manualmente si aplica)"
fi

echo "==> Últimas líneas del log (por si persiste error 500):"
tail -20 storage/logs/laravel.log 2>/dev/null || echo "    (sin laravel.log)"

echo "==> Listo. Recarga /deliveries en el navegador."
