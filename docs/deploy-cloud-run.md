# Cloud Run Deployment Guide（Complete）

本ドキュメントでは、本プロジェクトを**GCP Cloud Run**に**Docker イメージ指定方式**でデプロイする手順を示します。

- DHI（Docker Hardened Images）non-dev を使用（`--target=prod`）
- 再現性・セキュリティを重視
- CI/CD（GitHub Actions 等）への展開を想定
- Laravel は `src/` 配下
- `.env` はイメージに含めない

---

## 前提条件

- GCP プロジェクトが作成済みであること
- `gcloud` CLI が利用可能であること
- Docker が利用可能であること
- Cloud Run / Artifact Registry が有効化されていること

```bash
gcloud services enable run.googleapis.com artifactregistry.googleapis.com
```

---

## 0. 変数（共通）

```bash
PROJECT_ID="$(gcloud config get-value project)"
REGION="asia-northeast1"

# Artifact Registry
REPOSITORY="laravel-workflow" # 例: laravel-workflow
IMAGE_NAME="laravel-workflow-demo"

# Cloud Run
SERVICE_NAME="laravel-workflow-demo"

# 完成イメージ参照（Artifact Registry）
IMAGE="${REGION}-docker.pkg.dev/${PROJECT_ID}/${REPOSITORY}/${IMAGE_NAME}:latest"
```

---

## 1. Artifact Registry の作成（初回のみ）

```bash
gcloud artifacts repositories create "${REPOSITORY}" \
  --repository-format=docker \
  --location="${REGION}" \
  --description="Laravel Workflow Demo"
```

---

## 2. ビルド～デプロイまで（都度）

### 2-1. Docker イメージのビルド（Cloud Run 用）

> ※ prod は DHI non-dev で動かす想定です。
> DHI pull に認証が必要な場合のみ docker login dhi.io を実施してください。

```bash
# 必要に応じて
docker login dhi.io

docker build \
  --platform=linux/amd64 \
  --target=prod \
  -t "${IMAGE_NAME}:prod" \
  .

# tag 元は :prod
docker tag "${IMAGE_NAME}:prod" "${IMAGE}"
```

### 2-2. Artifact Registryへ push

```bash
gcloud auth configure-docker "${REGION}"-docker.pkg.dev
docker push "${IMAGE}"
```

### 2-3. Cloud Run へ deploy（private 前提）

```bash
gcloud run deploy "${SERVICE_NAME}" \
  --image "${IMAGE}" \
  --region "${REGION}" \
  --platform managed \
  --quiet
```

> --allow-unauthenticated は付けません（private 前提）。

### 2-4. 認証制御

本番では`--allow-unauthenticated`は使用しません。\
Cloud Run Invoker 権限を明示的に付与します。

```bash
gcloud run services add-iam-policy-binding "${SERVICE_NAME}" \
  --region "${REGION}" \
  --member="user:example@example.com" \
  --role="roles/run.invoker"
```

### 2-5. 環境変数の注入

APP_KEY などは Cloud Run 側の環境変数で管理します（イメージに含めません）。

```bash
gcloud run services update "${SERVICE_NAME}" \
  --region "${REGION}" \
  --set-env-vars APP_ENV=production,APP_DEBUG=false,LOG_CHANNEL=stderr,DB_CONNECTION=sqlite
```

---

## 3. 環境変数の設定

Cloud Run のサービス設定画面、または --set-env-vars を使用して以下の環境変数を設定してください。

| Key             | 説明                                    |
| --------------- | --------------------------------------- |
| `APP_ENV`       | `production`                            |
| `APP_DEBUG`     | `false`                                 |
| `APP_KEY`       | `php artisan key:generate --show` で生成 |
| `LOG_CHANNEL`   | `stderr`                                |
| `DB_CONNECTION` | `sqlite`                                |

※ SQLite はコンテナ内ファイルを使用するため永続しません。

---

## 4. SQLite に関する注意

- DB_CONNECTION=sqlite
- database/database.sqlite を使用
- Cloud Run 上ではコンテナ再作成によりデータは失われます（永続化しません）

---

## 5. セキュリティ・運用に関する補足

- local
  - Docker Hardened Images (DHI) の `-dev` イメージを使用しています
- Cloud Run
    - `non-dev` イメージへ移行
    - SBOM / provenance の確認
    - CI/CD での自動 build & deploy

## 6. CI/CD への展開

- GitHub Actions
- Cloud Build
- Self Hosted Runner

CI/CD サンプルは `docs/ci-cd.md`及び`.github/workflows/deploy-cloud-run.yml` を参照してください。

---

## 7. GitHub Actions との整合

- deploy-cloud-run.yml は `--target=prod` を使用
- `--platform=linux/amd64` を指定
- DHI login を `secrets` 経由で実施
- 必須`secrets`が無い場合はデプロイをスキップ

---

## 8. ローカル開発との差分

| 項目       | ローカル         | Cloud Run          |
| ---------- | --------------- | ------------------ |
| target     | dev-local       | prod               |
| base image | official php    | DHI non-dev        |
| env        | --env-file      | Cloud Run env vars |
| storage    | volume mount    | ephemeral          |
| database   | sqlite (volume) | sqlite / Cloud SQL |

---

## 9. トラブルシューティング

### 401 Unauthorized（DHI）

→ docker login dhi.io が必要

### cannot execute binary file

→ --platform=linux/amd64 を指定

### Missing APP_KEY

→ Cloud Run に APP_KEY を設定

### Please provide a valid cache path

→ storage / bootstrap/cache が存在するか確認

---

## 10. 本番用ビルド原則

- RUN を prod ステージでは実行しない
- 依存解決は build ステージで完結
- prod は COPY のみ
- .env を含めない
- 最小権限 IAM