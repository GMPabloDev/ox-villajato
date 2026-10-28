<?php

namespace Tests\Feature\Agents;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AgentPropertyAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function propertyManagementRoutes(): array
    {
        return [
            'publish form' => ['get', 'properties.create', false],
            'publish property' => ['post', 'properties.store', false],
            'panel list' => ['get', 'panel.properties.index', false],
            'edit form' => ['get', 'panel.properties.edit', true],
            'close property' => ['patch', 'panel.properties.close', true],
            'reopen property' => ['patch', 'panel.properties.reopen', true],
            'update property' => ['put', 'panel.properties.update', true],
        ];
    }

    #[DataProvider('propertyManagementRoutes')]
    public function test_agent_cannot_access_property_management(
        string $method,
        string $routeName,
        bool $needsProperty,
    ): void {
        $agent = User::factory()->agent()->create();

        $parameters = $needsProperty
            ? ['property' => Property::factory()->create()]
            : [];

        $response = $this->actingAs($agent)->call($method, route($routeName, $parameters));

        $response->assertForbidden();

        if ($routeName === 'properties.store') {
            $this->assertDatabaseCount('properties', 0);
        }
    }
}
