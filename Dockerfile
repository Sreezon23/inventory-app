FROM php:8.3-fpm

# Install system dependencies + PostgreSQL libs
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    postgresql-client \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Composer install
RUN composer install --optimize-autoloader --no-dev --no-scripts
RUN composer dump-autoload --optimize

# Symfony permissions
RUN mkdir -p var/cache var/log var/sessions
RUN chown -R www-data:www-data /var/www/var /var/www/public
RUN chmod -R 755 var/

# Symfony cache clear
RUN php bin/console cache:clear --env=prod --no-debug --no-warmup

EXPOSE 80

CMD php -S 0.0.0.0:8000 -t public
