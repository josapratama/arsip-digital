#!/bin/sh
set -e

echo "================================================================"
echo "  Arsip Digital Desa Sukarami — Starting..."
echo "================================================================"

echo "--> Creating storage symlink..."
php artisan storage:link --no-interaction 2>/dev/null || true

echo "--> Generating APP_KEY if missing..."
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force --no-interaction 2>/dev/null || true
fi

echo "--> Waiting for PostgreSQL..."
RETRIES=30
until php -r "
try {
    \$host = getenv('DB_HOST') ?: 'postgres';
    \$port = getenv('DB_PORT') ?: '5432';
    \$db   = getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: 'arsip_digital');
    \$user = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'arsip_user');
    \$pass = getenv('DB_PASSWORD') ?: 'arsip_secret';
    new PDO(\"pgsql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass, [PDO::ATTR_TIMEOUT => 3]);
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
" > /dev/null 2>&1; do
    RETRIES=$((RETRIES - 1))
    if [ $RETRIES -le 0 ]; then
        echo "ERROR: Could not connect to PostgreSQL on \${DB_HOST:-postgres}:\${DB_PORT:-5432}!" >&2
        exit 1
    fi
    echo "    Waiting for PostgreSQL connection at \${DB_HOST:-postgres}:\${DB_PORT:-5432}..."
    sleep 2
done
echo "--> PostgreSQL is ready."

if ! ls /app/database/migrations/*_create_permission_tables.php >/dev/null 2>&1; then
    echo "--> Publishing permission migrations..."
    php artisan vendor:publish --tag="permission-migrations" --no-interaction 2>/dev/null || true
fi

echo "--> Running migrations (safe — skips already-run)..."
php artisan migrate --force --no-interaction || true

echo "--> Seeding database..."
php artisan db:seed --force --no-interaction 2>/dev/null || echo "    Seeder skipped."

echo "--> Caching application..."
php artisan config:cache  || true
php artisan route:cache   || true
php artisan view:cache    || true

echo "================================================================"
echo "  Server ready at http://localhost"
echo "  Login: sekdes@sukarami-desa.id / password"
echo "================================================================"

exec frankenphp run --config /app/Caddyfile
