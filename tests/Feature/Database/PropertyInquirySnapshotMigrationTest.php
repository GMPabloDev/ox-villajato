<?php

namespace Tests\Feature\Database;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PropertyInquirySnapshotMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_existing_inquiry_is_backfilled_for_admin_history(): void
    {
        $admin = User::factory()->admin()->create();
        $publisher = User::factory()->agent()->create();
        $title = 'Casa consultada antes de la migración';
        $property = Property::factory()->for($publisher)->create([
            'title' => $title,
        ]);
        $migrationPath = 'database/migrations/2026_10_29_192725_add_property_snapshots_to_property_inquiries_table.php';

        $this->artisan('migrate:rollback', ['--path' => $migrationPath])
            ->assertExitCode(0);

        $inquiryId = DB::table('property_inquiries')->insertGetId([
            'property_id' => $property->id,
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Quisiera coordinar una visita.',
            'created_at' => '2026-10-29 12:00:00',
            'updated_at' => '2026-10-29 12:00:00',
        ]);

        $this->artisan('migrate', ['--path' => $migrationPath])
            ->assertExitCode(0);

        $this->assertDatabaseHas('property_inquiries', [
            'id' => $inquiryId,
            'property_id' => $property->id,
            'property_title' => $title,
            'publisher_id' => $publisher->id,
        ]);

        $this->actingAs($admin)
            ->get(route('panel.inquiries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 1)
                ->where('canSeeAllInquiries', true)
                ->where('inquiries.data.0.id', $inquiryId)
                ->where('inquiries.data.0.property.title', $title)
                ->where('inquiries.data.0.name', 'Ana Pérez')
                ->where('inquiries.data.0.email', 'ana@example.com')
                ->where('inquiries.data.0.phone', '+51 987 654 321')
                ->where('inquiries.data.0.message', 'Quisiera coordinar una visita.')
            );
    }
}
