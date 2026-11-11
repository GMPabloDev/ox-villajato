<?php

namespace Tests\Feature\Seeders;

use App\Models\Department;
use App\Models\District;
use App\Models\Property;
use App\Models\Province;
use Database\Seeders\UbigeoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class UbigeoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_loads_the_official_catalog_with_its_provenance_and_totals(): void
    {
        app(UbigeoSeeder::class)->run();

        $source = $this->catalogSource();

        $this->assertSame(
            'Instituto Nacional de Estadística e Informática (INEI)',
            $source['institution'],
        );
        $this->assertSame('Sistema de Consulta de Códigos Estandarizados (SISCONCODE)', $source['service']);
        $this->assertSame('2026', $source['catalog_version']);
        $this->assertSame('2026-11-10', $source['consulted_at']);

        $this->assertSame(25, Department::query()->count());
        $this->assertSame(196, Province::query()->count());
        $this->assertSame(1893, District::query()->count());
        $this->assertSame($source['totals'], [
            'departments' => Department::query()->count(),
            'provinces' => Province::query()->count(),
            'districts' => District::query()->count(),
        ]);
    }

    public function test_loading_the_catalog_again_keeps_the_same_rows(): void
    {
        app(UbigeoSeeder::class)->run();
        app(UbigeoSeeder::class)->run();

        $this->assertSame(25, Department::query()->count());
        $this->assertSame(196, Province::query()->count());
        $this->assertSame(1893, District::query()->count());
    }

    public function test_every_level_of_the_catalog_matches_its_parent(): void
    {
        District::query()->with('province.department')->each(function (District $district): void {
            $this->assertSame($district->province_code, $district->province->code);
            $this->assertSame($district->province->department_code, $district->province->department->code);
            $this->assertStringStartsWith($district->province_code, $district->code);
            $this->assertStringStartsWith($district->province->department_code, $district->province->code);
        });

        $district = District::query()->whereKey('150122')->sole();

        $this->assertSame('Miraflores', $district->name);
        $this->assertSame('Lima', $district->province->name);
        $this->assertSame('Lima', $district->province->department->name);
    }

    public function test_homonymous_districts_are_distinguished_by_their_code_and_hierarchy(): void
    {
        $repeatedNames = District::query()
            ->pluck('name')
            ->countBy()
            ->filter(fn (int $districts): bool => $districts > 1);

        $this->assertCount(99, $repeatedNames);
        $this->assertSame(4, $repeatedNames['Miraflores']);
        $this->assertSame(4, District::query()->where('name', 'Miraflores')->count());

        $lima = District::query()->whereKey('150122')->sole();
        $arequipa = District::query()->whereKey('040110')->sole();

        $this->assertSame('Miraflores', $lima->name);
        $this->assertSame('Miraflores', $arequipa->name);
        $this->assertNotSame($lima->code, $arequipa->code);
        $this->assertSame(
            ['Lima', 'Lima', 'Miraflores'],
            [$lima->province->department->name, $lima->province->name, $lima->name],
        );
        $this->assertSame(
            ['Arequipa', 'Arequipa', 'Miraflores'],
            [$arequipa->province->department->name, $arequipa->province->name, $arequipa->name],
        );
    }

    public function test_a_district_cannot_be_removed_while_a_property_uses_it(): void
    {
        Property::factory()->create(['district_code' => '150122']);

        $this->expectException(QueryException::class);

        District::query()->whereKey('150122')->delete();
    }

    /**
     * @return array{
     *     institution: string,
     *     service: string,
     *     catalog_version: string,
     *     consulted_at: string,
     *     totals: array{departments: int, provinces: int, districts: int}
     * }
     */
    private function catalogSource(): array
    {
        $catalog = json_decode(
            File::get(database_path(UbigeoSeeder::CATALOG_PATH)),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        /** @var array{institution: string, service: string, catalog_version: string, consulted_at: string, totals: array{departments: int, provinces: int, districts: int}} $source */
        $source = $catalog['source'];

        return $source;
    }
}
