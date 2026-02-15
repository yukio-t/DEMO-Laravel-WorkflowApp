# Laravel Workflow Demo

## 概要
Laravelでワークフローアプリを構築するための技術サンプルです。\
業務フローの状態遷移・承認といった基本概念を小さく実装し、学習や検証に使える最小構成を目指します。

## 主要ポイント
- 最終デプロイ先はGCPのCloud Run
- DB は SQLite を使用
- Cloud Run はスケールや再起動があるため、**コンテナ内 SQLite は永続しない**
- 環境差をなくすため Docker で開発・実行
- Docker image は Docker Hardened Images (DHI) を使用（まずは`-dev`）
- Cloud Run 想定のため **1 コンテナ = 1 HTTP プロセス**
- `docker-compose` は使用しない（Cloud Run では利用されないため）

## 技術スタック
- Laravel
- SQLite
- Docker
- Docker Hardened Images (DHI)
- GCP Cloud Run

## 前提
- `Docker`が利用できること
- `gcloud CLI`が利用できること（Cloud Run デプロイ時）

---

## ローカル実行（Docker）

本プロジェクトは **Cloud Run と同一の Dockerfile をローカルでも使用**します。  
ホスト環境に PHP / Composer をインストールする必要はありません。

```bash
# ビルド
docker build --target=dev -t laravel-workflow-demo .

# 起動
docker run --rm -p 8080:8080 laravel-workflow-demo

# アクセス
http://localhost:8080
```

---

## SQLiteについて
- `DB_CONNECTION=sqlite`
- `database/database.sqlite`を使用
- Cloud Run 上ではコンテナ再作成によりデータは失われます。

---

## Cloud Run へのデプロイ（例）

※ APP_KEY などの環境変数は Cloud Run の設定画面または --set-env-vars で指定してください。

### 簡易

```bash
gcloud run deploy laravel-workflow-demo \
  --source . \
  --region asia-northeast1
```

### イメージ指定

1. Docker build
2. Artifact Registry へ push
3. Cloud Run へ deploy

詳細手順は以下を参照してください。
`docs/deploy-cloud-run.md`

---

## 補足
- 本リポジトリはデモ用途です。
- 認証、権限管理、監査ログ、バックアップ等は用途に応じて追加してください。
