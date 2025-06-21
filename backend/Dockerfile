FROM php:8.2.0-fpm-alpine
WORKDIR /var/www/html

# ComposerのインストールとPHP拡張モジュールの追加
RUN apk add --no-cache curl unzip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && docker-php-ext-install pdo pdo_mysql

# CMD [ "php", "artisan", "serve", "--host=0.0.0.0", "--port=8000" ]