<?php

namespace Tests\Feature\Database;

use App\Models\Property;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class PropertyGarageAndFurnishedMigrationTest extends TestCase
{
    use DatabaseMigrations;

    private const MIGRATION_PATH = 'database/migrations/2026_11_15_020025_add_has_garage_and_is_furnished_to_properties_table.php';

    public function test_a_later_photo_deletion_failure_does_not_leave_broken_references(): void
    {
        Storage::fake('public');
        config(['properties.photos_disk' => 'public']);
        $disk = Storage::disk('public');
        $property = Property::factory()->create();
        foreach (['properties/first.jpg', 'properties/second.jpg'] as $position => $path) {
            $property->photos()->create(['path' => $path, 'position' => $position + 1]);
            $disk->put($path, 'photo');
        }
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION_PATH])->assertExitCode(0);

        $failingDisk = \Mockery::mock(FilesystemAdapter::class);
        $failingDisk->shouldReceive('delete')->andReturnUsing(
            fn (string $path): bool => $path === 'properties/second.jpg' ? false : $disk->delete($path),
        );
        Storage::shouldReceive('disk')->with('public')->andReturn($failingDisk);
        Log::spy();

        $this->artisan('migrate', ['--path' => self::MIGRATION_PATH])->assertExitCode(0);
        $this->artisan('migrate', ['--path' => self::MIGRATION_PATH])->assertExitCode(0);

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $context['path'] === 'properties/second.jpg',
        );

        $disk->assertMissing('properties/first.jpg');
        $disk->assertExists('properties/second.jpg');
        $this->assertDatabaseCount('property_photos', 0);
        $this->assertDatabaseCount('properties', 0);
        $this->assertTrue(Schema::hasColumn('properties', 'has_garage'));
    }

    public function test_the_migration_never_deletes_properties_outside_development_and_testing(): void
    {
        Storage::fake('public');
        config(['properties.photos_disk' => 'public']);

        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa con consulta histórica',
        ]);
        $property->photos()->create(['path' => 'properties/cover.jpg', 'position' => 1]);
        Storage::disk('public')->put('properties/cover.jpg', 'photo');

        $inquiry = $property->inquiries()->create([
            'property_title' => $property->title,
            'publisher_id' => $agent->id,
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Quisiera coordinar una visita.',
        ]);

        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION_PATH])->assertExitCode(0);

        $this->assertFalse(Schema::hasColumn('properties', 'has_garage'));
        $this->assertFalse(Schema::hasColumn('properties', 'is_furnished'));

        $storedProperty = (array) DB::table('properties')->where('id', $property->id)->sole();

        $this->app->instance('env', 'production');

        try {
            $this->artisan('migrate', ['--path' => self::MIGRATION_PATH, '--force' => true])->run();

            $this->fail('La migración debió detenerse fuera de desarrollo y pruebas.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('plan de migración', $exception->getMessage());
        }

        Storage::disk('public')->assertExists('properties/cover.jpg');
        $this->assertDatabaseCount('property_photos', 1);
        $this->assertDatabaseCount('properties', 1);
        $this->assertSame(
            $storedProperty,
            (array) DB::table('properties')->where('id', $property->id)->sole(),
        );
        $this->assertFalse(Schema::hasColumn('properties', 'has_garage'));
        $this->assertFalse(Schema::hasColumn('properties', 'is_furnished'));

        $this->app->instance('env', 'testing');

        $this->artisan('migrate', ['--path' => self::MIGRATION_PATH])->assertExitCode(0);

        Storage::disk('public')->assertMissing('properties/cover.jpg');
        $this->assertDatabaseCount('properties', 0);
        $this->assertDatabaseHas('property_inquiries', [
            'id' => $inquiry->id,
            'property_id' => $property->id,
            'property_title' => 'Casa con consulta histórica',
            'publisher_id' => $agent->id,
        ]);
        $this->assertTrue(Schema::hasColumn('properties', 'has_garage'));
        $this->assertTrue(Schema::hasColumn('properties', 'is_furnished'));
    }
}
