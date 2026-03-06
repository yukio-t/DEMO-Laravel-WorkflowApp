<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_is_not_available(): void
    {
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_reset_password_link_cannot_be_requested(): void
    {
        $this->post('/forgot-password', [
            'email' => 'test@example.com',
        ])->assertNotFound();
    }

    public function test_reset_password_screen_is_not_available(): void
    {
        $this->get('/reset-password/fake-token')->assertNotFound();
    }

    public function test_password_cannot_be_reset(): void
    {
        $this->post('/reset-password', [
            'token' => 'fake-token',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }
}
