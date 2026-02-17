# Laravel Workflow Demo

## 概要
Laravelでワークフローアプリを構築するための技術サンプルです。\
業務フローの状態遷移・承認といった基本概念を小さく実装し、学習や検証に使える最小構成を目指します。

## 主要ポイント
- 最終デプロイ先は GCP の Cloud Run
- DB は SQLite を使用
- Cloud Run はスケールや再起動があるため、**コンテナ内 SQLite は永続しない**
- 環境差をなくすため Docker で開発・実行
- **ローカル開発は Official Image（互換性重視）**
- **Cloud Run 用の prod は DHI（Docker Hardened Images）non-dev を使用**
- Cloud Run 想定のため **1 コンテナ = 1 HTTP プロセス**
- `docker-compose` は使用しない（Cloud Run では利用されないため）

## 技術スタック
- Laravel
- SQLite
- Docker
- Docker Hardened Images (DHI) ※Cloud Run 用
- GCP Cloud Run

## 前提
- `Docker`が利用できること
- `gcloud CLI`が利用できること（Cloud Run デプロイ時）

---

## ローカル実行（Docker）

本プロジェクトは Dockerfile の `dev-local` ターゲットでローカル実行します。\
ホスト環境に PHP / Composer をインストールする必要はありません。

```ps
# ビルド（初回）
docker build --no-cache --target=dev-local -t laravel-workflow-demo:dev .

# Tailwind/Vite 導入後（導入済みの場合のみ）
docker build --no-cache --target=dev-local-assets -t laravel-workflow-demo:dev .

# 起動
docker run --rm -p 8080:8080 `
  --env-file src/.env `
  -v ${PWD}\src\storage:/app/storage `
  -v ${PWD}\src\bootstrap\cache:/app/bootstrap/cache `
  -v ${PWD}\src\database:/app/database `
  laravel-workflow-demo:dev

# アクセス
http://localhost:8080
```

---

## SQLiteについて
- `DB_CONNECTION=sqlite`
- `database/database.sqlite`を使用
- Cloud Run 上ではコンテナ再作成によりデータは失われます。

---

## Cloud Run へのデプロイ

本リポジトリは イメージ指定方式（Artifact Registry → Cloud Run）を前提とします。\
詳細手順は `docs/deploy-cloud-run.md` を参照してください。

> 補足：gcloud run deploy --source . は簡易デプロイとしては便利ですが、
> 本プロジェクトの方針（DHI non-dev の利用・再現性）とはズレるため、採用していません。

---

## 補足
- 本リポジトリはデモ用途です。
- 認証、権限管理、監査ログ、バックアップ等は用途に応じて追加してください。
