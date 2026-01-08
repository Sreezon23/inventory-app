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
    pkg-config \
    libwebp-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype=/usr/include \
    --with-jpeg=/usr/include \
    --with-webp=/usr/include

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

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Composer install
RUN composer install --optimize-autoloader --no-dev --no-scripts
RUN composer dump-autoload --optimize

# Symfony directories
RUN mkdir -p var/cache var/log var/sessions \
    && chown -R www-data:www-data var/ public/ \
    && chmod -R 755 var/

# Clear Symfony cache
RUN php bin/console cache:clear --env=prod --no-debug --no-warmup

EXPOSE 80

CMD php -S 0.0.0.0:8000 -t public
