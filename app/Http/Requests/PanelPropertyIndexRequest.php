<?php

namespace App\Http\Requests;

use App\Enums\Currency;
use App\Enums\PropertyOperation;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\District;
use App\Models\Province;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\Attributes\RedirectToRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

#[RedirectToRoute('panel.properties.index')]
class PanelPropertyIndexRequest extends FormRequest
{
    /**
     * Values the panel controls use to mean "no filter".
     *
     * @var list<string>
     */
    private const SENTINEL_VALUES = ['all', 'any'];

    /**
     * @var list<string>
     */
    private const PRICE_SORTS = ['price-asc', 'price-desc'];

    private const PRICE_RANGE_ERROR = 'El precio mínimo no puede ser mayor que el precio máximo.';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Remove the "all" and "any" values before validating, so the boundary only
     * accepts real filter values.
     */
    protected function prepareForValidation(): void
    {
        $query = $this->query();

        foreach (['status', 'operation', 'type', 'currency', 'bedrooms', 'department_code', 'province_code', 'district_code'] as $key) {
            if (in_array($query[$key] ?? null, self::SENTINEL_VALUES, true)) {
                unset($query[$key]);
            }
        }

        if (is_string($query['search'] ?? null) && trim($query['search']) === '') {
            unset($query['search']);
        }

        $this->query->replace($query);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(PropertyStatus::class)->except(PropertyStatus::Archived)],
            'operation' => ['nullable', Rule::enum(PropertyOperation::class)],
            'type' => ['nullable', Rule::enum(PropertyType::class)],
            'department_code' => ['nullable', 'string', Rule::exists('departments', 'code')],
            'province_code' => ['nullable', 'string', Rule::exists('provinces', 'code')],
            'district_code' => ['nullable', 'string', Rule::exists('districts', 'code')],
            'currency' => ['nullable', Rule::enum(Currency::class)],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', Rule::in(['recent', 'title', 'price-asc', 'price-desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no debe superar :max caracteres.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'min' => 'El campo :attribute debe ser mayor o igual a :min.',
            'enum' => 'El valor seleccionado para :attribute no es válido.',
            'status.enum' => 'El valor seleccionado para estado no es válido.',
            'operation.enum' => 'El valor seleccionado para operación no es válido.',
            'type.enum' => 'El valor seleccionado para tipo no es válido.',
            'currency.enum' => 'El valor seleccionado para moneda no es válido.',
            'exists' => 'El valor seleccionado para :attribute no es válido.',
            'in' => 'El valor seleccionado para :attribute no es válido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'search' => 'búsqueda',
            'status' => 'estado',
            'operation' => 'operación',
            'type' => 'tipo',
            'department_code' => 'departamento',
            'province_code' => 'provincia',
            'district_code' => 'distrito',
            'currency' => 'moneda',
            'min_price' => 'precio mínimo',
            'max_price' => 'precio máximo',
            'bedrooms' => 'dormitorios',
            'sort' => 'orden',
            'page' => 'página',
        ];
    }

    /**
     * Get the filters to apply, normalized to the values the query understands.
     *
     * @return array{
     *     search: string|null,
     *     status: string|null,
     *     operation: string|null,
     *     type: string|null,
     *     department_code: string|null,
     *     province_code: string|null,
     *     district_code: string|null,
     *     currency: string|null,
     *     min_price: string|null,
     *     max_price: string|null,
     *     bedrooms: int|null,
     *     sort: string
     * }
     */
    public function filters(): array
    {
        $currency = $this->nullableString('currency');
        $rangeIsUsable = $currency !== null && ! $this->hasInvalidPriceRange();
        $sort = $this->nullableString('sort') ?? 'recent';

        if ($currency === null && in_array($sort, self::PRICE_SORTS, true)) {
            $sort = 'recent';
        }

        $location = $this->locationFilters();

        return [
            'search' => $this->normalizedSearch(),
            'status' => $this->nullableString('status'),
            'operation' => $this->nullableString('operation'),
            'type' => $this->nullableString('type'),
            'department_code' => $location['department'],
            'province_code' => $location['province'],
            'district_code' => $location['district'],
            'currency' => $currency,
            'min_price' => $rangeIsUsable ? $this->nullableString('min_price') : null,
            'max_price' => $rangeIsUsable ? $this->nullableString('max_price') : null,
            'bedrooms' => $this->nullableInteger('bedrooms'),
            'sort' => $sort,
        ];
    }

    /**
     * Get the message for a price range that must not be applied.
     */
    public function priceRangeError(): ?string
    {
        return $this->nullableString('currency') !== null && $this->hasInvalidPriceRange()
            ? self::PRICE_RANGE_ERROR
            : null;
    }

    /**
     * Drop the location levels that do not belong to the level above them, so a
     * shared URL never combines a province with another department or a district
     * with another province.
     *
     * @return array{department: string|null, province: string|null, district: string|null}
     */
    private function locationFilters(): array
    {
        $department = $this->nullableString('department_code');
        $province = $this->nullableString('province_code');
        $district = $this->nullableString('district_code');

        if ($department !== null && $province !== null
            && Province::query()->whereKey($province)->value('department_code') !== $department) {
            $province = null;
        }

        if ($district !== null) {
            $selected = District::query()->with('province')->find($district, ['code', 'province_code']);
            $fitsProvince = $province === null || $selected?->province_code === $province;
            $fitsDepartment = $department === null || $selected?->province->department_code === $department;

            if (! $fitsProvince || ! $fitsDepartment) {
                $district = null;
            }
        }

        return ['department' => $department, 'province' => $province, 'district' => $district];
    }

    private function hasInvalidPriceRange(): bool
    {
        $minPrice = $this->nullableNumber('min_price');
        $maxPrice = $this->nullableNumber('max_price');

        return $minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice;
    }

    private function normalizedSearch(): ?string
    {
        $search = $this->nullableString('search');
        $search = $search === null ? null : trim($search);

        return $search === '' ? null : $search;
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->validated($key);

        return $value === null ? null : (string) $value;
    }

    private function nullableNumber(string $key): ?float
    {
        $value = $this->validated($key);

        return $value === null ? null : (float) $value;
    }

    private function nullableInteger(string $key): ?int
    {
        $value = $this->validated($key);

        return $value === null ? null : (int) $value;
    }
}
