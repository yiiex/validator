FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libonig-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    intl \
    mbstring

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Allow Composer to run as root (needed in Docker)
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-interaction --prefer-dist --no-progress

COPY . .

CMD ["php", "vendor/bin/phpunit"]
