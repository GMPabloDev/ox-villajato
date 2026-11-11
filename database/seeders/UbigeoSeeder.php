<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;

/**
 * Load the local copy of the official INEI ubigeo catalog.
 *
 * The catalog is reference data: existing rows are updated and missing rows are
 * added, but rows that disappear from a newer catalog are kept so properties
 * that still point at them remain valid.
 */
class UbigeoSeeder extends Seeder
{
    /**
     * Local copy of the official catalog, relative to the database directory.
     */
    public const CATALOG_PATH = 'data/ubigeo.json';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalog = $this->readCatalog();

        DB::transaction(function () use ($catalog): void {
            Department::query()->upsert($catalog['departments'], ['code'], ['name']);
            Province::query()->upsert($catalog['provinces'], ['code'], ['department_code', 'name']);
            District::query()->upsert($catalog['districts'], ['code'], ['province_code', 'name']);
        });
    }

    /**
     * Read the catalog and verify its provenance, codes and hierarchy.
     *
     * @return array{
     *     departments: list<array{code: string, name: string}>,
     *     provinces: list<array{code: string, name: string, department_code: string}>,
     *     districts: list<array{code: string, name: string, province_code: string}>
     * }
     */
    private function readCatalog(): array
    {
        $path = database_path(self::CATALOG_PATH);

        if (! File::isFile($path)) {
            throw new RuntimeException("No se encontró el catálogo de ubigeos en [{$path}].");
        }

        try {
            $catalog = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("El catálogo de ubigeos [{$path}] no es un JSON válido.", 0, $exception);
        }

        if (! is_array($catalog)) {
            throw new RuntimeException("El catálogo de ubigeos [{$path}] no tiene la estructura esperada.");
        }

        $this->assertSource($catalog['source'] ?? null);

        $departments = [];
        $provinces = [];
        $districts = [];

        foreach ($this->records($catalog['departments'] ?? null, 'departamentos') as $departmentRecord) {
            [$departmentCode, $departmentName] = $this->location($departmentRecord, 2, 'departamento');

            $this->assertNewCode($departments, $departmentCode, 'departamento');

            $departments[$departmentCode] = ['code' => $departmentCode, 'name' => $departmentName];

            foreach ($this->records($departmentRecord['provinces'] ?? null, 'provincias') as $provinceRecord) {
                [$provinceCode, $provinceName] = $this->location($provinceRecord, 4, 'provincia', $departmentCode);

                $this->assertNewCode($provinces, $provinceCode, 'provincia');

                $provinces[$provinceCode] = [
                    'code' => $provinceCode,
                    'name' => $provinceName,
                    'department_code' => $departmentCode,
                ];

                foreach ($this->records($provinceRecord['districts'] ?? null, 'distritos') as $districtRecord) {
                    [$districtCode, $districtName] = $this->location($districtRecord, 6, 'distrito', $provinceCode);

                    $this->assertNewCode($districts, $districtCode, 'distrito');

                    $districts[$districtCode] = [
                        'code' => $districtCode,
                        'name' => $districtName,
                        'province_code' => $provinceCode,
                    ];
                }
            }
        }

        if ($departments === [] || $provinces === [] || $districts === []) {
            throw new RuntimeException('El catálogo de ubigeos debe incluir departamentos, provincias y distritos.');
        }

        return [
            'departments' => array_values($departments),
            'provinces' => array_values($provinces),
            'districts' => array_values($districts),
        ];
    }

    /**
     * Verify that the catalog records where it comes from and when it was read.
     */
    private function assertSource(mixed $source): void
    {
        if (! is_array($source)) {
            throw new RuntimeException('El catálogo de ubigeos no registra su procedencia.');
        }

        foreach (['institution', 'service', 'catalog_version', 'consulted_at'] as $field) {
            if (! is_string($source[$field] ?? null) || trim($source[$field]) === '') {
                throw new RuntimeException("El catálogo de ubigeos no registra su procedencia: falta [source.{$field}].");
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function records(mixed $records, string $label): array
    {
        if (! is_array($records) || ! array_is_list($records)) {
            throw new RuntimeException("El catálogo de ubigeos no contiene una lista de {$label}.");
        }

        $validated = [];

        foreach ($records as $record) {
            if (! is_array($record)) {
                throw new RuntimeException("El catálogo de ubigeos contiene un registro inválido en {$label}.");
            }

            $validated[] = $record;
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{0: string, 1: string}
     */
    private function location(array $record, int $length, string $label, ?string $parentCode = null): array
    {
        $code = $record['code'] ?? null;
        $name = $record['name'] ?? null;

        if (! is_string($code) || preg_match('/^\d{'.$length.'}$/', $code) !== 1) {
            throw new RuntimeException("El catálogo de ubigeos contiene un {$label} con un código inválido.");
        }

        if ($parentCode !== null && ! str_starts_with($code, $parentCode)) {
            throw new RuntimeException("El código del {$label} [{$code}] no pertenece al nivel superior [{$parentCode}].");
        }

        if (! is_string($name) || trim($name) === '') {
            throw new RuntimeException("El catálogo de ubigeos contiene un {$label} [{$code}] sin nombre.");
        }

        return [$code, trim($name)];
    }

    /**
     * @param  array<string, array<string, string>>  $locations
     */
    private function assertNewCode(array $locations, string $code, string $label): void
    {
        if (array_key_exists($code, $locations)) {
            throw new RuntimeException("El catálogo de ubigeos repite el código de {$label} [{$code}].");
        }
    }
}
