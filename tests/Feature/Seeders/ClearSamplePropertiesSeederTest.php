<?php

namespace Tests\Feature\Seeders;

use App\Models\Property;
use App\Models\User;
use Database\Seeders\ClearSamplePropertiesSeeder;
use Database\Seeders\SamplePropertiesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClearSamplePropertiesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleaner_removes_seeded_properties_and_photos_but_preserves_other_data(): void
    {
        $photosDisk = (string) config('properties.photos_disk');
        Storage::fake($photosDisk);

        $agent = User::factory()->agent()->create();

        app(SamplePropertiesSeeder::class)->run();

        $unrelatedProperty = Property::factory()->create([
            'user_id' => $agent->id,
            'title' => 'Propiedad ingresada manualmente',
        ]);

        $unrelatedProperty->photos()->create([
            'path' => 'properties/manual.jpg',
            'position' => 1,
        ]);

        Storage::disk($photosDisk)->put('properties/manual.jpg', 'imagen manual');

        app(ClearSamplePropertiesSeeder::class)->run();

        $this->assertDatabaseCount('properties', 1);
        $this->assertDatabaseCount('property_photos', 1);
        $this->assertModelExists($unrelatedProperty);
        $this->assertModelExists($agent);
        Storage::disk($photosDisk)->assertDirectoryEmpty(SamplePropertiesSeeder::PHOTO_DIRECTORY);
        Storage::disk($photosDisk)->assertExists('properties/manual.jpg');
    }
}
