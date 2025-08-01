FROM php:8.2.0-fpm-alpine
ARG APP_ENV
WORKDIR /var/www/html

# ComposerのインストールとPHP拡張モジュールの追加
RUN apk add --no-cache curl unzip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && docker-php-ext-install pdo pdo_mysql

# 本番環境のみユーザーを作成
RUN if [ "$APP_ENV" = "production" ]; then \
    adduser -D -u 1000 appuser && \
    chown -R appuser /var/www/html && \
    echo "Switched to appuser"; \
    else \
    echo "Using default root user"; \
    fi
