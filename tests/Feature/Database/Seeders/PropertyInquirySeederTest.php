<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\Property;
use App\Models\PropertyPhoto;
use Database\Seeders\PropertyInquirySeeder;
use Database\Seeders\SamplePropertiesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyInquirySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_only_creates_inquiries_for_sample_properties(): void
    {
        $sampleProperty = Property::factory()->create();
        $otherProperty = Property::factory()->create();

        PropertyPhoto::factory()->for($sampleProperty)->create([
            'path' => SamplePropertiesSeeder::PHOTO_PATH_PREFIX.'review.jpg',
        ]);

        $this->seed(PropertyInquirySeeder::class);

        $this->assertDatabaseHas('property_inquiries', [
            'property_id' => $sampleProperty->id,
            'email' => 'consulta-ejemplo@example.test',
        ]);
        $this->assertDatabaseMissing('property_inquiries', [
            'property_id' => $otherProperty->id,
            'email' => 'consulta-ejemplo@example.test',
        ]);
    }
}
