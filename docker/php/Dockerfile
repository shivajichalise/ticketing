FROM php:8.3-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    bash \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions (grouped for readability and cache efficiency)
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mbstring \
    ctype \
    curl \
    dom \
    fileinfo \
    intl \
    session \
    xml \
    filter \
    zip

# Install Composer (from official image)
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Optional: create Laravel storage and cache dirs (in case not pre-existing)
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Set working directory
WORKDIR /var/www
