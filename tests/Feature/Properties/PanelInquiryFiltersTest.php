<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\PropertyInquiry as PropertyInquiryModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PanelInquiryFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_name_email_property_title_and_message_ignoring_case_and_accents(): void
    {
        $agent = User::factory()->agent()->create([
            'name' => 'Publicadora Única',
        ]);
        $jesusMaria = Property::factory()->for($agent)->create([
            'title' => 'Casa en Jesús María',
        ]);
        $barranco = Property::factory()->for($agent)->create([
            'title' => 'Departamento en Barranco',
        ]);

        $ana = PropertyInquiryModel::factory()->for($jesusMaria)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana.perez@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Quisiera coordinar una visita.',
        ]);
        $luis = PropertyInquiryModel::factory()->for($barranco)->create([
            'name' => 'Luis Rojas',
            'email' => 'luis@example.com',
            'phone' => null,
            'message' => 'Me interesa el jardín interior.',
        ]);

        $cases = [
            'name' => ['ana', [$ana->id]],
            'name with case and accent' => ['PÉREZ', [$ana->id]],
            'email' => ['ANA.PEREZ@EXAMPLE.COM', [$ana->id]],
            'property title without accents' => ['jesus maria', [$ana->id]],
            'message without accents' => ['JARDÍN', [$luis->id]],
            'property title' => ['barranco', [$luis->id]],
            'phone is not searched' => ['987 654', []],
            'publisher is not searched' => ['Publicadora Única', []],
            'like wildcards match literally' => ['%', []],
        ];

        foreach ($cases as $description => [$term, $expectedIds]) {
            $response = $this->actingAs($agent)->get(route('panel.inquiries.index', [
                'search' => $term,
            ]));

            $response->assertOk();

            $this->assertSame(
                $expectedIds,
                collect($response->inertiaProps('inquiries.data'))->pluck('id')->all(),
                "La búsqueda «{$term}» ({$description}) devolvió consultas inesperadas.",
            );
        }
    }

    public function test_search_and_property_filters_combine_so_every_result_matches_both(): void
    {
        $agent = User::factory()->agent()->create();
        $miraflores = Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
        ]);
        $surco = Property::factory()->for($agent)->create([
            'title' => 'Casa en Surco',
        ]);

        $anaMiraflores = PropertyInquiryModel::factory()->for($miraflores)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'message' => 'Consulta número uno.',
            'created_at' => '2026-10-28 10:00:00',
        ]);
        $anaSurco = PropertyInquiryModel::factory()->for($surco)->create([
            'name' => 'Ana Torres',
            'email' => 'ana.torres@example.com',
            'message' => 'Consulta número dos.',
            'created_at' => '2026-10-28 11:00:00',
        ]);
        $luisSurco = PropertyInquiryModel::factory()->for($surco)->create([
            'name' => 'Luis Rojas',
            'email' => 'luis@example.com',
            'message' => 'Consulta número tres.',
            'created_at' => '2026-10-28 12:00:00',
        ]);

        $cases = [
            'search only' => [
                ['search' => 'ana'],
                [$anaSurco->id, $anaMiraflores->id],
            ],
            'property only' => [
                ['property' => $surco->id],
                [$luisSurco->id, $anaSurco->id],
            ],
            'search and property' => [
                ['search' => 'ana', 'property' => $surco->id],
                [$anaSurco->id],
            ],
            'search and property without matches' => [
                ['search' => 'luis', 'property' => $miraflores->id],
                [],
            ],
        ];

        foreach ($cases as $description => [$query, $expectedIds]) {
            $response = $this->actingAs($agent)->get(route('panel.inquiries.index', $query));

            $response->assertOk();

            $this->assertSame(
                $expectedIds,
                collect($response->inertiaProps('inquiries.data'))->pluck('id')->all(),
                "El caso «{$description}» devolvió consultas inesperadas.",
            );
        }
    }

    public function test_the_panel_orders_inquiries_by_received_date_in_both_directions(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
        ]);

        $oldest = PropertyInquiryModel::factory()->for($property)->create([
            'name' => 'Ana Antigua',
            'email' => 'ana.antigua@example.com',
            'message' => 'Consulta número uno.',
            'created_at' => '2026-10-01 09:00:00',
        ]);
        $middle = PropertyInquiryModel::factory()->for($property)->create([
            'name' => 'Ana Intermedia',
            'email' => 'ana.intermedia@example.com',
            'message' => 'Consulta número dos.',
            'created_at' => '2026-10-15 12:00:00',
        ]);
        $newest = PropertyInquiryModel::factory()->for($property)->create([
            'name' => 'Ana Reciente',
            'email' => 'ana.reciente@example.com',
            'message' => 'Consulta número tres.',
            'created_at' => '2026-10-28 18:30:00',
        ]);

        $defaultResponse = $this->actingAs($agent)->get(route('panel.inquiries.index'));

        $defaultResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'recent')
        );
        $this->assertSame(
            [$newest->id, $middle->id, $oldest->id],
            collect($defaultResponse->inertiaProps('inquiries.data'))->pluck('id')->all(),
        );

        $explicitRecentResponse = $this->actingAs($agent)->get(route('panel.inquiries.index', [
            'sort' => 'recent',
        ]));

        $explicitRecentResponse->assertOk();
        $this->assertSame(
            [$newest->id, $middle->id, $oldest->id],
            collect($explicitRecentResponse->inertiaProps('inquiries.data'))->pluck('id')->all(),
        );

        $oldestResponse = $this->actingAs($agent)->get(route('panel.inquiries.index', [
            'sort' => 'oldest',
        ]));

        $oldestResponse->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'oldest')
        );
        $this->assertSame(
            [$oldest->id, $middle->id, $newest->id],
            collect($oldestResponse->inertiaProps('inquiries.data'))->pluck('id')->all(),
        );
    }

    public function test_filtered_results_paginate_fifteen_and_keep_the_query_in_the_pagination_links(): void
    {
        $agent = User::factory()->agent()->create();
        $miraflores = Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
        ]);
        $surco = Property::factory()->for($agent)->create([
            'title' => 'Casa en Surco',
        ]);

        $matchingIds = [];

        for ($index = 1; $index <= 18; $index++) {
            $matchingIds[] = PropertyInquiryModel::factory()->for($miraflores)->create([
                'name' => "Ana {$index}",
                'email' => "ana{$index}@example.com",
                'message' => "Consulta número {$index}.",
                'created_at' => sprintf('2026-10-01 09:%02d:00', $index),
            ])->id;
        }

        for ($index = 1; $index <= 4; $index++) {
            PropertyInquiryModel::factory()->for($surco)->create([
                'name' => "Luis {$index}",
                'email' => "luis{$index}@example.com",
                'message' => "Consulta distinta {$index}.",
            ]);
        }

        $query = [
            'search' => 'ana',
            'property' => $miraflores->id,
            'sort' => 'oldest',
        ];

        $firstPage = $this->actingAs($agent)->get(route('panel.inquiries.index', $query));

        $firstPage->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 15)
            ->where('inquiries.total', 18)
            ->where('inquiries.current_page', 1)
            ->where('inquiries.last_page', 2)
            ->where('accessibleTotal', 22)
        );
        $this->assertSame(
            array_slice($matchingIds, 0, 15),
            collect($firstPage->inertiaProps('inquiries.data'))->pluck('id')->all(),
        );
        $this->assertSame(
            [
                ['id' => $miraflores->id, 'title' => 'Casa en Miraflores'],
                ['id' => $surco->id, 'title' => 'Casa en Surco'],
            ],
            $firstPage->inertiaProps('propertyOptions'),
        );

        $secondPageUrl = collect($firstPage->inertiaProps('inquiries.links'))
            ->pluck('url')
            ->filter(fn (?string $url): bool => $url !== null && str_contains($url, 'page=2'))
            ->first();

        $this->assertIsString($secondPageUrl);

        parse_str((string) parse_url($secondPageUrl, PHP_URL_QUERY), $secondPageQuery);

        $this->assertEquals([
            'search' => 'ana',
            'property' => (string) $miraflores->id,
            'sort' => 'oldest',
            'page' => '2',
        ], $secondPageQuery);

        $secondPage = $this->actingAs($agent)->get($secondPageUrl);

        $secondPage->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 3)
            ->where('inquiries.total', 18)
            ->where('inquiries.current_page', 2)
            ->where('accessibleTotal', 22)
        );
        $this->assertSame(
            array_slice($matchingIds, 15),
            collect($secondPage->inertiaProps('inquiries.data'))->pluck('id')->all(),
        );
    }

    public function test_out_of_range_pages_redirect_to_the_last_page_with_filters(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create();
        PropertyInquiryModel::factory()->count(16)->for($property)->create(['name' => 'Ana']);

        $query = ['search' => 'Ana', 'property' => $property->id, 'sort' => 'oldest'];

        $this->actingAs($agent)->get(route('panel.inquiries.index', [...$query, 'page' => 99]))
            ->assertRedirect(route('panel.inquiries.index', [...$query, 'page' => 2]));

        $emptyQuery = [...$query, 'search' => 'No existe'];
        $this->actingAs($agent)->get(route('panel.inquiries.index', [...$emptyQuery, 'page' => 99]))
            ->assertRedirect(route('panel.inquiries.index', [...$emptyQuery, 'page' => 1]));
        $this->actingAs($agent)->get(route('panel.inquiries.index', [...$emptyQuery, 'page' => 1]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('inquiries.total', 0)->where('inquiries.current_page', 1));
    }

    public function test_the_property_selector_distinguishes_properties_that_share_a_title(): void
    {
        $admin = User::factory()->admin()->create();
        $firstAgent = User::factory()->agent()->create();
        $secondAgent = User::factory()->agent()->create();
        $firstProperty = Property::factory()->for($firstAgent)->create([
            'title' => 'Casa familiar',
        ]);
        $secondProperty = Property::factory()->for($secondAgent)->create([
            'title' => 'Casa familiar',
        ]);

        $firstInquiry = PropertyInquiryModel::factory()->for($firstProperty)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'message' => 'Consulta número uno.',
        ]);
        $secondInquiry = PropertyInquiryModel::factory()->for($secondProperty)->create([
            'name' => 'Luis Rojas',
            'email' => 'luis@example.com',
            'message' => 'Consulta número dos.',
        ]);

        $response = $this->actingAs($admin)->get(route('panel.inquiries.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 2)
            ->where('accessibleTotal', 2)
        );

        $this->assertSame(
            [
                ['id' => $firstProperty->id, 'title' => 'Casa familiar'],
                ['id' => $secondProperty->id, 'title' => 'Casa familiar'],
            ],
            $response->inertiaProps('propertyOptions'),
        );

        $filtered = $this->actingAs($admin)->get(route('panel.inquiries.index', [
            'property' => $secondProperty->id,
        ]));

        $filtered->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 1)
            ->where('inquiries.data.0.id', $secondInquiry->id)
            ->where('inquiries.total', 1)
            ->where('accessibleTotal', 2)
            ->where('filters.property', $secondProperty->id)
        );
    }

    public function test_inquiries_of_deleted_properties_stay_listed_and_selectable(): void
    {
        $agent = User::factory()->agent()->create();
        $deletedTitle = 'Casa eliminada con consultas';
        $deletedProperty = Property::factory()->for($agent)->create([
            'status' => 'archived',
            'title' => $deletedTitle,
        ]);
        $deletedInquiry = PropertyInquiryModel::factory()->for($deletedProperty)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'message' => 'Quisiera coordinar una visita.',
        ]);
        $currentProperty = Property::factory()->for($agent)->create([
            'title' => 'Casa vigente',
        ]);
        PropertyInquiryModel::factory()->for($currentProperty)->create([
            'name' => 'Luis Rojas',
            'email' => 'luis@example.com',
            'message' => 'Consulta vigente.',
        ]);

        $deletedProperty->delete();

        $this->assertDatabaseMissing('properties', ['id' => $deletedProperty->id]);

        $filtered = $this->actingAs($agent)->get(route('panel.inquiries.index', [
            'property' => $deletedProperty->id,
        ]));

        $filtered->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 1)
            ->where('inquiries.data.0.id', $deletedInquiry->id)
            ->where('inquiries.data.0.property.title', $deletedTitle)
            ->where('inquiries.total', 1)
            ->where('accessibleTotal', 2)
            ->where('filters.property', $deletedProperty->id)
        );

        $this->assertSame(
            [
                ['id' => $deletedProperty->id, 'title' => $deletedTitle],
                ['id' => $currentProperty->id, 'title' => 'Casa vigente'],
            ],
            $this->actingAs($agent)->get(route('panel.inquiries.index'))->inertiaProps('propertyOptions'),
        );
    }

    public function test_inquiries_of_closed_and_archived_properties_are_still_listed_and_filterable(): void
    {
        $agent = User::factory()->agent()->create();
        $soldProperty = Property::factory()->for($agent)->create([
            'status' => 'sold',
            'title' => 'Casa vendida',
        ]);
        $archivedProperty = Property::factory()->for($agent)->create([
            'status' => 'archived',
            'title' => 'Casa archivada',
        ]);
        $rentedProperty = Property::factory()->for($agent)->create([
            'status' => 'rented',
            'title' => 'Casa alquilada',
        ]);
        $rentedInquiry = PropertyInquiryModel::factory()->for($rentedProperty)->create();
        $soldInquiry = PropertyInquiryModel::factory()->for($soldProperty)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'message' => 'Consulta de la casa vendida.',
        ]);
        $archivedInquiry = PropertyInquiryModel::factory()->for($archivedProperty)->create([
            'name' => 'Luis Rojas',
            'email' => 'luis@example.com',
            'message' => 'Consulta de la casa archivada.',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.inquiries.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 3)
            ->where('accessibleTotal', 3)
        );
        $this->assertEqualsCanonicalizing(
            [$soldInquiry->id, $archivedInquiry->id, $rentedInquiry->id],
            collect($response->inertiaProps('inquiries.data'))->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [
                ['id' => $soldProperty->id, 'title' => 'Casa vendida'],
                ['id' => $archivedProperty->id, 'title' => 'Casa archivada'],
                ['id' => $rentedProperty->id, 'title' => 'Casa alquilada'],
            ],
            $response->inertiaProps('propertyOptions'),
        );

        $this->actingAs($agent)
            ->get(route('panel.inquiries.index', ['property' => $archivedProperty->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('inquiries.data', 1)
                ->where('inquiries.data.0.id', $archivedInquiry->id)
            );

        $this->actingAs($agent)
            ->get(route('panel.inquiries.index', ['property' => $rentedProperty->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('inquiries.data', 1)
                ->where('inquiries.data.0.id', $rentedInquiry->id)
            );
    }

    public function test_agent_results_totals_and_property_options_exclude_other_agents_inquiries(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();
        $ownProperty = Property::factory()->for($agent)->create([
            'title' => 'Casa propia en Surco',
        ]);
        $otherProperty = Property::factory()->for($otherAgent)->create([
            'title' => 'Casa ajena en Surco',
        ]);

        $ownInquiry = PropertyInquiryModel::factory()->for($ownProperty)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'message' => 'Consulta número uno.',
        ]);
        PropertyInquiryModel::factory()->for($otherProperty)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'message' => 'Consulta número uno.',
        ]);

        $searched = $this->actingAs($agent)->get(route('panel.inquiries.index', [
            'search' => 'ana perez',
        ]));

        $searched->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 1)
            ->where('inquiries.data.0.id', $ownInquiry->id)
            ->where('inquiries.total', 1)
            ->where('accessibleTotal', 1)
            ->where('canSeeAllInquiries', false)
        );
        $this->assertSame(
            [
                ['id' => $ownProperty->id, 'title' => 'Casa propia en Surco'],
            ],
            $searched->inertiaProps('propertyOptions'),
        );

        $otherPropertyFilter = $this->actingAs($agent)->get(route('panel.inquiries.index', [
            'property' => $otherProperty->id,
        ]));

        $otherPropertyFilter->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 0)
            ->where('inquiries.total', 0)
            ->where('accessibleTotal', 1)
            ->where('filters.property', $otherProperty->id)
        );
        $this->assertSame(
            [
                ['id' => $ownProperty->id, 'title' => 'Casa propia en Surco'],
            ],
            $otherPropertyFilter->inertiaProps('propertyOptions'),
        );
    }

    public function test_admin_searches_and_lists_inquiries_from_every_agent(): void
    {
        $admin = User::factory()->admin()->create();
        $firstAgent = User::factory()->agent()->create();
        $secondAgent = User::factory()->agent()->create();
        $adminProperty = Property::factory()->for($admin)->create([
            'title' => 'Casa del administrador',
        ]);
        $firstAgentProperty = Property::factory()->for($firstAgent)->create([
            'title' => 'Casa en Barranco',
        ]);
        $secondAgentProperty = Property::factory()->for($secondAgent)->create([
            'title' => 'Departamento en Miraflores',
        ]);

        PropertyInquiryModel::factory()->for($adminProperty)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'message' => 'Consulta número uno.',
            'created_at' => '2026-10-28 10:00:00',
        ]);
        $barrancoInquiry = PropertyInquiryModel::factory()->for($firstAgentProperty)->create([
            'name' => 'Luis Rojas',
            'email' => 'luis@example.com',
            'message' => 'Consulta número dos.',
            'created_at' => '2026-10-28 11:00:00',
        ]);
        PropertyInquiryModel::factory()->for($secondAgentProperty)->create([
            'name' => 'Carla Torres',
            'email' => 'carla@example.com',
            'message' => 'Consulta número tres.',
            'created_at' => '2026-10-28 12:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('panel.inquiries.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 3)
            ->where('inquiries.total', 3)
            ->where('accessibleTotal', 3)
            ->where('canSeeAllInquiries', true)
        );
        $this->assertSame(
            [
                ['id' => $adminProperty->id, 'title' => 'Casa del administrador'],
                ['id' => $firstAgentProperty->id, 'title' => 'Casa en Barranco'],
                ['id' => $secondAgentProperty->id, 'title' => 'Departamento en Miraflores'],
            ],
            $response->inertiaProps('propertyOptions'),
        );

        $searched = $this->actingAs($admin)->get(route('panel.inquiries.index', [
            'search' => 'barranco',
        ]));

        $searched->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 1)
            ->where('inquiries.data.0.id', $barrancoInquiry->id)
            ->where('inquiries.total', 1)
            ->where('accessibleTotal', 3)
        );
        $this->assertCount(3, $searched->inertiaProps('propertyOptions'));
    }

    public function test_the_panel_reports_the_normalized_filters(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
        ]);
        PropertyInquiryModel::factory()->for($property)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'message' => 'Consulta número uno.',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.inquiries.index', [
            'search' => '  Ana  ',
            'property' => $property->id,
            'sort' => 'oldest',
        ]));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 1)
            ->where('filters.search', 'Ana')
            ->where('filters.property', $property->id)
            ->where('filters.sort', 'oldest')
        );
    }

    public function test_all_and_any_control_values_mean_no_property_filter(): void
    {
        $agent = User::factory()->agent()->create();
        $firstProperty = Property::factory()->for($agent)->create([
            'title' => 'Casa en Miraflores',
        ]);
        $secondProperty = Property::factory()->for($agent)->create([
            'title' => 'Casa en Surco',
        ]);
        PropertyInquiryModel::factory()->for($firstProperty)->create();
        PropertyInquiryModel::factory()->for($secondProperty)->create();

        foreach (['all', 'any'] as $value) {
            $response = $this->actingAs($agent)->get(route('panel.inquiries.index', [
                'property' => $value,
            ]));

            $response->assertOk()->assertInertia(fn (Assert $page) => $page
                ->has('inquiries.data', 2)
                ->where('inquiries.total', 2)
                ->where('filters.property', null)
            );
        }

        $this->actingAs($agent)
            ->get(route('panel.inquiries.index', ['search' => '   ']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('inquiries.data', 2)
                ->where('filters.search', null)
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
        $property = Property::factory()->for($agent)->create();
        PropertyInquiryModel::factory()->for($property)->create();

        $response = $this->actingAs($agent)->get(route('panel.inquiries.index', $query));

        $response
            ->assertRedirect(route('panel.inquiries.index'))
            ->assertSessionHasErrors([$errorKey => $message]);
    }

    /**
     * @return array<string, array{array<string, string|int>, string, string}>
     */
    public static function invalidFilterCases(): array
    {
        return [
            'property' => [
                ['property' => 'abc'],
                'property',
                'El campo propiedad debe ser un número entero.',
            ],
            'property below range' => [
                ['property' => 0],
                'property',
                'El campo propiedad debe ser mayor o igual a 1.',
            ],
            'sort' => [
                ['sort' => 'title'],
                'sort',
                'El valor seleccionado para orden no es válido.',
            ],
            'page' => [
                ['page' => 0],
                'page',
                'El campo página debe ser mayor o igual a 1.',
            ],
        ];
    }
}
