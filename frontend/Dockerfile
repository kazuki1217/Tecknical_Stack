FROM node:20-slim
WORKDIR /app
ARG APP_ENV

# 本番環境のみユーザーを作成
RUN if [ "$APP_ENV" = "production" ]; then \
    if ! getent group 1000 > /dev/null; then addgroup --gid 1000 app; fi; \
    if ! getent passwd 1000 > /dev/null; then adduser --uid 1000 --disabled-password --gecos '' --gid 1000 appuser; fi; \
    mkdir -p /app && \
    chown -R 1000:1000 /app || echo "Warning: chown failed, but continuing"; \
    fi
