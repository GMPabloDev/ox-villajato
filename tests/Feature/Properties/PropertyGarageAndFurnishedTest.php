<?php

namespace Tests\Feature\Properties;

use App\Enums\Currency;
use App\Enums\PropertyOperation;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PropertyGarageAndFurnishedTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_property_keeps_the_garage_and_furnishing_answers_stored_for_it(): void
    {
        $agent = User::factory()->agent()->create();

        $garageWithoutFurnishing = Property::factory()->for($agent)->create([
            'has_garage' => true,
            'is_furnished' => false,
        ]);
        $furnishingWithoutGarage = Property::factory()->for($agent)->create([
            'has_garage' => false,
            'is_furnished' => true,
        ]);

        $storedGarageWithoutFurnishing = Property::query()->findOrFail($garageWithoutFurnishing->id);
        $storedFurnishingWithoutGarage = Property::query()->findOrFail($furnishingWithoutGarage->id);

        $this->assertSame(true, $storedGarageWithoutFurnishing->has_garage);
        $this->assertSame(false, $storedGarageWithoutFurnishing->is_furnished);
        $this->assertSame(false, $storedFurnishingWithoutGarage->has_garage);
        $this->assertSame(true, $storedFurnishingWithoutGarage->is_furnished);
    }

    #[DataProvider('unansweredFields')]
    public function test_a_property_cannot_be_stored_without_answering_whether_it_has_a_garage_or_is_furnished(string $missingField): void
    {
        $agent = User::factory()->agent()->create();

        $attributes = [
            'user_id' => $agent->id,
            'operation' => PropertyOperation::Sale->value,
            'status' => PropertyStatus::Available->value,
            'type' => PropertyType::House->value,
            'title' => 'Casa sin respuesta',
            'description' => 'Casa publicada sin indicar garaje ni amueblado.',
            'price' => 250000,
            'currency' => Currency::Pen->value,
            'district_code' => '150101',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area' => 120,
            'has_garage' => false,
            'is_furnished' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        unset($attributes[$missingField]);

        try {
            DB::table('properties')->insert($attributes);

            $this->fail("La propiedad se guardó sin responder [{$missingField}].");
        } catch (QueryException $exception) {
            $this->assertStringContainsString($missingField, $exception->getMessage());
        }

        $this->assertDatabaseMissing('properties', ['title' => 'Casa sin respuesta']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unansweredFields(): array
    {
        return [
            'garaje' => ['has_garage'],
            'amueblado' => ['is_furnished'],
        ];
    }
}
