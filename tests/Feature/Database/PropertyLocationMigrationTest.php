<?php

namespace Tests\Feature\Database;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PropertyLocationMigrationTest extends TestCase
{
    use DatabaseMigrations;

    private const MIGRATION_PATH = 'database/migrations/2026_11_11_002801_replace_property_city_and_zone_with_district_code.php';

    public function test_the_migration_never_deletes_properties_outside_development_and_testing(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa con consulta histórica',
        ]);
        $photo = $property->photos()->create([
            'path' => 'properties/'.$property->id.'-1.jpg',
            'position' => 1,
        ]);
        $inquiry = $property->inquiries()->create([
            'property_title' => $property->title,
            'publisher_id' => $agent->id,
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Quisiera coordinar una visita.',
        ]);

        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION_PATH])->assertExitCode(0);

        $this->assertFalse(Schema::hasColumn('properties', 'district_code'));
        $this->assertTrue(Schema::hasColumn('properties', 'city'));
        $this->assertTrue(Schema::hasColumn('properties', 'zone'));

        $this->app->instance('env', 'production');

        try {
            $this->artisan('migrate', ['--path' => self::MIGRATION_PATH, '--force' => true])->run();

            $this->fail('La migración debió detenerse fuera de desarrollo y pruebas.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('plan de migración', $exception->getMessage());
        }

        $this->assertDatabaseHas('properties', ['id' => $property->id]);
        $this->assertDatabaseHas('property_photos', ['id' => $photo->id]);
        $this->assertDatabaseHas('property_inquiries', [
            'id' => $inquiry->id,
            'property_id' => $property->id,
            'property_title' => 'Casa con consulta histórica',
            'publisher_id' => $agent->id,
        ]);
        $this->assertFalse(Schema::hasColumn('properties', 'district_code'));

        $this->app->instance('env', 'testing');

        $this->artisan('migrate', ['--path' => self::MIGRATION_PATH])->assertExitCode(0);

        $this->assertDatabaseCount('properties', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('property_inquiries', 1);
        $this->assertDatabaseHas('property_inquiries', [
            'id' => $inquiry->id,
            'property_id' => $property->id,
            'property_title' => 'Casa con consulta histórica',
            'publisher_id' => $agent->id,
        ]);
        $this->assertDatabaseMissing('property_photos', ['id' => $photo->id]);
        $this->assertDatabaseCount('districts', 1893);
        $this->assertTrue(Schema::hasColumn('properties', 'district_code'));
        $this->assertFalse(Schema::hasColumn('properties', 'city'));
        $this->assertFalse(Schema::hasColumn('properties', 'zone'));
    }
}
