<?php

namespace App\Http\Requests;

use App\Enums\Currency;
use App\Enums\PropertyOperation;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
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

        foreach (['status', 'operation', 'type', 'currency', 'bedrooms'] as $key) {
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
            'city' => ['nullable', 'string', 'max:255'],
            'zone' => ['nullable', 'string', 'max:255'],
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
            'city' => 'ciudad',
            'zone' => 'zona',
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
     *     city: string|null,
     *     zone: string|null,
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

        return [
            'search' => $this->normalizedSearch(),
            'status' => $this->nullableString('status'),
            'operation' => $this->nullableString('operation'),
            'type' => $this->nullableString('type'),
            'city' => $this->nullableString('city'),
            'zone' => $this->nullableString('zone'),
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
