FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    postgresql-client \
    libpq-dev \
    libzip-dev \
    pkg-config

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Composer install
RUN composer install --optimize-autoloader --no-dev --no-scripts
RUN composer dump-autoload --optimize

# Symfony setup
RUN mkdir -p var/cache var/log var/sessions
RUN chown -R www-data:www-data var/ public/
RUN chmod -R 755 var/

# Clear Symfony cache
RUN php bin/console cache:clear --env=prod --no-debug --no-warmup

EXPOSE 80

CMD php -S 0.0.0.0:8000 -t public
