<?php

namespace Tests\Feature\Seeders;

use App\Models\Department;
use App\Models\District;
use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Models\Province;
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

    public function test_sample_properties_use_catalog_districts_without_confusing_homonyms(): void
    {
        Storage::fake((string) config('properties.photos_disk'));

        User::factory()->agent()->create();

        District::query()->delete();
        Province::query()->delete();
        Department::query()->delete();

        $this->assertDatabaseCount('districts', 0);

        app(SamplePropertiesSeeder::class)->run();

        $this->assertDatabaseCount('districts', 1893);

        $locations = Property::query()
            ->with('district.province.department')
            ->get()
            ->mapWithKeys(fn (Property $property): array => [
                $property->title => [
                    $property->district_code,
                    $property->district->province->department->name,
                    $property->district->province->name,
                    $property->district->name,
                ],
            ])
            ->all();

        $this->assertEquals([
            'Casa familiar con jardín en Miraflores' => ['150122', 'Lima', 'Lima', 'Miraflores'],
            'Departamento con balcón en San Isidro' => ['150131', 'Lima', 'Lima', 'San Isidro'],
            'Casa de playa en Asia' => ['150502', 'Lima', 'Cañete', 'Asia'],
            'Departamento luminoso en Barranco' => ['150104', 'Lima', 'Lima', 'Barranco'],
            'Casa con terraza en Cayma' => ['040103', 'Arequipa', 'Arequipa', 'Cayma'],
            'Departamento amoblado en Yanahuara' => ['040126', 'Arequipa', 'Arequipa', 'Yanahuara'],
            'Casa moderna en Víctor Larco' => ['130111', 'La Libertad', 'Trujillo', 'Victor Larco Herrera'],
        ], $locations);
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
