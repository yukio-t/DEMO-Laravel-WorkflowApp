<?php

namespace Tests\Feature\Workflow;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_cannot_approve(): void
    {
        $workflow = Workflow::factory()->submitted()->create();

        $applicant = User::factory()->applicant()->create();

        $token = 'test-token';

        $this->actingAs($applicant)
            ->withSession(['_token' => $token])
            ->post(route('workflows.approve', $workflow), ['_token' => $token])
            ->assertForbidden();
    }

    /**
     * approver は submitted を approve できる（302）
     */
    public function test_approver_can_approve_submitted_workflow(): void
    {
        $workflow = Workflow::factory()->submitted()->create();

        $approver = User::factory()->approver()->create();

        $token = 'test-token';

        $this->actingAs($approver)
            ->withSession(['_token' => $token])
            ->post(route('workflows.approve', $workflow), ['_token' => $token])
            ->assertStatus(302);

        $this->assertDatabaseHas('workflows', [
            'id' => $workflow->id,
            'current_state' => 'approved',
        ]);
    }

    /**
     * approve したら履歴が1件増える（to_state=approved）
     */
    public function test_approve_records_history(): void
    {
        $workflow = Workflow::factory()->submitted()->create();

        $approver = User::factory()->approver()->create();

        $token = 'test-token';

        $this->actingAs($approver)
            ->withSession(['_token' => $token])
            ->post(route('workflows.approve', $workflow), ['_token' => $token])
            ->assertStatus(302);

        $this->assertDatabaseHas('workflow_histories', [
            'workflow_id' => $workflow->id,
            'to_state' => 'approved',
            'acted_by' => $approver->id,
        ]);
    }

    /**
     * approver は submitted を reject できる（state + 履歴）
     */
    public function test_approver_can_reject_submitted_workflow(): void
    {
        $workflow = Workflow::factory()->submitted()->create();
        $approver = User::factory()->approver()->create();

        $token = 'test-token';

        $this->actingAs($approver)
            ->withSession(['_token' => $token])
            ->post(
                route('workflows.reject', $workflow), [
                    '_token' => $token,
                    'comment' => 'NG'
                ]
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('workflows.show', $workflow));

        $this->assertDatabaseHas('workflows', [
            'id' => $workflow->id,
            'current_state' => 'rejected',
        ]);

        $this->assertDatabaseHas('workflow_histories', [
            'workflow_id' => $workflow->id,
            'acted_by' => $approver->id,
            'to_state' => 'rejected',
        ]);
    }

    /**
     * applicant は draft を submit できる（state + 履歴）
     */
    public function test_applicant_can_submit_draft_workflow(): void
    {
        $applicant = User::factory()->applicant()->create();

        $workflow = Workflow::factory()
            ->draft()
            ->create(['created_by' => $applicant->id]);

        $token = 'test-token';

        $this->actingAs($applicant)
            ->withSession(['_token' => $token])
            ->post(route('workflows.submit', $workflow), ['_token' => $token])
            ->assertStatus(302);

        $this->assertDatabaseHas('workflows', [
            'id' => $workflow->id,
            'current_state' => 'submitted',
        ]);

        $this->assertDatabaseHas('workflow_histories', [
            'workflow_id' => $workflow->id,
            'to_state' => 'submitted',
            'acted_by' => $applicant->id,
        ]);
    }

    /**
     * applicant は submitted を submit できない（遷移違反を期待）
     */
    public function test_applicant_cannot_submit_when_not_draft(): void
    {
        $applicant = User::factory()->applicant()->create();

        $workflow = Workflow::factory()
            ->submitted()
            ->create(['created_by' => $applicant->id]);

        $token = 'test-token';

        $this->actingAs($applicant)
            ->withSession(['_token' => $token])
            ->post(route('workflows.submit', $workflow), ['_token' => $token])
            ->assertForbidden();

        // 状態が変わってないことを確認
        $this->assertDatabaseHas('workflows', [
            'id' => $workflow->id,
            'current_state' => 'submitted',
        ]);
    }

    public function test_applicant_cannot_view_others_workflow(): void
    {
        $owner = User::factory()->applicant()->create();
        $other = User::factory()->applicant()->create();

        $workflow = Workflow::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($other)
            ->get(route('workflows.show', $workflow))
            ->assertForbidden();
    }

    public function test_applicant_can_view_create_form(): void
    {
        $user = User::factory()->applicant()->create();

        $this->actingAs($user)
            ->get(route('workflows.create'))
            ->assertOk();
    }
}