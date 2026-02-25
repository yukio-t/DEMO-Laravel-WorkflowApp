<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Http\Exceptions\InvalidTransitionException;
use App\Http\Requests\RejectWorkflowRequest;
use App\Http\Requests\StoreWorkflowRequest;
use App\Services\WorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkflowController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Workflow::query()
            ->with('creator')
            ->orderByDesc('id');

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
        $workflow->load(['creator', 'histories.actor']);

        return view('workflows.show', [
            'workflow' => $workflow,
        ]);
    }

    public function submit(Workflow $workflow, WorkflowService $service, Request $request): RedirectResponse
    {
        try {
            $service->transition($workflow, 'submitted', $request->user(), 'Submitted', ['action' => 'submit']);
            
        } catch (InvalidTransitionException $e) {
            return back()
                ->withErrors(['transition' => "Transition not allowed: {$e->from} → {$e->to}"])
                ->withInput();
        }

        return redirect()->route('workflows.show', $workflow)->with('status', 'Submitted.');
    }

    public function approve(Workflow $workflow, WorkflowService $service, Request $request): RedirectResponse
    {
        try {
            $service->transition($workflow, 'approved', $request->user(), 'Approved', ['action' => 'approve']);

        } catch (InvalidTransitionException $e) {
            return back()
                ->withErrors(['transition' => "Transition not allowed: {$e->from} → {$e->to}"])
                ->withInput();
        }

        return redirect()->route('workflows.show', $workflow)->with('status', 'Approved.');
    }

    public function reject(Workflow $workflow, WorkflowService $service, RejectWorkflowRequest $request): RedirectResponse
    {
        
        try {
            $service->transition($workflow, 'rejected', $request->user(), $request->string('comment')->toString(), ['action' => 'reject']);

        } catch (InvalidTransitionException $e) {
            return back()
                ->withErrors(['transition' => "Transition not allowed: {$e->from} → {$e->to}"])
                ->withInput();
        }
        return redirect()->route('workflows.show', $workflow)->with('status', 'Rejected.');
    }

    public function create(): View
    {
        return view('workflows.create');
    }

    public function store(StoreWorkflowRequest $request, WorkflowService $service): RedirectResponse
    {
        $workflow = Workflow::create([
            'public_id' => (string) Str::ulid(),
            'title' => $request->string('title')->toString(),
            'body' => $request->input('body'),
            'current_state' => config('workflow.initial_state', 'draft'),
            'created_by' => $request->user()->id,
        ]);

        // 状態は変えない、履歴だけ作る
        $service->record($workflow, $request->user(), $workflow->current_state, 'Created', ['action' => 'create'], fromState: null);

        return redirect()->route('workflows.show', $workflow)->with('status', 'Created.');
    }
}
