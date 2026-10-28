<?php

namespace Tests\Feature\Agents;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AgentInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_sets_password_from_invitation_link_and_logs_in(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('panel.agents.store'), [
            'name' => 'Ana Torres',
            'email' => 'ana@example.com',
        ]);

        $agent = User::where('email', 'ana@example.com')->sole();

        $token = null;

        Notification::assertSentTo(
            $agent,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            },
        );

        $this->post(route('logout'));

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'ana@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => 'ana@example.com',
            'password' => 'new-password-123',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($agent);
        $this->get(route('dashboard'))->assertOk();
    }
}
