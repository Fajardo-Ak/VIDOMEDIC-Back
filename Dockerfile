FROM php:8.2-apache

# 1. Habilitar módulos
RUN a2enmod rewrite headers proxy_http

# 2. Instalar dependencias
RUN apt-get update && apt-get install -y \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libxml2-dev libonig-dev libcurl4-openssl-dev \
    unzip zip git curl default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip pdo pdo_mysql xml curl opcache

# 3. Configurar Apache a /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# 4. Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html

# 5. Copiar y construir
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-dev
COPY . .
RUN composer dump-autoload --optimize && composer run-script post-root-package-install

# 6. Permisos (MODIFICADO)
# Agregamos /var/www/html/public para que Laravel pueda guardar fotos ahí sin error 500
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/public

# 7. Configuración PHP (NUEVO)
# Aumentar límite de subida a 10MB para evitar errores de validación con fotos grandes
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 80
CMD ["apache2-foreground"]