FROM php:8.3-fpm-bookworm

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
 && docker-php-ext-configure intl \
 && docker-php-ext-install -j"$(nproc)" \
    pdo_mysql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    intl \
    opcache \
 && rm -rf /var/lib/apt/lists/*

COPY conf.d/uploads.ini /usr/local/etc/php/conf.d/99-uploads.ini

WORKDIR /var/www/html
