<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Services\WorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Workflow::class);

        $user = $request->user();

        $query = Workflow::query()
            ->with('creator')
            ->orderByDesc('id');

        // 「何を見せるか」は Policy の思想に沿って role で最小絞り（デモとして分かりやすさ優先）
        if ($user->role === 'applicant') {
            $query->where('created_by', $user->id);
        } elseif ($user->role === 'approver') {
            $query->where('current_state', 'submitted');
        }

        $workflows = $query->get();

        return view('dashboard', [
            'workflows' => $workflows,
            'role' => $user->role,
        ]);
    }

    public function show(Workflow $workflow): View
    {
        $this->authorize('view', $workflow);

        $workflow->load(['creator', 'histories.actor']);

        return view('workflows.show', [
            'workflow' => $workflow,
        ]);
    }

    public function submit(Workflow $workflow, WorkflowService $service, Request $request): RedirectResponse
    {
        $this->authorize('submit', $workflow);

        $service->transition($workflow, 'submitted', $request->user(), 'Submitted', ['action' => 'submit']);

        return redirect()->route('workflows.show', $workflow)->with('status', 'Submitted.');
    }

    public function approve(Workflow $workflow, WorkflowService $service, Request $request): RedirectResponse
    {
        $this->authorize('approve', $workflow);

        $service->transition($workflow, 'approved', $request->user(), 'Approved', ['action' => 'approve']);

        return redirect()->route('workflows.show', $workflow)->with('status', 'Approved.');
    }

    public function reject(Workflow $workflow, WorkflowService $service, Request $request): RedirectResponse
    {
        $this->authorize('reject', $workflow);

        $comment = (string) $request->input('comment', 'Rejected');

        // デモでも comment を最低限入れておく
        $service->transition($workflow, 'rejected', $request->user(), $comment, ['action' => 'reject']);

        return redirect()->route('workflows.show', $workflow)->with('status', 'Rejected.');
    }
}
