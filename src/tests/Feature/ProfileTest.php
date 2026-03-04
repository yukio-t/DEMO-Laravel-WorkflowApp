<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_not_available(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertNotFound();
    }

    public function test_profile_information_cannot_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ])
            ->assertNotFound();
    }

    public function test_user_cannot_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ])
            ->assertNotFound();
    }
}
