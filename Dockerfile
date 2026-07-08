# ─────────────────────────────────────────────────────────────────────────────
# Stage 1 — PHP Dependencies (Composer)
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS composer-deps

# System deps required by Composer and common PHP extensions
RUN apk add --no-cache \
    git \
    curl \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    oniguruma-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    sqlite-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy only dependency manifests first — allows Docker to cache this layer
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-scripts

# ─────────────────────────────────────────────────────────────────────────────
# Stage 2 — Node / Frontend Assets (Vite build)
# ─────────────────────────────────────────────────────────────────────────────
FROM node:22-alpine AS node-builder

WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

# Copy source files needed by Vite
COPY resources/ resources/
COPY tailwind.config.js postcss.config.js vite.config.js ./

# Copy blade views so Tailwind can tree-shake unused classes
COPY resources/views/ resources/views/

RUN npm run build

# ─────────────────────────────────────────────────────────────────────────────
# Stage 3 — Production Runtime Image
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS production

LABEL org.opencontainers.image.title="CV Akuna Inventory Management System"
LABEL org.opencontainers.image.description="Laravel 12 / PHP 8.4 — Sprint 1 Production Image"
LABEL org.opencontainers.image.url="https://github.com/your-org/inventory-management-system-akuna"

# Runtime system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    oniguruma \
    libzip \
    icu-libs \
    libpq \
    && rm -rf /var/cache/apk/*

# PHP extensions (compiled in a temp layer to keep final image smaller)
RUN apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    oniguruma-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    sqlite-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apk del .build-deps

# PHP Redis extension (for production session/cache/queue — SAD §7.1)
RUN apk add --no-cache --virtual .phpredis-build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .phpredis-build-deps

# OPcache tuning for production
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php-prod.ini /usr/local/etc/php/conf.d/php-prod.ini

WORKDIR /var/www/html

# Copy application code
COPY . .

# Pull compiled vendor from composer stage
COPY --from=composer-deps /var/www/html/vendor vendor/

# Pull compiled Vite assets from node stage
COPY --from=node-builder /var/www/html/public/build public/build/

# Create the .env for production from the example — real values injected
# at runtime via Cloud Run environment variables / Secret Manager.
RUN cp .env.example .env 2>/dev/null || true

# Laravel bootstrap
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Fix ownership — nginx and php-fpm both run as www-data
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy Nginx and Supervisor config
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Cloud Run requires listening on $PORT (default 8080)
ENV PORT=8080
EXPOSE 8080

# Entrypoint: run database migrations then hand off to Supervisor
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
