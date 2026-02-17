
## 概要

<table>
  <tr>
    <td>目的</td>
    <td>Web開発の全体像を実践的に理解するために制作</td>
  </tr>
  <tr>
    <td>サービスのURL</td>
    <td><a href="https://tecknical-stack.com/">https://tecknical-stack.com/</a></td>
  </tr>
  <tr>
    <td>サービスの主な機能</td>
    <td>ユーザー投稿の作成・閲覧・検索ができるWebアプリ</td>
  </tr>
</table>

<br>

## 使用技術

| 分類 | 技術スタック |
|-|-|
| フロントエンド |  Node.js, Vite, TypeScript, React, HTML, CSS |
| バックエンド | PHP-FPM, PHP, Larave, RESTful API, APIトークン認証 |
| データベース | MySQL, phpMyAdmin |
| インフラ | AWS Lightsail, Route 53, Linux, Docker（マルチコンテナ構成）, Nginx (HTTPS対応), Mailpit|
| その他 | Windows 11, macOS（M1）, VSCode, Codex|

<br>


## 設計書

<table>
  <tr>
    <td>API仕様書</td>
    <td>https://drive.google.com/file/d/1sxDLyl4k6MclzXv9wF6pt54K82BmdbkR/view?usp=drive_link</td>
  </tr>
  <tr>
    <td>ER図</td>
    <td>https://drive.google.com/file/d/1vUoyyXPO4wGQjWshCElPLsJWU1Rozrcd/view?usp=drive_link</td>
  </tr>
  <tr>
    <td>テーブル定義書</td>
    <td>https://drive.google.com/file/d/1L6Y48aPuIeCGPCFdQW_NbMewZPmVLxQo/view?usp=drive_link</td>
  </tr>
</table>

<br>

## 機能一覧

|ログイン画面|新規登録画面|
|-|-|
| <img width="1280" height="658" alt="スクリーンショット 2025-08-06 23 30 47" src="https://github.com/user-attachments/assets/4b0b17e8-2bcc-4969-84e8-edd96e163ed9" />           | <img width="1280" height="658" alt="スクリーンショット 2025-08-06 22 21 19" src="https://github.com/user-attachments/assets/e701cb6b-f21f-4b75-9e15-3638453c82d5" />  |
|ログイン認証機能を実装しました。短時間に複数回失敗すると一定期間ログインができなくなります。　　　　　　　　　　　　　|アカウント登録機能を実装しました。登録処理を行いたくない場合、ログイン画面で「メールアドレス: sample1@example.com, パスワード: sample1pass」を入力するとログインできるように設定されています。|


|投稿一覧画面|検索画面|
|-|-|
| <img width="1280" height="658" alt="スクリーンショット 2025-08-06 22 25 44" src="https://github.com/user-attachments/assets/791b82fc-0c60-4c80-b783-30240e3540a2" /> | <img width="1280" height="658" alt="スクリーンショット 2025-08-06 22 27 02" src="https://github.com/user-attachments/assets/74a59c2f-5cd2-46eb-bf4a-ca7abd3ae8cb" /> |
|全ユーザーの投稿内容を一覧で表示する機能を実装しました。また画面上部からテキストと画像を投稿できる機能も実装しました。ユーザー自身が投稿したものは編集・削除が可能です。|検索キーワードにヒットした投稿内容を表示する機能を実装しました。こちらでもユーザー自身が投稿したものは編集・削除が可能です。　　|

<br>

## 環境構築の手順書

### 0.　環境の前提条件

以下の環境を満たしていることを確認してください。

- Docker がインストールされていること
- 本リポジトリをローカルにクローン済みであること
- プロジェクトのルートディレクトリに移動していること

### 1.　Taskをインストール

以下のコマンドを実行し、「Task」をインストールします。<br>

```
sudo snap install task --classic
```

以下のコマンドを実行し、バージョン情報が表示されれば、インストールが完了です<br>

```
task --version
```


### 2. 　Nginx・React・Laravel等のイメージを作成

以下のコマンドを実行し、すべての環境を一括で構築します。

```
task setup
```


### 3.　 .envの作成

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
DB_DATABASE=laravel_dev
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
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS=dev@example.com
MAIL_FROM_NAME="Local Dev"
LOG_MAIL_TO=dev@example.com
```

</details>



以下の内容を Tecknical_Stack/frontend 配下の .env.dev に反映します。

<details>
<summary>.env.dev</summary>

```dotenv
VITE_API_BASE_URL=http://localhost:80
```

</details>

以下の内容を Tecknical_Stack 配下の .env に反映します。

<details>
<summary>.env</summary>

```dotenv
APP_ENV=development

# mysql
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=laravel_dev
MYSQL_USER=laravel
MYSQL_PASSWORD=secret
```
</details>


### 4. 　Laravelのストレージ配下に画像を保存

以下の画像をダウンロードし、Tecknical_Stack/backend/laravel/storage/app/public 配下に保存します。

- [sample1](https://github.com/user-attachments/assets/bd8aca01-200e-4d33-a4a1-9609e6e92563)
- [sample2](https://github.com/user-attachments/assets/426ceea9-6d32-4d82-972d-9f625abd5e38)
- [sample3](https://github.com/user-attachments/assets/bbca3003-6aec-4019-9b86-aabfb2b88da8)

<details>
<summary>適切な保存状態</summary>
<img width="311" height="413" alt="画像の表示に失敗しました。" src="https://github.com/user-attachments/assets/6ce58cda-0bc0-4cc2-94e6-5055f94120c3" />
</details>


### 5.　 DBの初期化

以下のコマンドを実行し、DB に必要なテーブルやサンプルデータ等を作成します。

```
docker compose exec backend php artisan migrate:fresh --seed
```


### 6. 　動作確認

以下のコマンドを実行し、Vite を開発モードで起動します。

```
docker compose exec frontend npm run dev
```

以下の URL にアクセスし、ログイン画面を表示します。

- http://localhost:5173/

メールアドレスに「sample1@example.com」パスワードに「sample1pass」を入力し、ボタン名「ログイン」を押下します。
投稿一覧画面が表示されれば、環境構築は成功です。

<details>
<summary>ログイン画面</summary>
<img width="1919" height="824" alt="画像の表示に失敗しました。" src="https://github.com/user-attachments/assets/42870286-e9be-4aa8-95d3-a49dc6a5c66a" />
</details>

<details>
<summary>投稿一覧画面</summary>
<img width="1914" height="2472" alt="画像の表示に失敗しました。" src="https://github.com/user-attachments/assets/3253110a-6f42-49bf-aace-51eddaa110ff" />
</details>
