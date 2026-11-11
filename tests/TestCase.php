<?php

namespace Tests;

use App\Models\District;
use Database\Seeders\UbigeoSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('districts')) {
            app(UbigeoSeeder::class)->run();
        }
    }

    /**
     * Build the three location codes the property form submits for a district
     * of the seeded catalog.
     *
     * @return array{department_code: string, province_code: string, district_code: string}
     */
    protected function propertyLocation(string $districtCode): array
    {
        $district = District::query()->with('province')->find($districtCode);

        if ($district === null) {
            throw new RuntimeException("The ubigeo catalog does not contain the district [{$districtCode}].");
        }

        return [
            'department_code' => $district->province->department_code,
            'province_code' => $district->province_code,
            'district_code' => $district->code,
        ];
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
