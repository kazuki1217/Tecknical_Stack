FROM node:20-slim
ARG APP_ENV
ARG UID
ARG GID
WORKDIR /app

# グループが存在しない場合のみ作成
RUN getent group "$GID" >/dev/null || addgroup -g "$GID" appuser; \
    # ユーザーが存在しない場合のみ作成
    getent passwd "$UID" >/dev/null || adduser -D -u "$UID" -G appuser appuser; \
    # アプリケーション用の作業ディレクトリを作成
    mkdir -p /app && \
    # 所有権を付与
    chown -R appuser:appuser /app 2>/dev/null || true; \    
