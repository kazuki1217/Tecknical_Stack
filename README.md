## 環境構築の手順書

### 0. 環境の前提条件

以下の環境を満たしていることを確認してください。

- Docker がインストールされていること
- 本リポジトリをローカルにクローン済みであること
- プロジェクトのルートディレクトリに移動していること

<br>

### 1. 「Task」と「Tree」をインストール

以下のコマンドを実行し、「Task」をインストールします。<br>

```
sudo snap install task --classic
```

以下のコマンドを実行し、「Tree」をインストールします。<br>

```
sudo apt update
sudo apt install tree
```

以下のコマンドを実行し、バージョン情報が表示されれば、インストールが完了です<br>

```
task --version
tree --version
```

<br>

### 2. Nginx・React・Laravel 等 のイメージを作成

以下のコマンドを実行し、すべての環境を一括で構築します。

```
task setup
```

<br>

### 3. Laravel の .env を更新

以下の内容を Tecknical_Stack/backend/laravel 配下の .env に反映します。

<details>
<summary>.env</summary>

```dotenv
# アプリケーション基本設定
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:cgO0y7cyTt+eTp1LgXu8M5HHVZyTY0GY7OIUYK13C7g=
APP_DEBUG=true
APP_URL=http://localhost

# アプリの言語設定
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

# メンテナンスモード関連
APP_MAINTENANCE_DRIVER=file

# ハッシュ設定
BCRYPT_ROUNDS=12

# ログ設定
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# データベース接続設定
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret

# セッション管理設定
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# ファイル保存先の設定
FILESYSTEM_DISK=local

# キャッシュの保存先を設定
CACHE_STORE=file

# Redis設定
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# メール送信設定（MailpitやSMTPなど）
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# AWS設定（S3を使う場合）
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# Viteフロントエンド用設定（JS側でAPP_NAMEを使いたい場合
VITE_APP_NAME="${APP_NAME}"
```

</details>

<br>

### 4. Laravel のストレージ配下に画像を保存

以下の画像をダウンロードし、Tecknical_Stack/backend/laravel/storage/app/public 配下に保存します。

- [sample1](https://github.com/user-attachments/assets/bd8aca01-200e-4d33-a4a1-9609e6e92563)
- [sample2](https://github.com/user-attachments/assets/426ceea9-6d32-4d82-972d-9f625abd5e38)
- [sample3](https://github.com/user-attachments/assets/bbca3003-6aec-4019-9b86-aabfb2b88da8)

<details>
<summary>適切な保存状態</summary>
<img width="311" height="413" alt="画像の表示に失敗しました。" src="https://github.com/user-attachments/assets/6ce58cda-0bc0-4cc2-94e6-5055f94120c3" />
</details>

<br>

### 5. DB の初期化

以下のコマンドを実行し、DB に必要なテーブルやサンプルデータ等を作成します。

```
docker compose exec backend php artisan migrate:fresh --seed
```

<br>


### 6. 動作の確認

以下の URL にアクセスし、ログイン画面を表示します。

- http://localhost:5173/

名前に「sample1」パスワードに「sample1pass」を入力し、ボタン名「ログイン」を押下します。
投稿一覧画面が表示されれば、環境構築は成功です。

<details>
<summary>ログイン画面</summary>
<img width="1919" height="824" alt="画像の表示に失敗しました。" src="https://github.com/user-attachments/assets/42870286-e9be-4aa8-95d3-a49dc6a5c66a" />
</details>

<details>
<summary>投稿一覧画面</summary>
<img width="1914" height="2472" alt="画像の表示に失敗しました。" src="https://github.com/user-attachments/assets/3253110a-6f42-49bf-aace-51eddaa110ff" />
</details>
