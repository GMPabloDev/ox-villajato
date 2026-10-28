<?php

namespace Tests\Feature\Agents;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AgentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_cannot_open_the_agent_creation_screen(): void
    {
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)
            ->get(route('panel.agents.create'))
            ->assertForbidden();
    }

    public function test_agent_cannot_create_an_agent_account(): void
    {
        Notification::fake();

        $agent = User::factory()->agent()->create();

        $response = $this->actingAs($agent)->post(route('panel.agents.store'), [
            'name' => 'Otra Persona',
            'email' => 'otra@example.com',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        Notification::assertNothingSent();
    }

    public function test_guest_is_redirected_to_login_when_opening_the_agent_creation_screen(): void
    {
        $this->get(route('panel.agents.create'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_create_an_agent_account(): void
    {
        Notification::fake();

        $this->post(route('panel.agents.store'), [
            'name' => 'Otra Persona',
            'email' => 'otra@example.com',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        Notification::assertNothingSent();
    }
}
