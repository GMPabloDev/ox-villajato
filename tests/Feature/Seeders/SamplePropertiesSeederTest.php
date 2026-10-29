<?php

namespace Tests\Feature\Seeders;

use App\Models\PropertyPhoto;
use App\Models\User;
use Database\Seeders\SamplePropertiesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class SamplePropertiesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_varied_properties_with_distinct_cover_photos_and_reuses_existing_agents(): void
    {
        $photosDisk = (string) config('properties.photos_disk');
        Storage::fake($photosDisk);

        $firstAgent = User::factory()->agent()->create();
        $secondAgent = User::factory()->agent()->create();

        app(SamplePropertiesSeeder::class)->run();
        app(SamplePropertiesSeeder::class)->run();

        $this->assertDatabaseCount('properties', 7);
        $this->assertDatabaseCount('property_photos', 21);
        $this->assertDatabaseCount('users', 2);
        $this->assertSame(4, $firstAgent->properties()->count());
        $this->assertSame(3, $secondAgent->properties()->count());

        $coverPaths = PropertyPhoto::query()
            ->where('position', 1)
            ->orderBy('path')
            ->pluck('path')
            ->all();

        $coverFileNames = array_map(
            static fn (string $path): string => basename($path),
            $coverPaths,
        );
        sort($coverFileNames);

        $this->assertSame([
            'casa.jpeg',
            'casa2.jpeg',
            'casa3.jpeg',
            'casa4.jpeg',
            'casa5.jpeg',
            'casa6.jpeg',
            'casa7.jpeg',
        ], $coverFileNames);

        Storage::disk($photosDisk)->assertExists($coverPaths);
        $this->assertCount(21, Storage::disk($photosDisk)->allFiles(SamplePropertiesSeeder::PHOTO_DIRECTORY));
    }

    public function test_seeder_requires_an_existing_agent_without_creating_partial_data(): void
    {
        $photosDisk = (string) config('properties.photos_disk');
        Storage::fake($photosDisk);

        try {
            app(SamplePropertiesSeeder::class)->run();
            $this->fail('Se esperaba una excepción cuando no hay agentes.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'SamplePropertiesSeeder necesita al menos un agente existente.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('properties', 0);
        $this->assertDatabaseCount('property_photos', 0);
        Storage::disk($photosDisk)->assertDirectoryEmpty(SamplePropertiesSeeder::PHOTO_DIRECTORY);
    }
}
