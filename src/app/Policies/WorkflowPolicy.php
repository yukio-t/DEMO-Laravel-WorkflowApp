<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Auth\Access\Response;

class WorkflowPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // applicant/approver/admin 全員OK（一覧自体はOK、見える範囲は query 側で絞る）
        return in_array($user->role, ['applicant', 'approver', 'admin'], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Workflow $workflow): bool
    {
        // admin は全て
        if ($user->role === 'admin') return true;

        // approver は閲覧制限
        if ($user->role === 'approver') return in_array($workflow->current_state, ['submitted', 'approved', 'rejected'], true);

        // applicant は自分のものだけ
        return $workflow->created_by === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['applicant', 'admin'], true);
    }

    public function submit(User $user, Workflow $workflow): bool
    {
        // admin は全て
        if ($user->role === 'admin') return true;

        // applicant は自分の draft のみ提出可
        return $user->role === 'applicant'
            && $workflow->created_by === $user->id
            && $workflow->current_state === 'draft';
    }

    public function approve(User $user, Workflow $workflow): bool
    {
        // admin は全て
        if ($user->role === 'admin') return true;

        // approver は可
        return $user->role === 'approver'
            && $workflow->current_state === 'submitted';
    }

    public function reject(User $user, Workflow $workflow): bool
    {
        return $this->approve($user, $workflow);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Workflow $workflow): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Workflow $workflow): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Workflow $workflow): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Workflow $workflow): bool
    {
        return false;
    }
}
