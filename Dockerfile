FROM dunglas/frankenphp:1-php8.4-alpine AS base

LABEL maintainer="Arsip Digital Desa Sukarami"

# Install system dependencies + PHP extensions
RUN apk add --no-cache \
    git curl zip unzip nodejs npm postgresql-client \
    tesseract-ocr tesseract-ocr-data-ind imagemagick poppler-utils \
    && install-php-extensions \
        pdo_pgsql pgsql intl gd exif zip redis opcache pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# --- Development stage ---
FROM base AS development

ENV APP_ENV=local
ENV APP_DEBUG=true
ENV SERVER_NAME=":80"

# Install PHP dependencies first (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-interaction --prefer-dist --no-autoloader

# Install Node dependencies
COPY package.json package-lock.json* ./
RUN npm install

# Copy application files
COPY . .

# Generate autoloader & build frontend
RUN composer dump-autoload --optimize \
    && npm run build

# Fix permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod +x /app/docker-entrypoint.sh

EXPOSE 80 443 443/udp

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 CMD curl -f http://localhost:80/ || exit 1

ENTRYPOINT ["/app/docker-entrypoint.sh"]
