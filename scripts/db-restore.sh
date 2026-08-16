#!/bin/bash
# バックアップファイルからMySQLを復元する。
#
# ダンプに含まれるテーブルは DROP TABLE IF EXISTS により置き換わるが、
# バックアップ取得後に追加されたテーブルはダンプに存在せず削除されない。
# 中途半端な状態を避けるため、データベースを作り直してから流し込む。
#
# 使用例:
#   bash scripts/db-restore.sh backup/laravel_dev_20260816_030000.sql.gz

# エラーを握りつぶさず、問題があったら早めに止めるよう設定する
set -euo pipefail

# リポジトリルートへ移動する
cd "$(dirname "$0")/.."

# .env には task up が追記する UID が含まれ、これは bash の読み取り専用変数のため、
# そのまま source するとエラーになる。必要な変数だけを抽出して読み込む
set -a
source <(grep -E '^(MYSQL_ROOT_PASSWORD|MYSQL_DATABASE)=' .env)
set +a

BACKUP_FILE="${1:-}"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "エラー: バックアップファイルを指定してください。" >&2
    echo "使用例: task db-restore FILE=backup/xxx.sql.gz" >&2
    exit 1
fi

# データベースを削除した後にファイルの破損が判明すると復旧手段を失うため、
# 削除前に展開できることを確認する
gzip -t "$BACKUP_FILE"

MYSQL_EXEC=(docker compose exec -T -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" db mysql -u root)

# 文字セットは config/database.php の設定値に合わせる。
# 各テーブルの文字セットはダンプ内の CREATE TABLE に含まれるため、
# ここで指定するのは以降のマイグレーションに影響するデータベース単位の既定値となる
"${MYSQL_EXEC[@]}" -e "DROP DATABASE IF EXISTS \`${MYSQL_DATABASE}\`;
    CREATE DATABASE \`${MYSQL_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# バックアップファイルを展開してデータベースに流し込む
gunzip -c "$BACKUP_FILE" | "${MYSQL_EXEC[@]}" "$MYSQL_DATABASE"

echo "[$(date +'%Y-%m-%d %H:%M:%S')] 復元が完了しました: ${BACKUP_FILE}"
