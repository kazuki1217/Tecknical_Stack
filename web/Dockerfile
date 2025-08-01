FROM nginx:1.28.0-alpine
ARG APP_ENV
WORKDIR /usr/share/nginx/html

# 本番環境のみユーザーを作成
RUN if [ "$APP_ENV" = "production" ]; then \
    addgroup -g 1000 app && \
    adduser -D -u 1000 -G app appuser && \
    chown -R appuser:app /usr/share/nginx/html /etc/nginx /var/log/nginx /var/cache/nginx /run; \
    fi

