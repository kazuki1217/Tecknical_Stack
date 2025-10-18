FROM php:8.2.0-fpm-alpine
ARG APP_ENV
ARG UID
ARG GID
WORKDIR /var/www/html

# ComposerのインストールとPHP拡張モジュールの追加
RUN apk add --no-cache curl unzip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && docker-php-ext-install pdo pdo_mysql

# グループが存在しない場合のみ作成
RUN getent group "$GID" >/dev/null || addgroup -g "$GID" appuser; \
    # ユーザーが存在しない場合のみ作成
    getent passwd "$UID" >/dev/null || adduser -D -u "$UID" -G appuser appuser; \
    # 所有権を付与
    chown -R appuser:appuser /var/www/html 2>/dev/null || true; \
