#!/bin/sh
# ─────────────────────────────────────────────────────────────────────────────
# docker/entrypoint.sh
#
# Container startup script executed before Supervisor takes over.
# Runs Laravel release tasks (migrate, storage:link) then hands off.
# ─────────────────────────────────────────────────────────────────────────────

set -e

echo "==> [entrypoint] Starting CV Akuna container..."

# ── 1. Ensure required directories exist with correct permissions ─────────────
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/app/livewire-tmp \
         /var/www/html/bootstrap/cache \
         /tmp/nginx_client_body

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /tmp/nginx_client_body

# ── 2. Clear cached config so Cloud Run env vars take effect ──────────────────
echo "==> [entrypoint] Clearing config cache..."
php /var/www/html/artisan config:clear

# ── 3. Re-cache with injected env vars (optimise for production) ──────────────
echo "==> [entrypoint] Caching config, routes and views..."
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:cache
php /var/www/html/artisan view:cache

# ── 4. Run database migrations (SAD §9: run as release step) ─────────────────
echo "==> [entrypoint] Running database migrations..."
php /var/www/html/artisan migrate --force

# ── 5. Create storage symlink if it doesn't exist ────────────────────────────
echo "==> [entrypoint] Creating storage symlink..."
php /var/www/html/artisan storage:link --quiet || true

echo "==> [entrypoint] Bootstrap complete. Starting Supervisor..."

# ── 6. Hand off to Supervisor (Nginx + PHP-FPM + Queue Worker) ───────────────
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
