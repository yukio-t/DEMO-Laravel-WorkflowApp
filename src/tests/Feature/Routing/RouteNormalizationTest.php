<?php

namespace Tests\Feature\Routing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_login_when_guest(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_root_redirects_to_dashboard_when_authenticated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_profile_route_is_not_available(): void
    {
        $this->get('/profile')->assertNotFound();
    }

    public function test_forgot_password_route_is_not_available(): void
    {
        $this->get('/forgot-password')->assertNotFound();
    }
}