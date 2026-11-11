<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PanelPropertyFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_options_exclude_other_agents_inventory_even_when_filtered_by_it(): void
    {
        $agent = User::factory()->agent()->create();
        $other = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['district_code' => '150122']);
        Property::factory()->for($other)->create(['district_code' => '040126']);

        $this->actingAs($agent)->get(route('panel.properties.index', [
            'department_code' => '04', 'province_code' => '0401', 'district_code' => '040126',
        ]))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('filters.department_code', '04')
            ->where('filters.province_code', '0401')
            ->where('filters.district_code', '040126')
            ->where('appliedLocation.department', 'Arequipa')
            ->where('appliedLocation.province', 'Arequipa')
            ->where('appliedLocation.district', 'Yanahuara')
            ->has('properties.data', 0)
            ->has('locations.departments', 1)
            ->where('locations.departments.0.code', '15')
            ->has('locations.provinces', 1)
            ->where('locations.provinces.0.code', '1501')
            ->has('locations.districts', 1)
            ->where('locations.districts.0.code', '150122'));
    }

    public function test_long_search_preserves_other_filters(): void
    {
        $agent = User::factory()->agent()->create();
        $search = str_repeat('a', 256);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', [
            'search' => $search, 'operation' => 'rent',
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', $search)
            ->where('filters.operation', 'rent')
            ->has('properties.data', 0));
    }

    public function test_title_order_folds_accents_consistently_with_search(): void
    {
        $agent = User::factory()->agent()->create();
        foreach (['Zorro casa', 'Àtico luminoso', 'Casa azul'] as $title) {
            Property::factory()->for($agent)->create(['title' => $title]);
        }

        $response = $this->actingAs($agent)->get(route('panel.properties.index', ['sort' => 'title']));
        $response->assertOk();
        $this->assertSame(['Àtico luminoso', 'Casa azul', 'Zorro casa'],
            collect($response->inertiaProps('properties.data'))->pluck('title')->all());
    }

    public function test_search_matches_the_title_ignoring_case_and_accents(): void
    {
        $agent = User::factory()->agent()->create();

        $jesusMaria = Property::factory()->for($agent)->create([
            'title' => 'Casa en Jesús María',
            'description' => 'Frente al parque.',
        ]);
        $barranco = Property::factory()->for($agent)->create([
            'title' => 'Departamento en Barranco',
            'description' => 'Cerca de la iglesia Jesús María.',
        ]);
        $wanchaq = Property::factory()->for($agent)->create([
            'title' => 'Local céntrico',
            'description' => 'A una cuadra de la plaza.',
        ]);

        $cases = [
            'jesus maria' => [$jesusMaria->id],
            'CASA' => [$jesusMaria->id],
            'barranco' => [$barranco->id],
            'JESÚS' => [$jesusMaria->id],
            'CENTRICO' => [$wanchaq->id],
            'plaza' => [],
        ];

        foreach ($cases as $term => $expectedIds) {
            $response = $this->actingAs($agent)->get(route('panel.properties.index', [
                'search' => $term,
            ]));

            $response->assertOk();

            $this->assertSame(
                $expectedIds,
                collect($response->inertiaProps('properties.data'))->pluck('id')->all(),
                "La búsqueda «{$term}» devolvió propiedades inesperadas.",
            );
        }

        $this->actingAs($agent)
            ->get(route('panel.properties.index', ['search' => '%']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('properties.data', 0));
    }

    public function test_search_matches_accents_outside_the_original_spanish_character_set(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Àtico luminoso en Évry y München',
        ]);

        foreach (['atico', 'EVRY', 'munchen'] as $term) {
            $response = $this->actingAs($agent)->get(route('panel.properties.index', ['search' => $term]));

            $response->assertOk();
            $this->assertSame([$property->id], collect($response->inertiaProps('properties.data'))->pluck('id')->all());
        }
    }

    public function test_search_matches_department_province_and_district_names_ignoring_case(): void
    {
        $agent = User::factory()->agent()->create();

        $miraflores = Property::factory()->for($agent)->create([
            'title' => 'Piso frente al parque',
            'district_code' => '150122',
        ]);
        $asia = Property::factory()->for($agent)->create([
            'title' => 'Casa de playa',
            'district_code' => '150502',
        ]);
        $yanahuara = Property::factory()->for($agent)->create([
            'title' => 'Local comercial',
            'district_code' => '040126',
        ]);

        $cases = [
            'MIRAFLORES' => [$miraflores->id],
            // La propiedad de Asia solo comparte «Lima» con su departamento.
            'lima' => [$asia->id, $miraflores->id],
            'Arequipa' => [$yanahuara->id],
            'yanahuara' => [$yanahuara->id],
        ];

        foreach ($cases as $term => $expectedIds) {
            $response = $this->actingAs($agent)->get(route('panel.properties.index', ['search' => $term]));

            $response->assertOk();

            $this->assertSame(
                $expectedIds,
                collect($response->inertiaProps('properties.data'))->pluck('id')->all(),
                "La búsqueda «{$term}» no encontró la ubicación esperada.",
            );
        }
    }

    public function test_search_matches_province_names_without_matching_the_district(): void
    {
        $agent = User::factory()->agent()->create();
        $asia = Property::factory()->for($agent)->create([
            'title' => 'Casa de playa',
            'district_code' => '150502',
        ]);

        foreach (['Asia' => [$asia->id], 'cañete' => [$asia->id], 'CANETE' => [$asia->id]] as $term => $expectedIds) {
            $response = $this->actingAs($agent)->get(route('panel.properties.index', ['search' => $term]));

            $response->assertOk();
            $this->assertSame(
                $expectedIds,
                collect($response->inertiaProps('properties.data'))->pluck('id')->all(),
                "La búsqueda «{$term}» no encontró la ubicación esperada.",
            );
        }
    }

    /**
     * @param  array<string, string|int|float>  $query
     * @param  list<string>  $expectedTitles
     */
    #[DataProvider('panelFilterCases')]
    public function test_each_filter_limits_the_panel_results(array $query, array $expectedTitles): void
    {
        $agent = User::factory()->agent()->create();
        $this->createFilterFixture($agent);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', $query));

        $response->assertOk();

        $this->assertSame(
            $expectedTitles,
            collect($response->inertiaProps('properties.data'))->pluck('title')->all(),
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->where('properties.total', count($expectedTitles))
        );
    }

    /**
     * @return array<string, array{array<string, string|int|float>, list<string>}>
     */
    public static function panelFilterCases(): array
    {
        return [
            'status' => [
                ['status' => 'sold'],
                ['Casa vendida en Surco'],
            ],
            'operation' => [
                ['operation' => 'rent'],
                ['Departamento disponible en Barranco', 'Departamento en alquiler en Yanahuara'],
            ],
            'type' => [
                ['type' => 'apartment'],
                ['Departamento disponible en Barranco', 'Departamento en alquiler en Yanahuara'],
            ],
            'currency' => [
                ['currency' => 'USD'],
                ['Departamento disponible en Barranco', 'Departamento en alquiler en Yanahuara'],
            ],
            'minimum bedrooms' => [
                ['bedrooms' => 3],
                ['Casa vendida en Surco', 'Casa en venta en Miraflores'],
            ],
            'minimum price' => [
                ['currency' => 'PEN', 'min_price' => 300000],
                ['Casa vendida en Surco'],
            ],
            'maximum price' => [
                ['currency' => 'PEN', 'max_price' => 300000],
                ['Casa en venta en Miraflores'],
            ],
            'price range' => [
                ['currency' => 'PEN', 'min_price' => 200000, 'max_price' => 600000],
                ['Casa vendida en Surco', 'Casa en venta en Miraflores'],
            ],
            'search' => [
                ['search' => 'miraflores'],
                ['Casa en venta en Miraflores'],
            ],
            'department' => [
                ['department_code' => '15'],
                ['Departamento disponible en Barranco', 'Casa vendida en Surco', 'Casa en venta en Miraflores'],
            ],
            'province' => [
                ['province_code' => '1501'],
                ['Departamento disponible en Barranco', 'Casa vendida en Surco', 'Casa en venta en Miraflores'],
            ],
            'district' => [
                ['district_code' => '150104'],
                ['Departamento disponible en Barranco'],
            ],
            'department combined with another filter' => [
                ['department_code' => '15', 'operation' => 'rent'],
                ['Departamento disponible en Barranco'],
            ],
            'district combined with another filter' => [
                ['district_code' => '150122', 'type' => 'house'],
                ['Casa en venta en Miraflores'],
            ],
            'search combined with another filter' => [
                ['search' => 'barranco', 'type' => 'apartment'],
                ['Departamento disponible en Barranco'],
            ],
            'combined filters' => [
                [
                    'status' => 'available',
                    'operation' => 'sale',
                    'type' => 'house',
                    'currency' => 'PEN',
                    'min_price' => 200000,
                    'max_price' => 300000,
                    'bedrooms' => 2,
                    'department_code' => '15',
                    'province_code' => '1501',
                ],
                ['Casa en venta en Miraflores'],
            ],
        ];
    }

    public function test_location_options_only_include_the_properties_the_user_can_manage(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create(['district_code' => '150122']);
        Property::factory()->for($agent)->create(['district_code' => '150104']);
        Property::factory()->for($agent)->create(['district_code' => '150131', 'status' => 'archived']);
        Property::factory()->for($otherAgent)->create(['district_code' => '040126']);

        $response = $this->actingAs($agent)->get(route('panel.properties.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('locations.departments', [
                ['code' => '15', 'name' => 'Lima'],
            ])
            ->where('locations.provinces', [
                ['code' => '1501', 'name' => 'Lima', 'department_code' => '15'],
            ])
            ->where('locations.districts', [
                ['code' => '150104', 'name' => 'Barranco', 'province_code' => '1501', 'province' => 'Lima'],
                ['code' => '150122', 'name' => 'Miraflores', 'province_code' => '1501', 'province' => 'Lima'],
            ])
        );
    }

    public function test_admin_location_options_include_every_agents_locations(): void
    {
        $admin = User::factory()->admin()->create();
        $firstAgent = User::factory()->agent()->create();
        $secondAgent = User::factory()->agent()->create();

        Property::factory()->for($firstAgent)->create(['district_code' => '150122']);
        Property::factory()->for($secondAgent)->create(['district_code' => '040126']);

        $response = $this->actingAs($admin)->get(route('panel.properties.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('locations.departments', [
                ['code' => '04', 'name' => 'Arequipa'],
                ['code' => '15', 'name' => 'Lima'],
            ])
            ->where('locations.districts', [
                ['code' => '150122', 'name' => 'Miraflores', 'province_code' => '1501', 'province' => 'Lima'],
                ['code' => '040126', 'name' => 'Yanahuara', 'province_code' => '0401', 'province' => 'Arequipa'],
            ])
        );
    }

    public function test_an_agent_never_receives_another_agents_location_options(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();

        Property::factory()->for($otherAgent)->create(['district_code' => '040126']);

        $response = $this->actingAs($agent)->get(route('panel.properties.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('locations.departments', [])
            ->where('locations.provinces', [])
            ->where('locations.districts', [])
        );
    }

    public function test_a_selected_location_without_matching_properties_is_not_offered(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['district_code' => '150122']);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', [
            'department_code' => '04',
            'province_code' => '0401',
            'district_code' => '040126',
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 0)
            ->where('filters.department_code', '04')
            ->where('filters.province_code', '0401')
            ->where('filters.district_code', '040126')
            ->where('locations.departments', [
                ['code' => '15', 'name' => 'Lima'],
            ])
            ->where('locations.provinces', [
                ['code' => '1501', 'name' => 'Lima', 'department_code' => '15'],
            ])
            ->where('locations.districts', [
                ['code' => '150122', 'name' => 'Miraflores', 'province_code' => '1501', 'province' => 'Lima'],
            ])
        );
    }

    #[DataProvider('incompatibleLocationCases')]
    public function test_incompatible_location_levels_are_cleared_before_filtering(
        array $query,
        array $expectedFilters,
    ): void {
        $agent = User::factory()->agent()->create();

        $lima = Property::factory()->for($agent)->create([
            'title' => 'Casa en Lima',
            'district_code' => '150122',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en Arequipa',
            'district_code' => '040126',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', $query));

        $response->assertOk();

        $this->assertSame([$lima->id], collect($response->inertiaProps('properties.data'))->pluck('id')->all());

        $response->assertInertia(fn (Assert $page) => $page
            ->where('filters.department_code', $expectedFilters['department_code'])
            ->where('filters.province_code', $expectedFilters['province_code'])
            ->where('filters.district_code', $expectedFilters['district_code'])
        );
    }

    /**
     * @return array<string, array{array<string, string>, array{department_code: string|null, province_code: string|null, district_code: string|null}}>
     */
    public static function incompatibleLocationCases(): array
    {
        return [
            'province from another department' => [
                ['department_code' => '15', 'province_code' => '0401'],
                ['department_code' => '15', 'province_code' => null, 'district_code' => null],
            ],
            'district from another province' => [
                ['province_code' => '1501', 'district_code' => '040126'],
                ['department_code' => null, 'province_code' => '1501', 'district_code' => null],
            ],
            'district from another department without a province' => [
                ['department_code' => '15', 'district_code' => '040126'],
                ['department_code' => '15', 'province_code' => null, 'district_code' => null],
            ],
        ];
    }

    public function test_status_counts_apply_the_location_filters_across_every_page(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->count(16)->create([
            'status' => 'available',
            'district_code' => '150122',
        ]);
        Property::factory()->for($agent)->count(4)->create([
            'status' => 'sold',
            'district_code' => '150122',
        ]);
        Property::factory()->for($agent)->count(3)->create([
            'status' => 'available',
            'district_code' => '040126',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', [
            'department_code' => '15',
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 15)
            ->where('properties.total', 20)
            ->where('counts.all', 20)
            ->where('counts.available', 16)
            ->where('counts.sold', 4)
            ->where('counts.rented', 0)
        );
    }

    public function test_a_shared_panel_url_keeps_its_location_filters_and_results_when_reloaded(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->count(16)->create([
            'status' => 'available',
            'district_code' => '150122',
        ]);
        Property::factory()->for($agent)->create([
            'status' => 'available',
            'district_code' => '040126',
        ]);

        $query = ['department_code' => '15', 'district_code' => '150122', 'page' => 2];

        $first = $this->actingAs($agent)->get(route('panel.properties.index', $query));
        $second = $this->actingAs($agent)->get(route('panel.properties.index', $query));

        $first->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('filters.department_code', '15')
            ->where('filters.district_code', '150122')
            ->where('properties.current_page', 2)
            ->where('properties.total', 16)
            ->where('counts.available', 16)
        );

        $this->assertSame(
            collect($first->inertiaProps('properties.data'))->pluck('id')->all(),
            collect($second->inertiaProps('properties.data'))->pluck('id')->all(),
        );

        $firstPageLink = collect($first->inertiaProps('properties.links'))
            ->first(fn (array $link): bool => $link['label'] === '1');

        $this->assertIsArray($firstPageLink);
        $this->assertIsString($firstPageLink['url']);
        $this->assertStringContainsString('department_code=15', $firstPageLink['url']);
        $this->assertStringContainsString('district_code=150122', $firstPageLink['url']);
    }

    public function test_price_sort_uses_only_the_selected_currency_ascending(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa en soles cara',
            'price' => 300,
            'currency' => 'PEN',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en dólares',
            'price' => 100,
            'currency' => 'USD',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en soles barata',
            'price' => 100,
            'currency' => 'PEN',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', [
            'currency' => 'PEN',
            'sort' => 'price-asc',
        ]));

        $response->assertOk();

        $this->assertSame(
            ['Casa en soles barata', 'Casa en soles cara'],
            collect($response->inertiaProps('properties.data'))->pluck('title')->all(),
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->where('properties.total', 2)
            ->where('filters.sort', 'price-asc')
        );
    }

    public function test_price_ranges_and_price_sort_without_a_currency_are_ignored(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa en soles barata',
            'price' => 100,
            'currency' => 'PEN',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en soles cara',
            'price' => 300,
            'currency' => 'PEN',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en dólares',
            'price' => 1000,
            'currency' => 'USD',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', [
            'sort' => 'price-asc',
            'min_price' => 500,
            'max_price' => 5000,
        ]));

        $response->assertOk();

        $this->assertSame(
            ['Casa en dólares', 'Casa en soles cara', 'Casa en soles barata'],
            collect($response->inertiaProps('properties.data'))->pluck('title')->all(),
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->where('properties.total', 3)
            ->where('filters.sort', 'recent')
            ->where('filters.min_price', null)
            ->where('filters.max_price', null)
        );
    }

    public function test_an_invalid_price_range_is_reported_and_not_applied(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa en soles barata',
            'price' => 1000,
            'currency' => 'PEN',
        ]);
        Property::factory()->for($agent)->create([
            'title' => 'Casa en soles cara',
            'price' => 5000,
            'currency' => 'PEN',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', [
            'currency' => 'PEN',
            'min_price' => 6000,
            'max_price' => 5000,
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 2)
            ->where('filterErrors.price', 'El precio mínimo no puede ser mayor que el precio máximo.')
            ->where('filters.min_price', null)
            ->where('filters.max_price', null)
            ->where('filters.currency', 'PEN')
        );
    }

    public function test_title_sort_ignores_case_and_accents(): void
    {
        $agent = User::factory()->agent()->create();

        foreach (['ático en Barranco', 'Casa en Miraflores', 'departamento en Surco', 'Ámbar en Lima'] as $title) {
            Property::factory()->for($agent)->create(['title' => $title]);
        }

        $response = $this->actingAs($agent)->get(route('panel.properties.index', [
            'sort' => 'title',
        ]));

        $response->assertOk();

        $this->assertSame(
            ['Ámbar en Lima', 'ático en Barranco', 'Casa en Miraflores', 'departamento en Surco'],
            collect($response->inertiaProps('properties.data'))->pluck('title')->all(),
        );
    }

    public function test_the_panel_reports_the_normalized_filters(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create([
            'title' => 'Casa en venta en Miraflores',
            'status' => 'available',
            'operation' => 'sale',
            'type' => 'house',
            'currency' => 'PEN',
            'price' => 250000,
            'bedrooms' => 3,
            'district_code' => '150122',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', [
            'search' => '  Casa  ',
            'status' => 'available',
            'operation' => 'sale',
            'type' => 'house',
            'department_code' => '15',
            'province_code' => '1501',
            'district_code' => '150122',
            'currency' => 'PEN',
            'min_price' => 200000,
            'max_price' => 300000,
            'bedrooms' => 2,
            'sort' => 'title',
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 1)
            ->where('filters.search', 'Casa')
            ->where('filters.status', 'available')
            ->where('filters.operation', 'sale')
            ->where('filters.type', 'house')
            ->where('filters.department_code', '15')
            ->where('filters.province_code', '1501')
            ->where('filters.district_code', '150122')
            ->where('filters.currency', 'PEN')
            ->where('filters.min_price', '200000')
            ->where('filters.max_price', '300000')
            ->where('filters.bedrooms', 2)
            ->where('filters.sort', 'title')
        );
    }

    public function test_out_of_range_page_redirects_to_last_page_preserving_filters(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['operation' => 'rent']);
        $response = $this->actingAs($agent)->get(route('panel.properties.index', ['operation' => 'rent', 'page' => 2]));
        $response->assertRedirect(route('panel.properties.index', ['operation' => 'rent', 'page' => 1]));
        $this->followingRedirects()->actingAs($agent)->get(route('panel.properties.index', ['operation' => 'rent', 'page' => 2]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('properties.data', 1)->where('filters.operation', 'rent'));
    }

    public function test_all_and_any_control_values_mean_no_filter(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->count(2)->create(['status' => 'available']);
        Property::factory()->for($agent)->create(['status' => 'sold']);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', [
            'status' => 'all',
            'operation' => 'all',
            'type' => 'all',
            'currency' => 'all',
            'bedrooms' => 'any',
            'department_code' => 'all',
            'province_code' => 'all',
            'district_code' => 'all',
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 3)
            ->where('filters.status', null)
            ->where('filters.operation', null)
            ->where('filters.type', null)
            ->where('filters.department_code', null)
            ->where('filters.province_code', null)
            ->where('filters.district_code', null)
            ->where('filters.currency', null)
            ->where('filters.bedrooms', null)
            ->where('filters.sort', 'recent')
        );
    }

    /**
     * @param  array<string, string|int>  $query
     */
    #[DataProvider('invalidFilterCases')]
    public function test_invalid_filter_values_return_to_the_clean_panel(
        array $query,
        string $errorKey,
        string $message,
    ): void {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create();

        $response = $this->actingAs($agent)->get(route('panel.properties.index', $query));

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors([$errorKey => $message]);
    }

    /**
     * @return array<string, array{array<string, string|int>, string, string}>
     */
    public static function invalidFilterCases(): array
    {
        return [
            'operation' => [
                ['operation' => 'trade'],
                'operation',
                'El valor seleccionado para operación no es válido.',
            ],
            'type' => [
                ['type' => 'land'],
                'type',
                'El valor seleccionado para tipo no es válido.',
            ],
            'status' => [
                ['status' => 'archived'],
                'status',
                'El valor seleccionado para estado no es válido.',
            ],
            'currency' => [
                ['currency' => 'EUR'],
                'currency',
                'El valor seleccionado para moneda no es válido.',
            ],
            'sort' => [
                ['sort' => 'price'],
                'sort',
                'El valor seleccionado para orden no es válido.',
            ],
            'bedrooms' => [
                ['bedrooms' => 0],
                'bedrooms',
                'El campo dormitorios debe ser mayor o igual a 1.',
            ],
            'department' => [
                ['department_code' => '99'],
                'department_code',
                'El valor seleccionado para departamento no es válido.',
            ],
            'province' => [
                ['province_code' => '9999'],
                'province_code',
                'El valor seleccionado para provincia no es válido.',
            ],
            'district' => [
                ['district_code' => '999999'],
                'district_code',
                'El valor seleccionado para distrito no es válido.',
            ],
            'min price' => [
                ['min_price' => 'abc'],
                'min_price',
                'El campo precio mínimo debe ser un número.',
            ],
            'max price' => [
                ['max_price' => -5],
                'max_price',
                'El campo precio máximo debe ser mayor o igual a 0.',
            ],
            'page' => [
                ['page' => 0],
                'page',
                'El campo página debe ser mayor o igual a 1.',
            ],
        ];
    }

    public function test_status_counts_apply_the_other_filters_across_every_page(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->count(16)->create([
            'operation' => 'rent',
            'status' => 'available',
        ]);
        Property::factory()->for($agent)->count(4)->create([
            'operation' => 'rent',
            'status' => 'sold',
        ]);
        Property::factory()->for($agent)->count(2)->create([
            'operation' => 'rent',
            'status' => 'rented',
        ]);
        Property::factory()->for($agent)->count(3)->create([
            'operation' => 'sale',
            'status' => 'available',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', [
            'operation' => 'rent',
            'status' => 'available',
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 15)
            ->where('properties.total', 16)
            ->where('counts.all', 22)
            ->where('counts.available', 16)
            ->where('counts.sold', 4)
            ->where('counts.rented', 2)
        );
    }

    public function test_panel_paginates_results_and_keeps_the_filters_in_the_page_links(): void
    {
        $agent = User::factory()->agent()->create();

        $properties = Property::factory()->for($agent)->count(16)->create([
            'status' => 'available',
        ]);

        Property::factory()->for($agent)->create([
            'status' => 'sold',
        ]);

        $firstPage = $this->actingAs($agent)->get(route('panel.properties.index', [
            'status' => 'available',
        ]));

        $firstPage->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 15)
            ->where('properties.total', 16)
            ->where('properties.current_page', 1)
            ->where('properties.last_page', 2)
        );
        $firstPageIds = collect($firstPage->inertiaProps('properties.data'))->pluck('id')->all();

        $secondPage = $this->actingAs($agent)->get(route('panel.properties.index', [
            'status' => 'available',
            'page' => 2,
        ]));

        $secondPage->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 1)
            ->where('properties.total', 16)
            ->where('properties.current_page', 2)
        );
        $secondPageIds = collect($secondPage->inertiaProps('properties.data'))->pluck('id')->all();

        $this->assertSame(
            $properties->pluck('id')->sortDesc()->values()->all(),
            [...$firstPageIds, ...$secondPageIds],
        );

        $secondPageLink = collect($firstPage->inertiaProps('properties.links'))
            ->first(fn (array $link): bool => $link['label'] === '2');

        $this->assertIsArray($secondPageLink);
        $this->assertIsString($secondPageLink['url']);
        $this->assertStringContainsString('status=available', $secondPageLink['url']);
    }

    public function test_agent_only_receives_their_own_non_archived_properties_in_results_and_counts(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();

        $ownAvailable = Property::factory()->for($agent)->create([
            'status' => 'available',
        ]);
        $ownSold = Property::factory()->for($agent)->create([
            'status' => 'sold',
        ]);
        Property::factory()->for($agent)->create([
            'status' => 'archived',
        ]);
        Property::factory()->for($otherAgent)->create([
            'status' => 'available',
            'title' => 'Casa ajena',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.properties.index'));

        $response->assertOk();

        $this->assertSame(
            [$ownSold->id, $ownAvailable->id],
            collect($response->inertiaProps('properties.data'))->pluck('id')->all(),
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->where('properties.total', 2)
            ->where('counts.all', 2)
            ->where('counts.available', 1)
            ->where('counts.sold', 1)
            ->where('counts.rented', 0)
        );
    }

    public function test_admin_receives_every_non_archived_property_and_global_counts(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->agent()->create();

        $adminProperty = Property::factory()->for($admin)->create([
            'status' => 'available',
        ]);
        $agentProperty = Property::factory()->for($agent)->create([
            'status' => 'sold',
        ]);
        Property::factory()->for($agent)->create([
            'status' => 'archived',
        ]);

        $response = $this->actingAs($admin)->get(route('panel.properties.index'));

        $response->assertOk();

        $this->assertSame(
            [$agentProperty->id, $adminProperty->id],
            collect($response->inertiaProps('properties.data'))->pluck('id')->all(),
        );

        $response->assertInertia(fn (Assert $page) => $page
            ->where('properties.total', 2)
            ->where('counts.all', 2)
            ->where('counts.available', 1)
            ->where('counts.sold', 1)
            ->where('counts.rented', 0)
        );
    }

    public function test_agent_filters_never_reveal_another_agents_properties(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa propia',
            'status' => 'available',
        ]);
        Property::factory()->for($otherAgent)->create([
            'title' => 'Casa ajena',
            'status' => 'available',
        ]);

        $searchResponse = $this->actingAs($agent)->get(route('panel.properties.index', [
            'search' => 'ajena',
        ]));

        $searchResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 0)
            ->where('properties.total', 0)
            ->where('counts.all', 0)
        );
    }

    /**
     * @return array<string, Property>
     */
    private function createFilterFixture(User $agent): array
    {
        return [
            'house' => Property::factory()->for($agent)->create([
                'title' => 'Casa en venta en Miraflores',
                'status' => 'available',
                'operation' => 'sale',
                'type' => 'house',
                'currency' => 'PEN',
                'price' => 250000,
                'bedrooms' => 3,
                'district_code' => '150122',
            ]),
            'rentedApartment' => Property::factory()->for($agent)->create([
                'title' => 'Departamento en alquiler en Yanahuara',
                'status' => 'rented',
                'operation' => 'rent',
                'type' => 'apartment',
                'currency' => 'USD',
                'price' => 1200,
                'bedrooms' => 2,
                'district_code' => '040126',
            ]),
            'soldHouse' => Property::factory()->for($agent)->create([
                'title' => 'Casa vendida en Surco',
                'status' => 'sold',
                'operation' => 'sale',
                'type' => 'house',
                'currency' => 'PEN',
                'price' => 500000,
                'bedrooms' => 4,
                'district_code' => '150140',
            ]),
            'availableApartment' => Property::factory()->for($agent)->create([
                'title' => 'Departamento disponible en Barranco',
                'status' => 'available',
                'operation' => 'rent',
                'type' => 'apartment',
                'currency' => 'USD',
                'price' => 900,
                'bedrooms' => 1,
                'district_code' => '150104',
            ]),
        ];
    }
}
