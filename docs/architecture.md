# Architecture Overview（Workflow）

本ドキュメントでは、本リポジトリにおける**ワークフロー（Workflow）アプリの設計思想と構成**を説明します。\
本サンプルは「業務フローの状態遷移・承認」という**抽象度の高い概念を、Laravel でどのように表現するか**を主眼としています。

---

## 前提

- Cloud Runは`private`前提

## 設計方針

### 1. ワークフローを「状態遷移」として捉える

業務フローは以下の要素で構成されると考えます。

- 状態（State）
- 遷移（Transition）
- 操作主体（Actor）
- 承認（Approval）

本サンプルでは、これらを **明示的な概念として分離**します。

---

### 2. DB 構造は最小限（SQLite）

- 技術サンプルのため SQLite を使用
- 正規化・性能最適化は主目的としない
- 「概念の分離」と「責務の明確化」を優先

---

### 3. Laravel の標準機能を活かす

- Eloquent Model
- Enum
- Policy / Gate

過度なフレームワーク拡張は行いません。

---

## ドメインモデル概要

### Workflow（業務フロー）

業務対象となるEntity。

例：
- 申請書
- 稟議
- 承認依頼

```text
Workflow
 ├─ id
 ├─ title
 ├─ current_state
 └─ created_at
```

---

## State（状態）

Workflow が取りうる状態。

例：
- draft（下書き）
- submitted（申請中）
- approved（承認済）
- rejected（却下）

```php
enum WorkflowState: string
{
    case Draft     = 'draft';
    case Submitted = 'submitted';
    case Approved  = 'approved';
    case Rejected  = 'rejected';
}
```

---

## Transition（遷移）

ある状態から別の状態へ進む操作。\
遷移は、コード上で定義し、DBに状態遷移ルールを持たせない。

```text
draft     → submitted
submitted → approved
submitted → rejected
```


---

## Actor（操作主体）

遷移を実行できる主体。\
Laravelの`User<Model>`と`role`で表現します。

- 申請者
- 承認者
- 管理者


---

## 状態遷移の責務分離

- Controller にロジックを持たせない
- Service クラスに集約
    - Controller：入力とレスポンスのみ
    - Service：業務ルール
    - Model：状態保持

例：
```php
WorkflowService::transition(
    workflow: $workflow,
    to: WorkflowState::Approved,
    actor: $user,
);
```

---

## WorkflowService の役割

主な責務

- 状態遷移が可能か判定：canTransition()
- 操作権限の検証：assertPermission()
- 状態の更新：transition()
- 履歴の記録：recordHistory()

---

## 承認（Approval）の考え方

本プロジェクトでは承認を以下のように定義します。

- 特定の遷移を行うための権限チェック
- 複雑な多段承認は扱わない
- 遷移を実行できるのは「承認者ロール」のみ

---

## 履歴（History）

状態遷移は履歴として保存

- 監査ログの最小形

```text
WorkflowHistory
 ├─ workflow_id
 ├─ from_state
 ├─ to_state
 ├─ acted_by
 └─ created_at
```

---

## 権限設計

- Laravel Policy を使用
- 「状態 × 操作 × ロール」で判定

```php
public function approve(User $user, Workflow $workflow): bool
{
    return $user->isApprover()
        && $workflow->state === WorkflowState::Submitted;
}
```

---

## スコープ外としたもの

本サンプルでは以下を扱いません。

- 多段承認（並列・直列）
- 承認ルートの動的変更
- SLA / 期限管理
- 通知・メール連携