<?php

namespace Tests\Feature\Properties;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublishPropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_member_can_open_the_publish_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('properties.create'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/create')
            );
    }
}
