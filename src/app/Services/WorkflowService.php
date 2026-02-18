<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowHistory;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    public function transition(Workflow $workflow, string $toState, User $actor, ?string $comment = null, array $meta = []): Workflow
    {
        return DB::transaction(function () use ($workflow, $toState, $actor, $comment, $meta) {
            $from = $workflow->current_state;

            $workflow->current_state = $toState;
            $workflow->save();

            WorkflowHistory::create([
                'workflow_id' => $workflow->id,
                'from_state'  => $from,
                'to_state'    => $toState,
                'acted_by'    => $actor->id,
                'comment'     => $comment,
                'meta'        => $meta ?: null,
                'created_at'  => now(),
            ]);

            return $workflow;
        });
    }
}
