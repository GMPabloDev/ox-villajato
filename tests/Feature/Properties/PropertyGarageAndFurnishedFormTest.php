<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PropertyGarageAndFurnishedFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{bool, bool}>
     */
    public static function answers(): array
    {
        return [
            'con garaje y amueblada' => [true, true],
            'con garaje y sin amueblar' => [true, false],
            'sin garaje y amueblada' => [false, true],
            'sin garaje y sin amueblar' => [false, false],
        ];
    }

    #[DataProvider('answers')]
    public function test_publishing_stores_the_chosen_answers_and_shows_them_in_the_public_page_and_the_panel_detail(bool $hasGarage, bool $isFurnished): void
    {
        Storage::fake('public');

        $agent = User::factory()->agent()->create();

        $response = $this->actingAs($agent)->post(route('properties.store'), $this->publishPayload([
            'has_garage' => $hasGarage,
            'is_furnished' => $isFurnished,
        ]));

        $property = Property::sole();

        $response->assertRedirect(route('properties.show', $property));

        $this->assertSame($hasGarage, $property->has_garage);
        $this->assertSame($isFurnished, $property->is_furnished);

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/show')
                ->where('property.has_garage', $hasGarage)
                ->where('property.is_furnished', $isFurnished)
            );

        $this->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/index')
                ->where('properties.data.0.id', $property->id)
                ->where('properties.data.0.has_garage', $hasGarage)
                ->where('properties.data.0.is_furnished', $isFurnished)
            );
    }

    public function test_publishing_without_choosing_garage_and_furnishing_reports_both_errors_and_saves_nothing(): void
    {
        Storage::fake('public');

        $agent = User::factory()->agent()->create();

        $payload = $this->publishPayload();
        unset($payload['has_garage'], $payload['is_furnished']);

        $response = $this->actingAs($agent)
            ->from(route('panel.properties.index'))
            ->post(route('properties.store'), $payload);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors([
                'has_garage' => 'El campo garaje es obligatorio.',
                'is_furnished' => 'El campo amueblado es obligatorio.',
            ]);

        $this->assertDatabaseCount('properties', 0);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidAnswers(): array
    {
        return [
            'garaje' => ['has_garage', 'El campo garaje debe indicar sí o no.'],
            'amueblado' => ['is_furnished', 'El campo amueblado debe indicar sí o no.'],
        ];
    }

    #[DataProvider('invalidAnswers')]
    public function test_publishing_rejects_an_answer_that_is_not_yes_or_no(string $field, string $message): void
    {
        Storage::fake('public');

        $agent = User::factory()->agent()->create();

        $response = $this->actingAs($agent)
            ->from(route('panel.properties.index'))
            ->post(route('properties.store'), $this->publishPayload([
                $field => 'quizás',
            ]));

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('properties', 0);
    }

    public function test_the_panel_exposes_the_saved_answers_so_the_edit_form_preloads_them(): void
    {
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'has_garage' => true,
            'is_furnished' => false,
        ]);

        $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $this->actingAs($agent)
            ->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/index')
                ->where('properties.data.0.id', $property->id)
                ->where('properties.data.0.has_garage', true)
                ->where('properties.data.0.is_furnished', false)
            );
    }

    public function test_editing_switches_both_answers_and_shows_the_new_values_in_the_public_page_and_the_panel_detail(): void
    {
        Storage::fake('public');

        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa con garaje',
            'status' => 'available',
            'has_garage' => true,
            'is_furnished' => false,
        ]);

        $photo = $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $this->actingAs($agent)
            ->put(route('panel.properties.update', $property), [
                ...$this->editPayload($property),
                'has_garage' => false,
                'is_furnished' => true,
                'photo_order' => [$photo->id],
            ])
            ->assertRedirect(route('properties.show', $property));

        $property->refresh();

        $this->assertFalse($property->has_garage);
        $this->assertTrue($property->is_furnished);

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/show')
                ->where('property.has_garage', false)
                ->where('property.is_furnished', true)
            );

        $this->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/index')
                ->where('properties.data.0.id', $property->id)
                ->where('properties.data.0.has_garage', false)
                ->where('properties.data.0.is_furnished', true)
            );
    }

    public function test_editing_without_choosing_garage_and_furnishing_reports_both_errors_and_keeps_the_saved_answers(): void
    {
        Storage::fake('public');

        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'has_garage' => true,
            'is_furnished' => false,
        ]);

        $photo = $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $payload = [
            ...$this->editPayload($property),
            'photo_order' => [$photo->id],
        ];
        unset($payload['has_garage'], $payload['is_furnished']);

        $response = $this->actingAs($agent)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), $payload);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors([
                'has_garage' => 'El campo garaje es obligatorio.',
                'is_furnished' => 'El campo amueblado es obligatorio.',
            ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'has_garage' => true,
            'is_furnished' => false,
        ]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidEditedAnswers(): array
    {
        return [
            'garaje' => ['has_garage', 'El campo garaje debe indicar sí o no.'],
            'amueblado' => ['is_furnished', 'El campo amueblado debe indicar sí o no.'],
        ];
    }

    #[DataProvider('invalidEditedAnswers')]
    public function test_editing_rejects_an_answer_that_is_not_yes_or_no_and_keeps_the_saved_answers(string $field, string $message): void
    {
        Storage::fake('public');

        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'has_garage' => false,
            'is_furnished' => true,
        ]);

        $photo = $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $response = $this->actingAs($agent)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->editPayload($property),
                $field => 'quizás',
                'photo_order' => [$photo->id],
            ]);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'has_garage' => false,
            'is_furnished' => true,
        ]);
    }

    public function test_the_admin_changes_the_garage_and_furnishing_answers_of_an_agents_property(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'status' => 'available',
            'has_garage' => false,
            'is_furnished' => true,
        ]);

        $photo = $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $this->actingAs($admin)
            ->put(route('panel.properties.update', $property), [
                ...$this->editPayload($property),
                'has_garage' => true,
                'is_furnished' => false,
                'photo_order' => [$photo->id],
            ])
            ->assertRedirect(route('properties.show', $property));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'user_id' => $agent->id,
            'has_garage' => true,
            'is_furnished' => false,
        ]);
    }

    public function test_an_agent_cannot_change_the_garage_and_furnishing_answers_of_another_agents_property(): void
    {
        Storage::fake('public');

        $agent = User::factory()->agent()->create();
        $owner = User::factory()->agent()->create();

        $property = Property::factory()->for($owner)->create([
            'status' => 'available',
            'has_garage' => true,
            'is_furnished' => true,
        ]);

        $photo = $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        $this->actingAs($agent)
            ->put(route('panel.properties.update', $property), [
                ...$this->editPayload($property),
                'has_garage' => false,
                'is_furnished' => false,
                'photo_order' => [$photo->id],
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'user_id' => $owner->id,
            'has_garage' => true,
            'is_furnished' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function publishPayload(array $overrides = []): array
    {
        return array_merge([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa con estacionamiento',
            'description' => 'Casa con espacio de estacionamiento y cocina equipada.',
            'price' => 250000,
            'currency' => 'PEN',
            ...$this->propertyLocation('150122'),
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area' => 120.5,
            'has_garage' => true,
            'is_furnished' => true,
            'photos' => [
                UploadedFile::fake()->image('front.jpg'),
            ],
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function editPayload(Property $property): array
    {
        return [
            'operation' => $property->operation->value,
            'type' => $property->type->value,
            'title' => $property->title,
            'description' => $property->description,
            'price' => $property->price,
            'currency' => $property->currency->value,
            ...$this->propertyLocation($property->district_code),
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'area' => $property->area,
            'has_garage' => $property->has_garage,
            'is_furnished' => $property->is_furnished,
        ];
    }
}
