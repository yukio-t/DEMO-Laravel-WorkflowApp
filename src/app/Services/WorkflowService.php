<?php

namespace App\Services;

use App\Http\Exceptions\InvalidTransitionException;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    public function transition(Workflow $workflow, string $toState, User $actor, ?string $comment = null, array $meta = []): Workflow
    {
        $from = (string) $workflow->current_state;

        if (! $this->canTransition($from, $toState)) throw new InvalidTransitionException($from, $toState);

        return DB::transaction(function () use ($workflow, $from, $toState, $actor, $comment, $meta) {
            $workflow->current_state = $toState;
            $workflow->save();

            $this->recordHistory($workflow, $from, $toState, $actor, $comment, $meta);

            return $workflow;
        });
    }

    /**
     * 状態は変えずに履歴だけ記録
     */
    public function record(Workflow $workflow, User $actor, string $toState, ?string $comment = null, array $meta = [], ?string $fromState = null): void
    {
        $from = $fromState ?? (string) $workflow->current_state;

        $this->recordHistory($workflow, $from, $toState, $actor, $comment, $meta);
    }

    private function canTransition(string $from, string $to): bool
    {
        $map = (array) config('workflow.transitions', []);
        $allowed = $map[$from] ?? [];

        return in_array($to, $allowed, true);
    }

    private function recordHistory(Workflow $workflow, string $from, string $to, User $actor, ?string $comment, array $meta): void
    {
        WorkflowHistory::create([
            'workflow_id' => $workflow->id,
            'from_state'  => $from,
            'to_state'    => $to,
            'acted_by'    => $actor->id,
            'comment'     => $comment,
            'meta'        => $meta ?: null,
            'created_at'  => now(),
        ]);
    }
}
