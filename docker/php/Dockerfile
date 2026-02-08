FROM php:8.4-fpm

# Устанавливаем системные зависимости
#RUN apt-get update && apt-get install -y \
#    git \
#    curl \
#    libpng-dev \
#    libonig-dev \
#    libxml2-dev \
#    libzip-dev \
#    zip \
#    unzip \
#    && docker-php-ext-install \
#    pdo_mysql \
#    mbstring \
#    exif \
#    pcntl \
#    bcmath \
#    gd \
#    zip

# Устанавливаем только необходимые зависимости
RUN apt-get update && apt-get install -y \
    git curl libpq-dev postgresql-client \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    zip unzip

# Устанавливаем расширения PHP
RUN docker-php-ext-install \
    pdo pdo_pgsql pgsql \
    pdo_mysql mbstring exif pcntl bcmath gd zip

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создаем рабочую директорию
WORKDIR /var/www

# Копируем файлы приложения
COPY . .

# Устанавливаем зависимости PHP
RUN composer install --no-interaction --optimize-autoloader

# Меняем владельца файлов
RUN chown -R www-data:www-data /var/www

# Указываем пользователя
USER www-data

EXPOSE 9000

CMD ["php-fpm"]