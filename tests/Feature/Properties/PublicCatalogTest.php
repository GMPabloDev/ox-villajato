<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_published_properties_in_the_catalog_with_their_card_data(): void
    {
        $property = Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa frente al parque',
            'price' => 250000,
            'currency' => 'PEN',
            'district_code' => '150122',
        ]);

        $property->photos()->createMany([
            ['path' => 'properties/cover.jpg', 'position' => 1],
            ['path' => 'properties/second.jpg', 'position' => 2],
        ]);

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $property->id)
                ->where('properties.0.title', 'Casa frente al parque')
                ->where('properties.0.operation_label', 'Venta')
                ->where('properties.0.type_label', 'Casa')
                ->where('properties.0.price_formatted', 'S/ 250,000')
                ->where('properties.0.district_code', '150122')
                ->where('properties.0.department', 'Lima')
                ->where('properties.0.province', 'Lima')
                ->where('properties.0.district', 'Miraflores')
                ->where('properties.0.cover_photo_url', Storage::disk('public')->url('properties/cover.jpg'))
                ->where('properties.0.url', route('properties.show', $property))
                ->where('filters.operation', null)
                ->where('filters.type', null)
                ->where('filters.department_code', null)
                ->where('filters.province_code', null)
                ->where('filters.district_code', null)
                ->where('appliedLocation', [
                    'department' => null,
                    'province' => null,
                    'district' => null,
                ])
            );
    }

    public function test_catalog_returns_an_empty_list_when_there_are_no_properties(): void
    {
        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 0)
                ->has('locations.departments', 0)
                ->has('locations.provinces', 0)
                ->has('locations.districts', 0)
            );
    }

    public function test_catalog_filters_by_operation(): void
    {
        $sale = Property::factory()->create([
            'operation' => 'sale',
            'title' => 'Casa en venta',
        ]);
        Property::factory()->create([
            'operation' => 'rent',
            'title' => 'Casa en alquiler',
        ]);

        $response = $this->get(route('home', ['operation' => 'sale']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $sale->id)
                ->where('properties.0.title', 'Casa en venta')
            );
    }

    public function test_catalog_filters_by_type(): void
    {
        $house = Property::factory()->create([
            'type' => 'house',
            'title' => 'Casa con jardín',
        ]);
        Property::factory()->create([
            'type' => 'apartment',
            'title' => 'Departamento con balcón',
        ]);

        $response = $this->get(route('home', ['type' => 'house']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $house->id)
                ->where('properties.0.title', 'Casa con jardín')
            );
    }

    public function test_catalog_filters_by_a_district_link_and_resolves_its_province_and_department(): void
    {
        $miraflores = Property::factory()->create([
            'title' => 'Casa en Miraflores',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'title' => 'Casa en Barranco',
            'district_code' => '150104',
        ]);

        $response = $this->get(route('home', ['district_code' => '150122']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $miraflores->id)
                ->where('properties.0.title', 'Casa en Miraflores')
                ->where('filters.department_code', '15')
                ->where('filters.province_code', '1501')
                ->where('filters.district_code', '150122')
                ->where('appliedLocation', [
                    'department' => 'Lima',
                    'province' => 'Lima',
                    'district' => 'Miraflores',
                ])
            );
    }

    public function test_catalog_filters_by_department(): void
    {
        $miraflores = Property::factory()->create([
            'title' => 'Casa en Miraflores',
            'district_code' => '150122',
        ]);
        $asia = Property::factory()->create([
            'title' => 'Casa en Asia',
            'district_code' => '150502',
        ]);
        Property::factory()->create([
            'title' => 'Casa en Yanahuara',
            'district_code' => '040126',
        ]);

        $response = $this->get(route('home', ['department_code' => '15']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 2)
                ->where('properties.0.id', $asia->id)
                ->where('properties.1.id', $miraflores->id)
                ->where('filters.department_code', '15')
                ->where('filters.province_code', null)
                ->where('filters.district_code', null)
                ->where('appliedLocation', [
                    'department' => 'Lima',
                    'province' => null,
                    'district' => null,
                ])
            );
    }

    public function test_catalog_filters_by_province(): void
    {
        $miraflores = Property::factory()->create([
            'title' => 'Casa en Miraflores',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'title' => 'Casa en Asia',
            'district_code' => '150502',
        ]);
        Property::factory()->create([
            'title' => 'Casa en Yanahuara',
            'district_code' => '040126',
        ]);

        $response = $this->get(route('home', ['province_code' => '1501']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $miraflores->id)
                ->where('filters.department_code', '15')
                ->where('filters.province_code', '1501')
                ->where('filters.district_code', null)
                ->where('appliedLocation', [
                    'department' => 'Lima',
                    'province' => 'Lima',
                    'district' => null,
                ])
            );
    }

    public function test_catalog_combines_the_location_filters_with_operation_and_type(): void
    {
        $matching = Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa en venta en Miraflores',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'operation' => 'rent',
            'type' => 'house',
            'title' => 'Casa en alquiler en Miraflores',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'apartment',
            'title' => 'Departamento en venta en Miraflores',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa en venta en Asia',
            'district_code' => '150502',
        ]);

        $response = $this->get(route('home', [
            'operation' => 'sale',
            'type' => 'house',
            'department_code' => '15',
            'province_code' => '1501',
        ]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $matching->id)
                ->where('filters.operation', 'sale')
                ->where('filters.type', 'house')
                ->where('filters.department_code', '15')
                ->where('filters.province_code', '1501')
                ->where('filters.district_code', null)
                ->where('appliedLocation', [
                    'department' => 'Lima',
                    'province' => 'Lima',
                    'district' => null,
                ])
            );
    }

    public function test_catalog_district_filter_distinguishes_districts_with_the_same_name(): void
    {
        Property::factory()->create([
            'title' => 'Casa en Santiago de Cusco',
            'district_code' => '080106',
        ]);
        $ica = Property::factory()->create([
            'title' => 'Casa en Santiago de Ica',
            'district_code' => '110111',
        ]);

        $response = $this->get(route('home', ['district_code' => '110111']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $ica->id)
                ->where('locations.districts', [
                    [
                        'code' => '080106',
                        'name' => 'Santiago',
                        'province_code' => '0801',
                        'province' => 'Cusco',
                        'department' => 'Cusco',
                    ],
                    [
                        'code' => '110111',
                        'name' => 'Santiago',
                        'province_code' => '1101',
                        'province' => 'Ica',
                        'department' => 'Ica',
                    ],
                ])
            );
    }

    public function test_catalog_offers_one_option_per_available_property_location(): void
    {
        Property::factory()->create(['district_code' => '150122']);
        Property::factory()->create(['district_code' => '150122']);
        Property::factory()->create(['status' => 'sold', 'district_code' => '040126']);
        Property::factory()->create(['district_code' => '150104']);

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->where('locations.departments', [
                    ['code' => '15', 'name' => 'Lima'],
                ])
                ->where('locations.provinces', [
                    ['code' => '1501', 'name' => 'Lima', 'department_code' => '15'],
                ])
                ->where('locations.districts', [
                    [
                        'code' => '150104',
                        'name' => 'Barranco',
                        'province_code' => '1501',
                        'province' => 'Lima',
                        'department' => 'Lima',
                    ],
                    [
                        'code' => '150122',
                        'name' => 'Miraflores',
                        'province_code' => '1501',
                        'province' => 'Lima',
                        'department' => 'Lima',
                    ],
                ])
            );
    }

    public function test_catalog_location_options_do_not_depend_on_the_operation_or_the_type(): void
    {
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'operation' => 'rent',
            'type' => 'apartment',
            'district_code' => '040126',
        ]);

        $response = $this->get(route('home', ['operation' => 'sale', 'type' => 'house']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('locations.departments', [
                    ['code' => '04', 'name' => 'Arequipa'],
                    ['code' => '15', 'name' => 'Lima'],
                ])
                ->where('locations.provinces', [
                    ['code' => '0401', 'name' => 'Arequipa', 'department_code' => '04'],
                    ['code' => '1501', 'name' => 'Lima', 'department_code' => '15'],
                ])
                ->where('locations.districts', [
                    [
                        'code' => '150122',
                        'name' => 'Miraflores',
                        'province_code' => '1501',
                        'province' => 'Lima',
                        'department' => 'Lima',
                    ],
                    [
                        'code' => '040126',
                        'name' => 'Yanahuara',
                        'province_code' => '0401',
                        'province' => 'Arequipa',
                        'department' => 'Arequipa',
                    ],
                ])
            );
    }

    public function test_catalog_combines_the_district_filter_with_operation_and_type(): void
    {
        $matching = Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa en venta en Miraflores',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'operation' => 'rent',
            'type' => 'house',
            'title' => 'Casa en alquiler en Miraflores',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'apartment',
            'title' => 'Departamento en venta en Miraflores',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa en venta en Barranco',
            'district_code' => '150104',
        ]);

        $query = ['operation' => 'sale', 'type' => 'house', 'district_code' => '150122'];
        $response = $this->get(route('home', $query));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $matching->id)
                ->where('filters.operation', 'sale')
                ->where('filters.type', 'house')
                ->where('filters.department_code', '15')
                ->where('filters.province_code', '1501')
                ->where('filters.district_code', '150122')
            );

        $this->get(route('home', ['operation' => 'rent', 'type' => 'apartment']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('properties', 0));
    }

    public function test_catalog_keeps_a_selected_district_visible_when_it_has_no_available_properties(): void
    {
        Property::factory()->create(['status' => 'sold', 'district_code' => '150122']);

        $response = $this->get(route('home', ['district_code' => '150122']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 0)
                ->has('locations.departments', 0)
                ->has('locations.provinces', 0)
                ->has('locations.districts', 0)
                ->where('appliedLocation', [
                    'department' => 'Lima',
                    'province' => 'Lima',
                    'district' => 'Miraflores',
                ])
                ->where('filters.department_code', '15')
                ->where('filters.province_code', '1501')
                ->where('filters.district_code', '150122')
            );
    }

    #[DataProvider('selectedLocationsWithoutAvailableProperties')]
    public function test_a_valid_selected_location_without_available_properties_stays_visible(
        array $query,
        array $expectedFilters,
        array $expectedAppliedLocation,
    ): void {
        Property::factory()->create(['status' => 'sold', 'district_code' => '040126']);
        Property::factory()->create(['district_code' => '150122']);

        $response = $this->get(route('home', $query));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 0)
                ->where('filters.department_code', $expectedFilters['department_code'])
                ->where('filters.province_code', $expectedFilters['province_code'])
                ->where('filters.district_code', $expectedFilters['district_code'])
                ->where('appliedLocation', $expectedAppliedLocation)
                ->where('locations.departments', [
                    ['code' => '15', 'name' => 'Lima'],
                ])
                ->where('locations.districts', [
                    [
                        'code' => '150122',
                        'name' => 'Miraflores',
                        'province_code' => '1501',
                        'province' => 'Lima',
                        'department' => 'Lima',
                    ],
                ])
            );
    }

    /**
     * @return array<string, array{
     *     array<string, string>,
     *     array{department_code: string, province_code: string|null, district_code: string|null},
     *     array{department: string, province: string|null, district: string|null}
     * }>
     */
    public static function selectedLocationsWithoutAvailableProperties(): array
    {
        return [
            'department' => [
                ['department_code' => '04'],
                ['department_code' => '04', 'province_code' => null, 'district_code' => null],
                ['department' => 'Arequipa', 'province' => null, 'district' => null],
            ],
            'province' => [
                ['province_code' => '0401'],
                ['department_code' => '04', 'province_code' => '0401', 'district_code' => null],
                ['department' => 'Arequipa', 'province' => 'Arequipa', 'district' => null],
            ],
            'district' => [
                ['district_code' => '040126'],
                ['department_code' => '04', 'province_code' => '0401', 'district_code' => '040126'],
                ['department' => 'Arequipa', 'province' => 'Arequipa', 'district' => 'Yanahuara'],
            ],
        ];
    }

    #[DataProvider('incompatibleLocationCases')]
    public function test_incompatible_location_levels_are_dropped_keeping_the_highest_valid_level(
        array $query,
        array $expectedFilters,
        array $expectedAppliedLocation,
        string $expectedTitle,
    ): void {
        Property::factory()->create([
            'title' => 'Casa en Lima',
            'district_code' => '150122',
        ]);
        Property::factory()->create([
            'title' => 'Casa en Arequipa',
            'district_code' => '040126',
        ]);

        $response = $this->get(route('home', $query));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.title', $expectedTitle)
                ->where('filters.department_code', $expectedFilters['department_code'])
                ->where('filters.province_code', $expectedFilters['province_code'])
                ->where('filters.district_code', $expectedFilters['district_code'])
                ->where('appliedLocation', $expectedAppliedLocation)
            );
    }

    /**
     * @return array<string, array{
     *     array<string, string>,
     *     array{department_code: string, province_code: string|null, district_code: string|null},
     *     array{department: string, province: string|null, district: string|null},
     *     string
     * }>
     */
    public static function incompatibleLocationCases(): array
    {
        return [
            'province from another department' => [
                ['department_code' => '15', 'province_code' => '0401'],
                ['department_code' => '15', 'province_code' => null, 'district_code' => null],
                ['department' => 'Lima', 'province' => null, 'district' => null],
                'Casa en Lima',
            ],
            'district from another province' => [
                ['province_code' => '0401', 'district_code' => '150122'],
                ['department_code' => '04', 'province_code' => '0401', 'district_code' => null],
                ['department' => 'Arequipa', 'province' => 'Arequipa', 'district' => null],
                'Casa en Arequipa',
            ],
            'district from another department' => [
                ['department_code' => '04', 'district_code' => '150122'],
                ['department_code' => '04', 'province_code' => null, 'district_code' => null],
                ['department' => 'Arequipa', 'province' => null, 'district' => null],
                'Casa en Arequipa',
            ],
            'district from another department under a valid province' => [
                ['department_code' => '15', 'province_code' => '1501', 'district_code' => '040126'],
                ['department_code' => '15', 'province_code' => '1501', 'district_code' => null],
                ['department' => 'Lima', 'province' => 'Lima', 'district' => null],
                'Casa en Lima',
            ],
            'province and district from another department' => [
                ['department_code' => '15', 'province_code' => '0401', 'district_code' => '150122'],
                ['department_code' => '15', 'province_code' => null, 'district_code' => null],
                ['department' => 'Lima', 'province' => null, 'district' => null],
                'Casa en Lima',
            ],
        ];
    }

    public function test_catalog_rejects_an_unknown_district(): void
    {
        $response = $this
            ->from(route('home', ['type' => 'house']))
            ->get(route('home', ['district_code' => '999999']));

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors([
                'district_code' => 'El valor seleccionado para distrito no es válido.',
            ]);
    }

    public function test_catalog_combines_filters_and_returns_the_same_card_data_from_the_url(): void
    {
        $property = Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa frente al parque',
            'price' => 250000,
            'currency' => 'PEN',
            'district_code' => '150122',
        ]);

        $property->photos()->create([
            'path' => 'properties/cover.jpg',
            'position' => 1,
        ]);

        Property::factory()->create([
            'operation' => 'rent',
            'type' => 'house',
            'title' => 'Casa en alquiler',
        ]);
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'apartment',
            'title' => 'Departamento en venta',
        ]);
        Property::factory()->create([
            'operation' => 'rent',
            'type' => 'house',
            'title' => 'Casa en alquiler en Barranco',
        ]);

        $query = [
            'operation' => 'sale',
            'type' => 'house',
            'department_code' => '15',
            'province_code' => '1501',
            'district_code' => '150122',
        ];

        $response = $this->get(route('home', $query));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 1)
                ->where('properties.0.id', $property->id)
                ->where('properties.0.title', 'Casa frente al parque')
                ->where('properties.0.operation_label', 'Venta')
                ->where('properties.0.type_label', 'Casa')
                ->where('properties.0.price_formatted', 'S/ 250,000')
                ->where('properties.0.district_code', '150122')
                ->where('properties.0.department', 'Lima')
                ->where('properties.0.province', 'Lima')
                ->where('properties.0.district', 'Miraflores')
                ->where('properties.0.cover_photo_url', Storage::disk('public')->url('properties/cover.jpg'))
                ->where('properties.0.url', route('properties.show', $property))
                ->where('filters.operation', 'sale')
                ->where('filters.type', 'house')
                ->where('filters.department_code', '15')
                ->where('filters.province_code', '1501')
                ->where('filters.district_code', '150122')
            );

        $reloaded = $this->get(route('home', $query));

        $this->assertSame(
            collect($response->inertiaProps('properties'))->pluck('id')->all(),
            collect($reloaded->inertiaProps('properties'))->pluck('id')->all(),
        );
    }

    public function test_catalog_keeps_filtered_properties_in_latest_id_order(): void
    {
        $older = Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa antigua',
        ]);
        $newer = Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa reciente',
        ]);
        Property::factory()->create([
            'operation' => 'rent',
            'type' => 'house',
            'title' => 'Casa en alquiler',
        ]);

        $response = $this->get(route('home', ['operation' => 'sale']));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 2)
                ->where('properties.0.id', $newer->id)
                ->where('properties.1.id', $older->id)
            );
    }

    public function test_catalog_returns_no_properties_when_filters_match_nothing(): void
    {
        Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
        ]);

        $response = $this->get(route('home', [
            'operation' => 'rent',
            'type' => 'apartment',
        ]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->has('properties', 0)
                ->where('filters.operation', 'rent')
                ->where('filters.type', 'apartment')
            );
    }

    public function test_catalog_rejects_invalid_operation_and_type_filters(): void
    {
        $response = $this
            ->from(route('home', ['type' => 'house']))
            ->get(route('home', [
                'operation' => 'venta',
                'type' => 'casa',
            ]));

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['operation', 'type']);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function invalidCatalogFilters(): array
    {
        return [
            'operation' => ['operation', 'venta', 'El valor seleccionado para operación no es válido.'],
            'type' => ['type', 'casa', 'El valor seleccionado para tipo no es válido.'],
            'department' => ['department_code', '99', 'El valor seleccionado para departamento no es válido.'],
            'province' => ['province_code', '9999', 'El valor seleccionado para provincia no es válido.'],
            'district' => ['district_code', '999999', 'El valor seleccionado para distrito no es válido.'],
        ];
    }

    #[DataProvider('invalidCatalogFilters')]
    public function test_catalog_rejects_invalid_filter_values(string $field, mixed $value, string $message): void
    {
        $response = $this
            ->from(route('home'))
            ->followingRedirects()
            ->get(route('home', [$field => $value]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('properties/index')
                ->where("errors.{$field}", $message)
            );
    }
}
