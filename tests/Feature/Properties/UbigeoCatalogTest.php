<?php

namespace Tests\Feature\Properties;

use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UbigeoCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_read_the_location_catalog(): void
    {
        $this->getJson(route('ubigeo.index'))->assertUnauthorized();
    }

    public function test_authenticated_user_receives_the_three_levels_with_their_parent_codes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('ubigeo.index'))
            ->assertOk()
            ->assertJsonCount(Department::query()->count(), 'departments')
            ->assertJsonCount(Province::query()->count(), 'provinces')
            ->assertJsonCount(District::query()->count(), 'districts');

        $department = collect($response->json('departments'))->firstWhere('code', '15');
        $province = collect($response->json('provinces'))->firstWhere('code', '1501');
        $district = collect($response->json('districts'))->firstWhere('code', '150122');

        $this->assertSame('Lima', $department['name']);

        $this->assertSame('Lima', $province['name']);
        $this->assertSame('15', $province['department_code']);

        $this->assertSame('Miraflores', $district['name']);
        $this->assertSame('1501', $district['province_code']);

        $departmentCodes = array_column($response->json('departments'), 'code');
        $sortedCodes = $departmentCodes;
        sort($sortedCodes);

        $this->assertSame($sortedCodes, $departmentCodes);
    }

    public function test_catalog_reports_when_it_has_no_locations(): void
    {
        District::query()->delete();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('ubigeo.index'))
            ->assertStatus(503)
            ->assertExactJson(['message' => 'No se pudo cargar el catálogo de ubicaciones.']);
    }

    public function test_catalog_reports_when_the_location_tables_cannot_be_read(): void
    {
        Schema::drop('districts');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('ubigeo.index'))
            ->assertStatus(503)
            ->assertExactJson(['message' => 'No se pudo cargar el catálogo de ubicaciones.']);
    }
}
