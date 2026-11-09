<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyInquiry as PropertyInquiryModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PanelNavInquiryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_counts_only_the_inquiries_from_their_properties(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();
        $agentProperty = Property::factory()->for($agent)->create();
        $otherProperty = Property::factory()->for($otherAgent)->create();

        PropertyInquiryModel::factory()->count(2)->for($agentProperty)->create();
        PropertyInquiryModel::factory()->for($otherProperty)->create();

        $this->actingAs($agent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nav.inquiryCount', 2));
    }

    public function test_admin_counts_the_inquiries_from_every_property(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->agent()->create();
        $adminProperty = Property::factory()->for($admin)->create();
        $agentProperty = Property::factory()->for($agent)->create();

        PropertyInquiryModel::factory()->count(3)->for($agentProperty)->create();
        PropertyInquiryModel::factory()->for($adminProperty)->create();

        $this->actingAs($admin)
            ->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nav.inquiryCount', 4));
    }

    public function test_guest_pages_share_an_empty_count(): void
    {
        $property = Property::factory()->create();
        PropertyInquiryModel::factory()->for($property)->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nav.inquiryCount', 0));
    }
}
