<?php

namespace Tests\Feature\Properties;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PropertyValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_without_required_data_returns_to_the_form_with_errors_and_saves_nothing(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->post(route('properties.store'));

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors([
                'operation',
                'type',
                'title',
                'description',
                'price',
                'currency',
                'city',
                'zone',
                'bedrooms',
                'bathrooms',
                'area',
                'photos',
            ])
            ->assertSessionHasErrors([
                'title' => 'El campo título es obligatorio.',
                'photos' => 'El campo fotos es obligatorio.',
            ]);

        $this->assertDatabaseCount('properties', 0);
        $this->assertDatabaseCount('property_photos', 0);
        Storage::disk('public')->assertDirectoryEmpty('properties');
    }

    public static function invalidEnumValues(): array
    {
        return [
            'operation' => ['operation', 'venta', 'El valor seleccionado para operación no es válido.'],
            'type' => ['type', 'terreno', 'El valor seleccionado para tipo no es válido.'],
            'currency' => ['currency', 'EUR', 'El valor seleccionado para moneda no es válido.'],
        ];
    }

    #[DataProvider('invalidEnumValues')]
    public function test_rejects_invalid_operation_type_and_currency_values(
        string $field,
        string $value,
        string $message,
    ): void {
        Storage::fake('public');

        $user = User::factory()->create();

        $payload = $this->validPayload();
        $payload[$field] = $value;

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->post(route('properties.store'), $payload);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('properties', 0);
        $this->assertDatabaseCount('property_photos', 0);
        Storage::disk('public')->assertDirectoryEmpty('properties');
    }

    public static function invalidScalarValues(): array
    {
        return [
            'price is not a number' => ['price', 'mucho', 'El campo precio debe ser un número.'],
            'price is negative' => ['price', -1, 'El campo precio debe ser mayor que cero.'],
            'area is negative' => ['area', -1, 'El campo área debe ser mayor que cero.'],
            'bedrooms is not an integer' => ['bedrooms', 'tres', 'El campo dormitorios debe ser un número entero.'],
            'title is too long' => ['title', str_repeat('a', 256), 'El campo título no debe superar 255 caracteres.'],
        ];
    }

    #[DataProvider('invalidScalarValues')]
    public function test_rejects_invalid_numeric_and_text_values(
        string $field,
        mixed $value,
        string $message,
    ): void {
        Storage::fake('public');

        $user = User::factory()->create();

        $payload = $this->validPayload();
        $payload[$field] = $value;

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->post(route('properties.store'), $payload);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('properties', 0);
        $this->assertDatabaseCount('property_photos', 0);
        Storage::disk('public')->assertDirectoryEmpty('properties');
    }

    public static function invalidPhotoCounts(): array
    {
        return [
            'no photos' => [0, 'El campo fotos es obligatorio.'],
            'more than eight photos' => [9, 'Puedes adjuntar como máximo 8 fotos.'],
        ];
    }

    #[DataProvider('invalidPhotoCounts')]
    public function test_requires_between_one_and_eight_photos(int $photoCount, string $message): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $payload = $this->validPayload();
        $payload['photos'] = array_map(
            fn (int $index): UploadedFile => UploadedFile::fake()->image("photo-{$index}.jpg"),
            $photoCount > 0 ? range(1, $photoCount) : [],
        );

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->post(route('properties.store'), $payload);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors(['photos' => $message]);

        $this->assertDatabaseCount('properties', 0);
        $this->assertDatabaseCount('property_photos', 0);
        Storage::disk('public')->assertDirectoryEmpty('properties');
    }

    public function test_rejects_a_photo_larger_than_five_megabytes(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $payload = $this->validPayload();
        $payload['photos'] = [
            UploadedFile::fake()->image('large.jpg')->size(5121),
        ];

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->post(route('properties.store'), $payload);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors(['photos.0' => 'Cada foto debe pesar como máximo 5 MB.']);

        $this->assertDatabaseCount('properties', 0);
        $this->assertDatabaseCount('property_photos', 0);
        Storage::disk('public')->assertDirectoryEmpty('properties');
    }

    public function test_rejects_a_photo_with_an_unsupported_format(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $payload = $this->validPayload();
        $payload['photos'] = [
            UploadedFile::fake()->image('photo.gif'),
        ];

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->post(route('properties.store'), $payload);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors(['photos.0' => 'Cada foto debe ser JPG, PNG o WebP.']);

        $this->assertDatabaseCount('properties', 0);
        $this->assertDatabaseCount('property_photos', 0);
        Storage::disk('public')->assertDirectoryEmpty('properties');
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa frente al parque',
            'description' => 'Casa de tres pisos con jardín y terraza.',
            'price' => 250000,
            'currency' => 'PEN',
            'city' => 'Lima',
            'zone' => 'Miraflores',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area' => 120.5,
            'photos' => [
                UploadedFile::fake()->image('front.jpg'),
            ],
        ];
    }
}
