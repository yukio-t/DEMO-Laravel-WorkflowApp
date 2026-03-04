<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_is_not_available(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/verify-email')
            ->assertNotFound();
    }

    public function test_email_verification_endpoint_is_not_available(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake([Verified::class]);

        // Breeze/Jetstream の典型パス（このデモでは塞ぐ前提）
        $path = '/email/verify/'.$user->id.'/'.sha1($user->email);

        $this->actingAs($user)
            ->get($path)
            ->assertNotFound();

        Event::assertNotDispatched(Verified::class);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_is_not_verified_with_invalid_hash_even_if_endpoint_is_hit(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake([Verified::class]);

        $path = '/email/verify/'.$user->id.'/'.sha1('wrong-email');

        $this->actingAs($user)
            ->get($path)
            ->assertNotFound();

        Event::assertNotDispatched(Verified::class);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
