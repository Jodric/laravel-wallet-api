# ============================================================
# Image de PRODUCTION : le code est copié DANS l'image (figé)
# Multi-stage : 3 étapes, on ne garde que le nécessaire
# ============================================================

# --- Étape 1 : installer les dépendances PHP (sans les outils de dev)
FROM composer:2 AS vendor
WORKDIR /app
COPY src/composer.json src/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader --ignore-platform-reqs
COPY src/ ./
RUN composer dump-autoload --optimize --no-dev --no-scripts

# --- Étape 2 : l'application (PHP-FPM + le code)
FROM php:8.4-fpm AS app
RUN apt-get update && apt-get install -y libzip-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip intl bcmath opcache \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
# PHP-FPM tourne avec l'utilisateur www-data : il doit pouvoir écrire ici
RUN chown -R www-data:www-data storage bootstrap/cache

# --- Étape 3 : le serveur web (Nginx + uniquement le dossier public)
FROM nginx:1.27-alpine AS web
COPY --from=vendor /app/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
