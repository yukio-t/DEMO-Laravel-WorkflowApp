# Cloud Run Deployment Guide（Complete）

本ドキュメントでは、本プロジェクトを**GCP Cloud Run**に**Docker イメージ指定方式**でデプロイする手順を示します。

- Docker Hardened Images（DHI）を使用
- 再現性・セキュリティを重視
- CI/CD（GitHub Actions 等）への展開を想定

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

## 1. Artifact Registry の作成（初回のみ）

```bash
gcloud artifacts repositories create laravel-workflow \
  --repository-format=docker \
  --location=asia-northeast1 \
  --description="Laravel Workflow Demo"
```

---

## 2. ビルド～デプロイまで（都度）

### 1. Docker イメージのビルド
```bash
PROJECT_ID=$(gcloud config get-value project)
IMAGE="asia-northeast1-docker.pkg.dev/${PROJECT_ID}/laravel-workflow/laravel-workflow-demo:latest"

docker build --target=prod -t laravel-workflow-demo .
docker tag laravel-workflow-demo "${IMAGE}"
```

### 2. Artifact Registryへ push
```bash
gcloud auth configure-docker asia-northeast1-docker.pkg.dev
docker push "${IMAGE}"
```

### 3. Cloud Run へデプロイ（イメージ指定）
```bash
gcloud run deploy laravel-workflow-demo \
  --image "${IMAGE}" \
  --region asia-northeast1 \
  --platform managed

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
- database/database.sqlite はコンテナ内に存在します
- Cloud Run のスケール・再起動によりデータは失われます
- 本構成は 技術サンプル用途を想定しています

---

## 5. セキュリティ・運用に関する補足
- local
  - Docker Hardened Images (DHI) の `-dev` イメージを使用しています
- Cluod Run
    - `non-dev` イメージへ移行
    - SBOM / provenance の確認
    - CI/CD での自動 build & deploy

## 6. CI/CD への展開
本手順は以下にそのまま展開可能です。

- GitHub Actions
- Cloud Build
- Self Hosted Runner

CI/CD サンプルは `ci-cd.md`及び`deploy-cloud-run.yml` を参照してください。