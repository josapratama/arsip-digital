#!/bin/sh
set -e

echo "================================================================"
echo "  Arsip Digital Desa Sukarami — Starting..."
echo "================================================================"

cd /app

# 1. Environment & Configuration Synchronization
[ -f .env ] || cp .env.example .env 2>/dev/null || touch .env

# Auto-detect DB_CONNECTION if DB_HOST is present but DB_CONNECTION is not
if [ -n "$DB_HOST" ] && [ -z "$DB_CONNECTION" ]; then
  export DB_CONNECTION=pgsql
fi

# Helper to write/update key in .env
set_env_key() {
  key="$1"
  val="$2"
  if [ -n "$val" ]; then
    if grep -q "^${key}=" .env 2>/dev/null; then
      sed -i "s|^${key}=.*|${key}=${val}|" .env
    else
      echo "${key}=${val}" >> .env
    fi
  fi
}

set_env_key "APP_URL" "${APP_URL:-https://arsipdigital.josapratama.com}"
set_env_key "APP_ENV" "${APP_ENV:-production}"
set_env_key "DB_CONNECTION" "${DB_CONNECTION:-pgsql}"
[ -n "$DB_HOST" ] && set_env_key "DB_HOST" "$DB_HOST"
[ -n "$DB_PORT" ] && set_env_key "DB_PORT" "$DB_PORT"
[ -n "$DB_DATABASE" ] && set_env_key "DB_DATABASE" "$DB_DATABASE"
[ -n "$DB_USERNAME" ] && set_env_key "DB_USERNAME" "$DB_USERNAME"
[ -n "$DB_PASSWORD" ] && set_env_key "DB_PASSWORD" "$DB_PASSWORD"
[ -n "$QUEUE_CONNECTION" ] && set_env_key "QUEUE_CONNECTION" "$QUEUE_CONNECTION"

# Setup APP_KEY
if [ -z "$APP_KEY" ]; then
  if grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    export APP_KEY=$(grep "^APP_KEY=" .env | head -n 1 | cut -d '=' -f2-)
  else
    php artisan key:generate --force --no-interaction 2>/dev/null || true
    export APP_KEY=$(grep "^APP_KEY=" .env | head -n 1 | cut -d '=' -f2-)
  fi
fi

# 2. Storage & Database Directory Initialization
mkdir -p /app/storage/framework/cache \
         /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/logs \
         /app/storage/app/public \
         /app/bootstrap/cache \
         /app/database

chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database

echo "--> Creating storage symlink..."
php artisan storage:link --no-interaction 2>/dev/null || true

# 3. Database Wait (PostgreSQL or SQLite fallback)
CURRENT_DB_CONN=$(grep "^DB_CONNECTION=" .env 2>/dev/null | cut -d '=' -f2- || echo "${DB_CONNECTION:-pgsql}")
if [ "$CURRENT_DB_CONN" = "pgsql" ] || [ -n "$DB_HOST" ]; then
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
else
    touch /app/database/database.sqlite
    chown -R www-data:www-data /app/database
fi

# 4. Migrations & Seeders
if ! ls /app/database/migrations/*_create_permission_tables.php >/dev/null 2>&1; then
    echo "--> Publishing permission migrations..."
    php artisan vendor:publish --tag="permission-migrations" --no-interaction 2>/dev/null || true
fi

echo "--> Running migrations..."
php artisan migrate --force --no-interaction || true

echo "--> Seeding database (idempotent)..."
php artisan db:seed --force --no-interaction 2>/dev/null || echo "    Seeder skipped."

# 5. Background Queue Worker (Automated OCR & background processing)
echo "--> Starting background queue worker for OCR and background tasks..."
(while true; do
  php artisan queue:work --sleep=2 --tries=3 --timeout=300 || true
  sleep 3
done) &

# 6. Optimization & Caching
echo "--> Caching application..."
php artisan config:cache  || true
php artisan route:cache   || true
php artisan view:cache    || true

chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database

echo "================================================================"
echo "  Server ready at ${APP_URL:-http://localhost}"
echo "  Login: sekdes@sukarami-desa.id / password"
echo "================================================================"

exec frankenphp run --config /app/Caddyfile "$@"
