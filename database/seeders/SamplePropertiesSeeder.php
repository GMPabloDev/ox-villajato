<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Enums\PropertyOperation;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SamplePropertiesSeeder extends Seeder
{
    use WithoutModelEvents;

    public const PHOTO_DIRECTORY = 'properties/sample-seed';

    public const PHOTO_PATH_PREFIX = self::PHOTO_DIRECTORY.'/';

    private const IMAGE_FILES = [
        'casa.jpeg',
        'casa2.jpeg',
        'casa3.jpeg',
        'casa4.jpeg',
        'casa5.jpeg',
        'casa6.jpeg',
        'casa7.jpeg',
        'casa8.jpeg',
    ];

    /**
     * @var array<string, array{
     *     attributes: array{
     *         operation: string,
     *         status: string,
     *         type: string,
     *         title: string,
     *         description: string,
     *         price: int,
     *         currency: string,
     *         district_code: string,
     *         bedrooms: int,
     *         bathrooms: int,
     *         area: float
     *     },
     *     photos: array{string, string, string}
     * }>
     */
    private const PROPERTIES = [
        'miraflores-casa-jardin' => [
            'attributes' => [
                'operation' => PropertyOperation::Sale->value,
                'status' => PropertyStatus::Available->value,
                'type' => PropertyType::House->value,
                'title' => 'Casa familiar con jardín en Miraflores',
                'description' => 'Casa amplia con jardín, sala iluminada y espacios cómodos para toda la familia.',
                'price' => 780000,
                'currency' => Currency::Pen->value,
                'district_code' => '150122',
                'bedrooms' => 4,
                'bathrooms' => 3,
                'area' => 210.0,
            ],
            'photos' => ['casa.jpeg', 'casa2.jpeg', 'casa3.jpeg'],
        ],
        'san-isidro-departamento-balcon' => [
            'attributes' => [
                'operation' => PropertyOperation::Sale->value,
                'status' => PropertyStatus::Available->value,
                'type' => PropertyType::Apartment->value,
                'title' => 'Departamento con balcón en San Isidro',
                'description' => 'Departamento moderno con balcón, buena iluminación y distribución funcional.',
                'price' => 185000,
                'currency' => Currency::Usd->value,
                'district_code' => '150131',
                'bedrooms' => 3,
                'bathrooms' => 2,
                'area' => 128.0,
            ],
            'photos' => ['casa2.jpeg', 'casa3.jpeg', 'casa4.jpeg'],
        ],
        'asia-casa-de-playa' => [
            'attributes' => [
                'operation' => PropertyOperation::Sale->value,
                'status' => PropertyStatus::Available->value,
                'type' => PropertyType::House->value,
                'title' => 'Casa de playa en Asia',
                'description' => 'Casa de descanso con terraza y ambientes abiertos, a pocos minutos de la playa.',
                'price' => 245000,
                'currency' => Currency::Usd->value,
                'district_code' => '150502',
                'bedrooms' => 4,
                'bathrooms' => 3,
                'area' => 185.0,
            ],
            'photos' => ['casa3.jpeg', 'casa4.jpeg', 'casa5.jpeg'],
        ],
        'barranco-departamento-alquiler' => [
            'attributes' => [
                'operation' => PropertyOperation::Rent->value,
                'status' => PropertyStatus::Available->value,
                'type' => PropertyType::Apartment->value,
                'title' => 'Departamento luminoso en Barranco',
                'description' => 'Departamento acogedor con ambientes bien distribuidos en una zona residencial.',
                'price' => 2800,
                'currency' => Currency::Pen->value,
                'district_code' => '150104',
                'bedrooms' => 2,
                'bathrooms' => 2,
                'area' => 92.0,
            ],
            'photos' => ['casa4.jpeg', 'casa5.jpeg', 'casa6.jpeg'],
        ],
        'cayma-casa-terraza' => [
            'attributes' => [
                'operation' => PropertyOperation::Sale->value,
                'status' => PropertyStatus::Available->value,
                'type' => PropertyType::House->value,
                'title' => 'Casa con terraza en Cayma',
                'description' => 'Casa de varios ambientes con terraza y espacios pensados para compartir.',
                'price' => 690000,
                'currency' => Currency::Pen->value,
                'district_code' => '040103',
                'bedrooms' => 3,
                'bathrooms' => 3,
                'area' => 176.0,
            ],
            'photos' => ['casa5.jpeg', 'casa7.jpeg', 'casa8.jpeg'],
        ],
        'yanahuara-departamento-amoblado' => [
            'attributes' => [
                'operation' => PropertyOperation::Rent->value,
                'status' => PropertyStatus::Available->value,
                'type' => PropertyType::Apartment->value,
                'title' => 'Departamento amoblado en Yanahuara',
                'description' => 'Departamento amoblado con espacios cómodos y acceso cercano a comercios y servicios.',
                'price' => 950,
                'currency' => Currency::Usd->value,
                'district_code' => '040126',
                'bedrooms' => 2,
                'bathrooms' => 2,
                'area' => 88.0,
            ],
            'photos' => ['casa6.jpeg', 'casa7.jpeg', 'casa.jpeg'],
        ],
        'trujillo-casa-moderna' => [
            'attributes' => [
                'operation' => PropertyOperation::Rent->value,
                'status' => PropertyStatus::Available->value,
                'type' => PropertyType::House->value,
                'title' => 'Casa moderna en Víctor Larco',
                'description' => 'Casa de diseño actual con ambientes amplios y buena entrada de luz natural.',
                'price' => 3200,
                'currency' => Currency::Pen->value,
                'district_code' => '130111',
                'bedrooms' => 3,
                'bathrooms' => 3,
                'area' => 165.0,
            ],
            'photos' => ['casa7.jpeg', 'casa8.jpeg', 'casa2.jpeg'],
        ],
    ];

    public function run(): void
    {
        $agents = User::query()
            ->where('role', UserRole::Agent->value)
            ->orderBy('id')
            ->get();

        if ($agents->isEmpty()) {
            throw new RuntimeException('SamplePropertiesSeeder necesita al menos un agente existente.');
        }

        $this->ensureSourceImagesExist();

        $this->call(UbigeoSeeder::class);

        $photosDisk = Storage::disk((string) config('properties.photos_disk'));
        $propertyIndex = 0;

        foreach (self::PROPERTIES as $propertyKey => $sampleProperty) {
            $agent = $agents[$propertyIndex % $agents->count()];
            $propertyPhotoPrefix = self::PHOTO_PATH_PREFIX.$propertyKey.'/';

            $property = Property::query()
                ->whereHas('photos', fn (Builder $query): Builder => $query
                    ->where('path', 'like', $propertyPhotoPrefix.'%'))
                ->first();

            if ($property === null) {
                $property = $agent->properties()->create($sampleProperty['attributes']);
            } else {
                $property->update([
                    ...$sampleProperty['attributes'],
                    'user_id' => $agent->id,
                ]);
            }

            foreach ($sampleProperty['photos'] as $photoIndex => $imageFile) {
                $sourcePath = database_path('seeders/assets/properties/'.$imageFile);
                $storedPath = $propertyPhotoPrefix.$imageFile;

                if (! $photosDisk->put($storedPath, File::get($sourcePath))) {
                    throw new RuntimeException("No se pudo guardar la imagen de muestra [{$imageFile}].");
                }

                $property->photos()->updateOrCreate(
                    ['path' => $storedPath],
                    ['position' => $photoIndex + 1],
                );
            }

            $propertyIndex++;
        }
    }

    private function ensureSourceImagesExist(): void
    {
        $missingImages = array_values(array_filter(
            self::IMAGE_FILES,
            fn (string $imageFile): bool => ! File::isFile(
                database_path('seeders/assets/properties/'.$imageFile),
            ),
        ));

        if ($missingImages !== []) {
            throw new RuntimeException(
                'Faltan imágenes para el seed de propiedades: '.implode(', ', $missingImages),
            );
        }
    }
}
