<?php

namespace Tests\Feature\Workflow;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowViewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * applicant は自分の workflow を見れる
     */
    public function test_applicant_can_view_own_workflow(): void
    {
        $applicant = User::factory()->applicant()->create();

        $workflow = Workflow::factory()->create([
            'created_by' => $applicant->id,
        ]);

        $this->actingAs($applicant)
            ->get(route('workflows.show', $workflow))
            ->assertOk();
    }

    /**
     * applicant は他人の workflow を見れない（403）
     */
    public function test_applicant_cannot_view_others_workflow(): void
    {
        $owner = User::factory()->applicant()->create();
        $other = User::factory()->applicant()->create();

        $workflow = Workflow::factory()->create([
            'created_by' => $owner->id,
        ]);

        $this->actingAs($other)
            ->get(route('workflows.show', $workflow))
            ->assertForbidden();
    }

    /**
     * approver は他人の workflow を見れる
     */
    public function test_approver_can_view_others_workflow(): void
    {
        $owner = User::factory()->applicant()->create();
        $approver = User::factory()->approver()->create();

        $workflow = Workflow::factory()
            ->submitted()
            ->create([
                'created_by' => $owner->id,
            ]);

        $this->actingAs($approver)
            ->get(route('workflows.show', $workflow))
            ->assertOk();
    }

    /**
     * admin は他人の workflow を見れる
     */
    public function test_admin_can_view_others_workflow(): void
    {
        $owner = User::factory()->applicant()->create();
        $admin = User::factory()->admin()->create();

        $workflow = Workflow::factory()->create([
            'created_by' => $owner->id,
        ]);

        $this->actingAs($admin)
            ->get(route('workflows.show', $workflow))
            ->assertOk();
    }

    /**
     * applicant は create 画面OK
     */
    public function test_applicant_can_view_create_form(): void
    {
        $applicant = User::factory()->applicant()->create();

        $this->actingAs($applicant)
            ->get(route('workflows.create'))
            ->assertOk();
    }

    /**
     * approver は create 画面NG（403）
     */
    public function test_approver_cannot_view_create_form(): void
    {
        $approver = User::factory()->approver()->create();

        $this->actingAs($approver)
            ->get(route('workflows.create'))
            ->assertForbidden();
    }

    /**
     * admin は create 画面OK
     */
    public function test_admin_can_view_create_form(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('workflows.create'))
            ->assertOk();
    }

    /**
     * applicant は workflow を作成できる
     */
    public function test_applicant_can_store_workflow(): void
    {
        $applicant = User::factory()->applicant()->create();

        $token = 'test-token';

        $this->actingAs($applicant)
            ->withSession(['_token' => $token])
            ->post(route('workflows.store'), [
                '_token' => $token,
                'title' => 'Test Workflow',
                'body' => 'Test body',
            ])
            ->assertSessionHasNoErrors()
            ->assertStatus(302);

        $this->assertDatabaseHas('workflows', [
            'title' => 'Test Workflow',
            'created_by' => $applicant->id,
            'current_state' => 'draft', // default
        ]);
    }

    /**
     * approver は store できない（403）
     */
    public function test_approver_cannot_store_workflow(): void
    {
        $approver = User::factory()->approver()->create();

        $token = 'test-token';

        $this->actingAs($approver)
            ->withSession(['_token' => $token])
            ->post(route('workflows.store'), [
                '_token' => $token,
                'title' => 'X',
            ])
            ->assertForbidden();
    }

    /**
     * admin は store できる
     */
    public function test_admin_can_store_workflow(): void
    {
        $admin = User::factory()->admin()->create();

        $token = 'test-token';

        $this->actingAs($admin)
            ->withSession(['_token' => $token])
            ->post(route('workflows.store'), [
                '_token' => $token,
                'title' => 'Admin Workflow',
            ])
            ->assertSessionHasNoErrors()
            ->assertStatus(302);

        $this->assertDatabaseHas('workflows', [
            'title' => 'Admin Workflow',
            'created_by' => $admin->id,
        ]);
    }
}