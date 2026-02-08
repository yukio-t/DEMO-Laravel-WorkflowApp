# CI/CD Guide（GitHub Actions）

本ドキュメントでは、本プロジェクトを **GitHub Actions** で **Artifact Registry へ push** → **Cloud Run へ deploy** する CI/CD 方針を説明します。

本リポジトリは「技術サンプル」として、

- 手動デプロイ手順（`docs/deploy-cloud-run.md`）
- 自動化（CI/CD）

を **両方揃える**ことで、理解と運用の両立を目指します。

---

## 1. 手動手順との対応関係

`docs/deploy-cloud-run.md` と CI/CD の対応は以下の通り

| deploy-cloud-run.md | CI/CD（GitHub Actions） |
|---|---|
| Artifact Registry 作成（初回のみ） | 手動（初回だけ） |
| `docker build` | Actions 内で build |
| `docker tag` | Actions 内で tag |
| `docker push` | Actions 内で push |
| `gcloud run deploy --image ...` | Actions 内で deploy |

※ Artifact Registry の作成は頻度が低く、権限も強いため「手動」対応

---

## 2. デプロイ戦略

### ブランチ戦略（例）

- `main`：本番相当（Cloud Run の production サービスへ）
- `staging`：検証（Cloud Run の staging サービスへ）

この技術サンプルでは、`staging`は実装せず、 `main` への push をトリガーにして Cloud Run へデプロイする想定のみとします。

### Cloud Run の公開設定について

- 本プロジェクトでは Cloud Run を **原則 private（unauthenticated なし）** とします。
- CI/CD から `--allow-unauthenticated` を付けない
- CI/CD から `allUsers` への `roles/run.invoker` 付与もしない

---

## 3. Secrets が無い場合は「失敗にせずスキップ」

本プロジェクトでは、以下を要件とします。

- Secrets が未設定、または期限切れなどで利用できない場合
  - **デプロイ処理を実行しない**
  - **ワークフロー自体は失敗にしない**
  - ログに「不足している」ことを明示する

これにより、フォーク環境や学習環境でも CI が壊れず、安全に利用できるようにします。

---

## 4. 必要な Secrets / Variables

### Secrets（GitHub）

以下は GitHub の `Settings > Secrets and variables > Actions` に設定します。

| Key | 用途 |
|---|---|
| `GCP_PROJECT_ID` | GCP プロジェクトID |
| `GCP_REGION` | Cloud Run / Artifact Registry のリージョン（例：`asia-northeast1`） |
| `GCP_WORKLOAD_ID_PROVIDER` | Workload Identity Federation の Provider |
| `GCP_SERVICE_ACCOUNT` | Actions から利用する SA メールアドレス |
| `AR_REPOSITORY` | Artifact Registry のリポジトリ名（例：`laravel-workflow`） |
| `CLOUD_RUN_SERVICE` | Cloud Run サービス名（例：`laravel-workflow-demo`） |

> 認証は **Workload Identity Federation（推奨）** を前提にします。  
> JSON キーは漏洩リスクが高いため原則使いません。

### アプリの環境変数（Cloud Run）

Cloud Run 側に以下を設定（CI/CDでは基本 “触らない”）

- `APP_KEY`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `LOG_CHANNEL=stderr`
- `DB_CONNECTION=sqlite`

---

## 5. デプロイの流れ（CI/CD）

1. Secrets の存在チェック（無ければスキップ）
2. GCP へ認証（Workload Identity Federation）
3. Artifact Registry へログイン
4. Docker build（DHIベース）
5. Docker tag & push
6. Cloud Run deploy（`--image` 指定）

---

## 6. ディレクトリ構成

```text
.github/
  workflows/
    deploy-cloud-run.yml # github action版
docs/
  deploy-cloud-run.md   # 手動版
  ci-cd.md              # CI/CD 方針と前提
```

---

## 7. 補足：DHI と CI/CD

DHI を採用しているため、CI では以下を意識します。

- latest 固定ではなく バージョンタグ固定
- ビルドは再現可能であること
- （SBOM / provenance の検証を追加可能）

---

## 8. ymlに落とし込み

このドキュメントに基づき、以下のように作成

- .github/workflows/deploy-cloud-run.yml
    - Secrets が無ければスキップ
    - Artifact Registry push
    - Cloud Run deploy

---

## 9. GCP 側の設定（Workload Identity Federation / 最小権限）

GitHub ActionsからGCPに**鍵なし（OIDC）**で認証するために、Workload Identity Federation（WIF）を構成します。

### 9.1 変数（手元で設定）

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

### 9.2 サービスアカウント作成（既存のためスキップするが手順として記載）

```bash
gcloud iam service-accounts create "${SA_NAME}" --project "${PROJECT_ID}"
```

### 9.3 Workload Identity Pool 作成

```bash
gcloud iam workload-identity-pools create "${POOL_ID}" \
  --project="${PROJECT_ID}" \
  --location="global" \
  --display-name="GitHub Actions Pool"
```

### 9.4 Workload Identity Provider 作成

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

### 9.5 Provider リソース名（GitHub Secrets に入れる値）を取得

GitHubの`GCP_WORKLOAD_ID_PROVIDER`には、この**provider のフルリソース名**を設定

```bash
gcloud iam workload-identity-pools providers describe "${PROVIDER_ID}" \
  --project="${PROJECT_ID}" \
  --location="global" \
  --workload-identity-pool="${POOL_ID}" \
  --format="value(name)"
```

### 9.6 GitHub リポジトリだけが SA を借用できるようにする

Service Accountに対して、`roles/iam.workloadIdentityUser`を付与\
本サンプルでは、特定リポジトリに限定します。

```bash
WIF_POOL_NAME="projects/${PROJECT_NUMBER}/locations/global/workloadIdentityPools/${POOL_ID}"

gcloud iam service-accounts add-iam-policy-binding "${SA_EMAIL}" \
  --project="${PROJECT_ID}" \
  --role="roles/iam.workloadIdentityUser" \
  --member="principalSet://iam.googleapis.com/${WIF_POOL_NAME}/attribute.repository/${GITHUB_REPO}"
```

### 9.7 SA に付与する最小ロール（Cloud Run デプロイ＋AR push）

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

### 9.8 GitHub Secrets に設定する値

GitHubの`Settings > Secrets and variables > Actions`に次を設定

- GCP_PROJECT_ID = ${PROJECT_ID}
- GCP_REGION = ${REGION}
- AR_REPOSITORY = ${AR_REPO}
- CLOUD_RUN_SERVICE = ${CLOUD_RUN_SERVICE}
- GCP_SERVICE_ACCOUNT = ${SA_EMAIL}
- GCP_WORKLOAD_ID_PROVIDER = 9.5 で取得した provider のフル名

---

## 10. トラブルシュート

### デプロイが 403 / permission denied

- SA に roles/run.developer が付与されているか
- SA に roles/artifactregistry.writer が付与されているか
- roles/iam.workloadIdentityUser の binding が repo 限定で正しく設定されているか
- 変更直後は IAM 反映待ち（数分）