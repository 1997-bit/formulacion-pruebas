# Dependencias sin dev.
FROM docker.io/library/composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader --ignore-platform-req=ext-*

FROM docker.io/library/php:8.2-apache
RUN docker-php-ext-install pdo_mysql opcache \
    && a2enmod rewrite headers \
    && sed -i 's#/var/www/html#/var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf '%s\n' \
        'opcache.validate_timestamps=0' \
        'opcache.preload=/var/www/html/config/precarga.php' \
        'opcache.preload_user=www-data' \
        'upload_max_filesize=5M' \
        'post_max_size=6M' \
        > "$PHP_INI_DIR/conf.d/app.ini"
WORKDIR /var/www/html
COPY --from=vendor /app/vendor vendor
COPY . .
RUN chown -R www-data:www-data storage
