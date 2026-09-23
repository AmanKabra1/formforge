# ─── 1. PHP dependencies ─────────────────────────────────────────────
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

# ─── 2. Front-end assets (Vite + Tailwind) ───────────────────────────
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
# Tailwind also scans the pagination views that ship with Laravel
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ─── 3. Runtime: PHP 8.2 + Apache ────────────────────────────────────
FROM php:8.2-apache

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql zip gd intl bcmath pcntl opcache \
    && a2enmod rewrite headers \
    && rm /etc/apache2/sites-enabled/000-default.conf \
    && echo "" > /etc/apache2/ports.conf \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/apache.conf /etc/apache2/sites-enabled/formforge.conf
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-formforge.ini"
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --optimize --no-dev --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/start.sh

# Render injects PORT (defaults to 10000)
ENV PORT=10000
EXPOSE 10000

CMD ["docker/start.sh"]
