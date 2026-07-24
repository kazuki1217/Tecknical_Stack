# リポジトリ運用ガイド

## プロジェクト概要

このリポジトリは、React・TypeScriptによるフロントエンド、LaravelによるREST API、MySQL、Nginxで構成されたDockerベースのWebアプリケーションです。

## ディレクトリ構成

- `frontend/react/`: Vite・TypeScriptを使用したReactフロントエンド
- `backend/laravel/`: Laravel API、データベースマイグレーション、シーダー、テスト
- `docs/openapi.yaml`: OpenAPI仕様書
- `web/`: Nginx設定と静的ファイル
- `docker-compose.yml`: ローカル環境のコンテナ構成
- `Taskfile.yml`: 開発用の共通コマンド

## 作業ルール

- 変更前に、対象となる実装・テスト・設定を確認すること。
- 変更範囲は依頼内容に限定し、利用者による無関係な変更を保持すること。
- 周辺コードの命名、書式、設計パターンに従うこと。
- APIの契約を変更した場合は、`docs/openapi.yaml`も更新すること。
- 秘密情報や生成された環境設定ファイルをコミットしないこと。`.env*`はローカル専用として扱うこと。
- 振る舞いを変更する場合は、可能な限りテストを追加または更新すること。

## 検証

変更した領域に応じて、次の確認を実行してください。

- フロントエンドのLint: `docker compose exec frontend npm run lint`
- フロントエンドのビルド: `docker compose exec frontend npm run build`
- バックエンドのテスト: `docker compose exec backend php artisan test`
- バックエンドのフォーマット確認: `docker compose exec backend ./vendor/bin/pint --test`

コンテナを利用できない場合は、実行できなかった確認項目と理由を報告してください。

## GitHub Issue

GitHub Issueの作成を依頼された場合は、`issue-create` Skillを使用してください。リポジトリ内の根拠を確認し、重複Issueを調査したうえで、利用者から即時作成を明示されていない限り、公開前に内容の確認を得てください。
