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

    public function test_search_matches_title_city_and_zone_ignoring_case_and_accents(): void
    {
        $agent = User::factory()->agent()->create();

        $jesusMaria = Property::factory()->for($agent)->create([
            'title' => 'Casa en Jesús María',
            'description' => 'Frente al parque.',
            'city' => 'Lima',
            'zone' => 'Jesús María',
        ]);
        $barranco = Property::factory()->for($agent)->create([
            'title' => 'Departamento en Barranco',
            'description' => 'Cerca de la iglesia Jesús María.',
            'city' => 'Arequipa',
            'zone' => 'Yanahuara',
        ]);
        $wanchaq = Property::factory()->for($agent)->create([
            'title' => 'Local céntrico',
            'description' => 'A una cuadra de la plaza.',
            'city' => 'Cusco',
            'zone' => 'Wanchaq',
        ]);

        $cases = [
            'jesus maria' => [$jesusMaria->id],
            'CASA' => [$jesusMaria->id],
            'arequipa' => [$barranco->id],
            'yanahuara' => [$barranco->id],
            'JESÚS' => [$jesusMaria->id],
            'wánchaq' => [$wanchaq->id],
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
            'title' => 'Àtico luminoso',
            'city' => 'Évry',
            'zone' => 'München',
        ]);

        foreach (['atico', 'EVRY', 'munchen'] as $term) {
            $response = $this->actingAs($agent)->get(route('panel.properties.index', ['search' => $term]));

            $response->assertOk();
            $this->assertSame([$property->id], collect($response->inertiaProps('properties.data'))->pluck('id')->all());
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
            'city' => [
                ['city' => 'Lima'],
                ['Departamento disponible en Barranco', 'Casa vendida en Surco', 'Casa en venta en Miraflores'],
            ],
            'zone' => [
                ['zone' => 'Surco'],
                ['Casa vendida en Surco'],
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
            'search combined with another filter' => [
                ['search' => 'departamento', 'city' => 'Lima'],
                ['Departamento disponible en Barranco'],
            ],
            'combined filters' => [
                [
                    'status' => 'available',
                    'operation' => 'sale',
                    'type' => 'house',
                    'city' => 'Lima',
                    'zone' => 'Miraflores',
                    'currency' => 'PEN',
                    'min_price' => 200000,
                    'max_price' => 300000,
                    'bedrooms' => 2,
                ],
                ['Casa en venta en Miraflores'],
            ],
        ];
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
            'city' => 'Lima',
            'zone' => 'Miraflores',
            'currency' => 'PEN',
            'price' => 250000,
            'bedrooms' => 3,
        ]);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', [
            'search' => '  Casa  ',
            'status' => 'available',
            'operation' => 'sale',
            'type' => 'house',
            'city' => 'Lima',
            'zone' => 'Miraflores',
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
            ->where('filters.city', 'Lima')
            ->where('filters.zone', 'Miraflores')
            ->where('filters.currency', 'PEN')
            ->where('filters.min_price', '200000')
            ->where('filters.max_price', '300000')
            ->where('filters.bedrooms', 2)
            ->where('filters.sort', 'title')
        );
    }

    public function test_location_names_all_and_any_are_real_filters(): void
    {
        $agent = User::factory()->agent()->create();
        $first = Property::factory()->for($agent)->create(['city' => 'all', 'zone' => 'any']);
        $second = Property::factory()->for($agent)->create(['city' => 'any', 'zone' => 'all']);
        Property::factory()->for($agent)->create(['city' => 'Lima', 'zone' => 'Surco']);

        foreach ([['city', 'all', $first], ['city', 'any', $second], ['zone', 'any', $first], ['zone', 'all', $second]] as [$key, $value, $property]) {
            $response = $this->actingAs($agent)->get(route('panel.properties.index', [$key => $value]));
            $response->assertOk();
            $this->assertSame([$property->id], collect($response->inertiaProps('properties.data'))->pluck('id')->all());
        }
    }

    public function test_out_of_range_page_redirects_to_last_page_preserving_filters(): void
    {
        $agent = User::factory()->agent()->create();
        Property::factory()->for($agent)->create(['city' => 'Lima']);
        $response = $this->actingAs($agent)->get(route('panel.properties.index', ['city' => 'Lima', 'page' => 2]));
        $response->assertRedirect(route('panel.properties.index', ['city' => 'Lima', 'page' => 1]));
        $this->followingRedirects()->actingAs($agent)->get(route('panel.properties.index', ['city' => 'Lima', 'page' => 2]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('properties.data', 1)->where('filters.city', 'Lima'));
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
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 3)
            ->where('filters.status', null)
            ->where('filters.operation', null)
            ->where('filters.type', null)
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
            'city' => 'Lima',
        ]);
        Property::factory()->for($agent)->count(4)->create([
            'operation' => 'rent',
            'status' => 'sold',
            'city' => 'Lima',
        ]);
        Property::factory()->for($agent)->count(2)->create([
            'operation' => 'rent',
            'status' => 'rented',
            'city' => 'Lima',
        ]);
        Property::factory()->for($agent)->count(3)->create([
            'operation' => 'sale',
            'status' => 'available',
            'city' => 'Lima',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.properties.index', [
            'operation' => 'rent',
            'city' => 'Lima',
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
            'city' => 'Lima',
        ]);

        Property::factory()->for($agent)->create([
            'status' => 'available',
            'city' => 'Cusco',
        ]);

        $firstPage = $this->actingAs($agent)->get(route('panel.properties.index', [
            'city' => 'Lima',
        ]));

        $firstPage->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 15)
            ->where('properties.total', 16)
            ->where('properties.current_page', 1)
            ->where('properties.last_page', 2)
        );
        $firstPageIds = collect($firstPage->inertiaProps('properties.data'))->pluck('id')->all();

        $secondPage = $this->actingAs($agent)->get(route('panel.properties.index', [
            'city' => 'Lima',
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
        $this->assertStringContainsString('city=Lima', $secondPageLink['url']);
    }

    public function test_agent_only_receives_their_own_non_archived_properties_in_results_counts_and_options(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();

        $ownAvailable = Property::factory()->for($agent)->create([
            'status' => 'available',
            'city' => 'Lima',
            'zone' => 'Miraflores',
        ]);
        $ownSold = Property::factory()->for($agent)->create([
            'status' => 'sold',
            'city' => 'Lima',
            'zone' => 'Surco',
        ]);
        Property::factory()->for($agent)->create([
            'status' => 'archived',
            'city' => 'Piura',
            'zone' => 'Castilla',
        ]);
        Property::factory()->for($otherAgent)->create([
            'status' => 'available',
            'title' => 'Casa ajena',
            'city' => 'Arequipa',
            'zone' => 'Yanahuara',
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
            ->where('cities', ['Lima'])
            ->where('zones', ['Miraflores', 'Surco'])
        );
    }

    public function test_admin_receives_every_non_archived_property_and_global_counts(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->agent()->create();

        $adminProperty = Property::factory()->for($admin)->create([
            'status' => 'available',
            'city' => 'Áncash',
        ]);
        $agentProperty = Property::factory()->for($agent)->create([
            'status' => 'sold',
            'city' => 'Cusco',
        ]);
        Property::factory()->for($agent)->create([
            'status' => 'archived',
            'city' => 'Piura',
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
            ->where('cities', ['Áncash', 'Cusco'])
        );
    }

    public function test_agent_filters_never_reveal_another_agents_properties(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create([
            'title' => 'Casa propia',
            'status' => 'available',
            'city' => 'Lima',
            'zone' => 'Miraflores',
        ]);
        Property::factory()->for($otherAgent)->create([
            'title' => 'Casa ajena',
            'status' => 'available',
            'city' => 'Arequipa',
            'zone' => 'Yanahuara',
        ]);

        $searchResponse = $this->actingAs($agent)->get(route('panel.properties.index', [
            'search' => 'ajena',
        ]));

        $searchResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', 0)
            ->where('properties.total', 0)
            ->where('counts.all', 0)
            ->where('cities', ['Lima'])
            ->where('zones', ['Miraflores'])
        );

        foreach ([['city' => 'Arequipa'], ['zone' => 'Yanahuara']] as $query) {
            $response = $this->actingAs($agent)->get(route('panel.properties.index', $query));

            $response->assertOk()->assertInertia(fn (Assert $page) => $page
                ->has('properties.data', 0)
                ->where('properties.total', 0)
                ->where('counts.all', 0)
                ->where('cities', ['Lima'])
            );

            $this->assertNotContains('Yanahuara', $response->inertiaProps('zones'));
        }
    }

    public function test_city_filter_limits_zone_options_to_that_city(): void
    {
        $agent = User::factory()->agent()->create();

        $locations = [
            ['Lima', 'Miraflores'],
            ['Lima', 'Surco'],
            ['Arequipa', 'Yanahuara'],
            ['Arequipa', 'Cayma'],
            ['Cusco', 'Wanchaq'],
        ];

        foreach ($locations as [$city, $zone]) {
            Property::factory()->for($agent)->create([
                'city' => $city,
                'zone' => $zone,
            ]);
        }

        $this->actingAs($agent)
            ->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('cities', ['Arequipa', 'Cusco', 'Lima'])
                ->where('zones', ['Cayma', 'Miraflores', 'Surco', 'Wanchaq', 'Yanahuara'])
            );

        $this->actingAs($agent)
            ->get(route('panel.properties.index', ['city' => 'Lima']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('cities', ['Arequipa', 'Cusco', 'Lima'])
                ->where('zones', ['Miraflores', 'Surco'])
            );
    }

    public function test_zone_options_are_grouped_by_city_without_revealing_other_agents_properties(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();

        foreach ([['Lima', 'surco'], ['Lima', 'Miraflores'], ['Arequipa', 'Yanahuara']] as [$city, $zone]) {
            Property::factory()->for($agent)->create([
                'city' => $city,
                'zone' => $zone,
            ]);
        }

        Property::factory()->for($agent)->create([
            'status' => 'archived',
            'city' => 'Piura',
            'zone' => 'Castilla',
        ]);
        Property::factory()->for($otherAgent)->create([
            'city' => 'Arequipa',
            'zone' => 'Cayma',
        ]);

        $this->actingAs($agent)
            ->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('zonesByCity.Arequipa', ['Yanahuara'])
                ->where('zonesByCity.Lima', ['Miraflores', 'surco'])
                ->missing('zonesByCity.Piura')
                ->where('zones', ['Miraflores', 'surco', 'Yanahuara'])
            );
    }

    public function test_location_options_are_sorted_without_case_or_accents(): void
    {
        $agent = User::factory()->agent()->create();

        foreach ([['lima', 'surco'], ['árequipa', 'cayma'], ['Cusco', 'Wanchaq']] as [$city, $zone]) {
            Property::factory()->for($agent)->create([
                'city' => $city,
                'zone' => $zone,
            ]);
        }

        $this->actingAs($agent)
            ->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('cities', ['árequipa', 'Cusco', 'lima'])
                ->where('zones', ['cayma', 'surco', 'Wanchaq'])
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
                'city' => 'Lima',
                'zone' => 'Miraflores',
                'currency' => 'PEN',
                'price' => 250000,
                'bedrooms' => 3,
            ]),
            'rentedApartment' => Property::factory()->for($agent)->create([
                'title' => 'Departamento en alquiler en Yanahuara',
                'status' => 'rented',
                'operation' => 'rent',
                'type' => 'apartment',
                'city' => 'Arequipa',
                'zone' => 'Yanahuara',
                'currency' => 'USD',
                'price' => 1200,
                'bedrooms' => 2,
            ]),
            'soldHouse' => Property::factory()->for($agent)->create([
                'title' => 'Casa vendida en Surco',
                'status' => 'sold',
                'operation' => 'sale',
                'type' => 'house',
                'city' => 'Lima',
                'zone' => 'Surco',
                'currency' => 'PEN',
                'price' => 500000,
                'bedrooms' => 4,
            ]),
            'availableApartment' => Property::factory()->for($agent)->create([
                'title' => 'Departamento disponible en Barranco',
                'status' => 'available',
                'operation' => 'rent',
                'type' => 'apartment',
                'city' => 'Lima',
                'zone' => 'Barranco',
                'currency' => 'USD',
                'price' => 900,
                'bedrooms' => 1,
            ]),
        ];
    }
}
