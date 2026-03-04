<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_is_not_available(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/confirm-password')
            ->assertNotFound();
    }

    public function test_password_cannot_be_confirmed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/confirm-password', [
                'password' => 'password',
            ])
            ->assertNotFound();
    }
}
