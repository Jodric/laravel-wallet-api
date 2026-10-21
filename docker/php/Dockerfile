# Image de base : PHP 8.4 avec PHP-FPM (le "moteur" qui exécute le PHP)
FROM php:8.4-fpm

# Outils système + extensions PHP dont Laravel a besoin
RUN apt-get update && apt-get install -y git unzip libzip-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip intl bcmath \
    && rm -rf /var/lib/apt/lists/*

# On récupère Composer depuis son image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Dossier de travail dans le conteneur (là où sera le code Laravel)
WORKDIR /var/www/html
