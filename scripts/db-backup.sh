#!/bin/bash
# MySQLの論理フルバックアップを取得し、gzip圧縮して backup/ に保存する。
# 保持世代数を超えた古いバックアップは削除する。
#
# 実行はホスト側で行う。コンテナ内で動作するのは mysqldump のみで、
# 圧縮・保存・世代管理はホスト側の処理となる。
#
# 使用例:
#   bash scripts/db-backup.sh
#     → backup/laravel_dev_20260816_030000.sql.gz を作成し、最新5世代を残す
#
# 定期実行の設定（環境構築時に1回だけ crontab -e に登録する）:
#
#   # 毎夜3時にDBのバックアップを取得する（最新5世代を保持し、古い世代は自動削除）
#   # ログ出力先の親ディレクトリが無いとリダイレクトに失敗しスクリプト自体が起動しないため、
#   # 先に backup を作成する
#   0 3 * * * cd <リポジトリのパス> && mkdir -p backup && bash scripts/db-backup.sh >> backup/backup.log 2>&1
#
# 本番環境では docker コマンドの実行に sudo が必要なため、スクリプトを sudo 経由で起動する:
#
#   0 3 * * * cd <リポジトリのパス> && mkdir -p backup && sudo bash scripts/db-backup.sh >> backup/backup.log 2>&1

# エラーを握りつぶさず、問題があったら早めに止めるよう設定する
set -euo pipefail

# リポジトリルートへ移動する
cd "$(dirname "$0")/.."

# .env には task up が追記する UID が含まれ、これは bash の読み取り専用変数のため、
# そのまま source するとエラーになる。必要な変数だけを抽出して読み込む
set -a
source <(grep -E '^(MYSQL_ROOT_PASSWORD|MYSQL_DATABASE)=' .env)
set +a

# 日数ではなく世代数で管理する。cronの実行が飛んだ日があっても、
# 常に指定した数のバックアップが残るようにするため
RETENTION_COUNT=5

# バックアップファイル名を生成する
DEST="backup/${MYSQL_DATABASE}_$(date +%Y%m%d_%H%M%S).sql.gz"

# バックアップ先ディレクトリを作成する
mkdir -p backup

# 一時ファイルへ書き出してから移動することで、失敗・中断時に
# 不完全なファイルがバックアップとして残らないようにする
trap 'rm -f "${DEST}.tmp"' EXIT

# --single-transaction: InnoDBをロックせずに一貫したスナップショットを取得する
docker compose exec -T -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" db \
    mysqldump -u root --single-transaction --no-tablespaces "$MYSQL_DATABASE" \
    | gzip >"${DEST}.tmp"

mv "${DEST}.tmp" "$DEST"
echo "[$(date +'%Y-%m-%d %H:%M:%S')] バックアップを作成しました: ${DEST}"

# 新しい順に並べ、保持世代数を超えた分を削除する
ls -1t backup/*.sql.gz | tail -n +$((RETENTION_COUNT + 1)) | xargs -r rm --
