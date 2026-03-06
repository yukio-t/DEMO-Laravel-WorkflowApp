# CI/CD Guide（Cloud Run）

本ドキュメントでは、本プロジェクトを GitHub Actions で Cloud Run にデプロイする CI/CD の方針をまとめます。

---

## 1. 方針

- Cloud Run へのデプロイは **Docker イメージ指定方式**
- prod は `--target=prod`（DHI non-dev）でビルド
- GitHub Actions から GCP へは **Workload Identity Federation（OIDC）** で認証（鍵ファイル不要）
- Secrets が未設定の場合、**デプロイ処理はスキップ**し警告を表示

---

## 2. 事前準備（GCP）

- Artifact Registry 作成
- Cloud Run サービス作成（または初回 deploy で作成）
- Workload Identity Federation 設定
- 最小権限の IAM 付与

---

## 3. GitHub Secrets

必要な Secrets：

- `GCP_PROJECT_ID`
- `GCP_REGION`
- `GCP_WORKLOAD_ID_PROVIDER`
- `GCP_SERVICE_ACCOUNT`
- `AR_REPOSITORY`
- `CLOUD_RUN_SERVICE`

DHI　認証：

- `DHI_USERNAME`
- `DHI_TOKEN`

---

## 4. Workflow の挙動（概要）

- Checkout
- Secrets の有無を検査
- OIDC 認証
- Docker build（`--platform=linux/amd64` / `--target=prod`）
- Artifact Registry へ push（SHA / latest）
- Cloud Run へ deploy（private 前提）
- tests（dev-local で build → `php artisan test`）
- deploy は `needs: tests`（テスト失敗時は deploy しない）

---

## 5. private 前提

- `--allow-unauthenticated` は付与しない
- 公開が必要な場合は Cloud Run 側で IAM を調整する

---

## 6. ビルドターゲット

- ローカル:
  - `--target=dev-local`（または dev-local-assets）
  - Official PHP ベース（ローカル互換性重視）
- Cloud Run:
  - `--target=prod`
  - DHI non-dev

### platform 固定

- Cloud Run 互換性を優先し、CI では以下を固定します。
  - `--platform=linux/amd64`

---

## 7. ファイル配置

> 初回 push 時の暴発防止のため、workflow は一時的に拡張子を変えて無効化している場合があります。

```text
Project /
├ readme.md
├ dockerfile
├ .github /
│    └workflows /
│        └ deploy-cloud-run.yml
├ docs /
│      ├ architecture.md
│      ├ ci-cd.md
│      ├ deploy-cloud-run.md
│      └ security.md
└ src/
  (Laravel application)
```

---

## 8. 補足：DHI と CI/CD

DHI を採用しているため、CI では以下を意識します。

- dhi.io は **認証**が必要な場合がある（Secrets 経由で `docker login dhi.io`）
- latest 固定ではなく バージョンタグ固定
- ビルドは再現可能であること
- （SBOM / provenance の検証を追加可能）

---

## 9. ymlに落とし込み

このドキュメントに基づき、以下のように作成
- .github/workflows/ci.yml
  - PR / push で **Docker（dev-local）でテストのみ実行**
  - deploy は行わない（CD は `deploy-cloud-run.yml` に限定）
- .github/workflows/deploy-cloud-run.yml
  - Secrets が無ければスキップ
  - Artifact Registry push
  - Cloud Run deploy

---

## 10. GCP 側の設定（Workload Identity Federation / 最小権限）

GitHub ActionsからGCPに**鍵なし（OIDC）**で認証するために、Workload Identity Federation（WIF）を構成します。

付与ロール：
- roles/artifactregistry.writer（Artifact Registry へ push）
- roles/run.developer（Cloud Run へ deploy）
- roles/iam.serviceAccountUser（Cloud Run が使用する SA を指定・借用する際に必要になることがある）

### 10.1 変数（手元で設定）

```bash
PROJECT_ID="<your-project-id>"
PROJECT_NUMBER="$(gcloud projects describe "${PROJECT_ID}" --format='value(projectNumber)')"

POOL_ID="github"
PROVIDER_ID="github-provider"

GITHUB_OWNER="<your-github-org-or-user>"  # 例: my-org
GITHUB_REPO="<your-github-org-or-user>/<repo>" # 例: my-org/laravel-workflow-demo

SA_NAME="gha-cloud-run-deployer"
SA_EMAIL="${SA_NAME}@${PROJECT_ID}.iam.gserviceaccount.com"

REGION="asia-northeast1"
AR_REPO="laravel-workflow"                # Artifact Registry repo
CLOUD_RUN_SERVICE="laravel-workflow-demo" # Cloud Run service name
```

### 10.2 サービスアカウント作成（既存のためスキップするが手順として記載）

```bash
gcloud iam service-accounts create "${SA_NAME}" --project "${PROJECT_ID}"
```

### 10.3 Workload Identity Pool 作成

```bash
gcloud iam workload-identity-pools create "${POOL_ID}" \
  --project="${PROJECT_ID}" \
  --location="global" \
  --display-name="GitHub Actions Pool"
```

### 10.4 Workload Identity Provider 作成

GitHubのOIDCクレームを属性へマッピングし、少なくとも **org/user で admission を制限**

```bash
gcloud iam workload-identity-pools providers create-oidc "${PROVIDER_ID}" \
  --project="${PROJECT_ID}" \
  --location="global" \
  --workload-identity-pool="${POOL_ID}" \
  --display-name="GitHub Actions Provider" \
  --attribute-mapping="google.subject=assertion.sub,attribute.actor=assertion.actor,attribute.repository=assertion.repository,attribute.repository_owner=assertion.repository_owner" \
  --attribute-condition="assertion.repository_owner == '${GITHUB_OWNER}'" \
  --issuer-uri="https://token.actions.githubusercontent.com"
```

### 10.5 Provider リソース名（GitHub Secrets に入れる値）を取得

GitHubの`GCP_WORKLOAD_ID_PROVIDER`には、この**provider のフルリソース名**を設定

```bash
gcloud iam workload-identity-pools providers describe "${PROVIDER_ID}" \
  --project="${PROJECT_ID}" \
  --location="global" \
  --workload-identity-pool="${POOL_ID}" \
  --format="value(name)"
```

### 10.6 GitHub リポジトリだけが SA を借用できるようにする

Service Accountに対して、`roles/iam.workloadIdentityUser`を付与\
本サンプルでは、特定リポジトリに限定します。

```bash
WIF_POOL_NAME="projects/${PROJECT_NUMBER}/locations/global/workloadIdentityPools/${POOL_ID}"

gcloud iam service-accounts add-iam-policy-binding "${SA_EMAIL}" \
  --project="${PROJECT_ID}" \
  --role="roles/iam.workloadIdentityUser" \
  --member="principalSet://iam.googleapis.com/${WIF_POOL_NAME}/attribute.repository/${GITHUB_REPO}"
```

### 10.7 SA に付与する最小ロール（Cloud Run デプロイ＋AR push）

本プロジェクトでは、CI/CD（GitHub Actions）用の SA（deployer）に対して、**プロジェクト単位の権限付与を避け、リソース単位で最小化**します。

前提：

- deployer SA：GitHub Actions が利用する（ビルド/プッシュ/デプロイ用）
- runtime SA：Cloud Run が実行時に利用する（必要なら AR pull 権限）

ロール：

1. （初回のみ管理者権限でサービスを作成）
2. Artifact Registry（push）: deployer に repo 単位で付与
3. Cloud Run（deploy）: deployer に service 単位で付与
4. ActAs（runtime SA を指定/借用）: runtime SA 単位で付与

付与コマンド例：

```bash
gcloud artifacts repositories add-iam-policy-binding "${AR_REPOSITORY}" \
  --location="${GCP_REGION}" \
  --member="serviceAccount:${DEPLOYER_SA_EMAIL}" \
  --role="roles/artifactregistry.writer"
gcloud run services add-iam-policy-binding "${CLOUD_RUN_SERVICE}" \
  --region="${GCP_REGION}" \
  --member="serviceAccount:${DEPLOYER_SA_EMAIL}" \
  --role="roles/run.developer"
gcloud iam service-accounts add-iam-policy-binding "${RUNTIME_SA_EMAIL}" \
  --member="serviceAccount:${DEPLOYER_SA_EMAIL}" \
  --role="roles/iam.serviceAccountUser"
```

### 10.8 GitHub Secrets に設定する値

GitHubの`Settings > Secrets and variables > Actions`に次を設定

- GCP_PROJECT_ID = ${PROJECT_ID}
- GCP_REGION = ${REGION}
- AR_REPOSITORY = ${AR_REPO}
- CLOUD_RUN_SERVICE = ${CLOUD_RUN_SERVICE}
- GCP_SERVICE_ACCOUNT = ${SA_EMAIL}
- GCP_WORKLOAD_ID_PROVIDER = 10.5 で取得した provider のフル名

---

## 10. トラブルシュート

### デプロイが 403 / permission denied

- SA に roles/run.developer が付与されているか
- SA に roles/artifactregistry.writer が付与されているか
- roles/iam.workloadIdentityUser の binding が repo 限定で正しく設定されているか
- 変更直後は IAM 反映待ち（数分）