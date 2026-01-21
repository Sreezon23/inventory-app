FROM node:18-alpine AS assets_builder
WORKDIR /app
COPY package*.json webpack.config.js ./
RUN npm install
COPY assets/ ./assets/
RUN npm run build

FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    git \
    unzip \
    && docker-php-ext-install \
    intl \
    pdo_pgsql \
    zip \
    opcache

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN echo '<Directory /var/www/html/public>' >> /etc/apache2/apache2.conf && \
    echo '    AllowOverride All' >> /etc/apache2/apache2.conf && \
    echo '    Require all granted' >> /etc/apache2/apache2.conf && \
    echo '</Directory>' >> /etc/apache2/apache2.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --no-scripts --no-autoloader

COPY . .

COPY --from=assets_builder /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative
RUN mkdir -p var && chown -R www-data:www-data var

CMD php bin/console doctrine:migrations:migrate --no-interaction && chown -R www-data:www-data var/ && apache2-foreground