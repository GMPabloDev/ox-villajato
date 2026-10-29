<?php

namespace Tests\Feature\Policies;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_any_property(): void
    {
        $admin = User::factory()->admin()->create();
        $property = Property::factory()->create();

        $this->assertTrue($admin->can('update', $property));
    }

    public function test_agent_can_update_their_own_property(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create();

        $this->assertTrue($agent->can('update', $property));
    }

    public function test_agent_cannot_update_another_agents_property(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();
        $property = Property::factory()->for($otherAgent)->create();

        $this->assertFalse($agent->can('update', $property));
    }
}
