<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\Attributes\RedirectToRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

#[RedirectToRoute('panel.inquiries.index')]
class PanelInquiryIndexRequest extends FormRequest
{
    /**
     * Values the panel controls use to mean "no filter".
     *
     * @var list<string>
     */
    private const SENTINEL_VALUES = ['all', 'any'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Remove the control values that mean "no filter" before validating, so
     * the boundary only accepts real filter values.
     */
    protected function prepareForValidation(): void
    {
        $query = $this->query();

        if (in_array($query['property'] ?? null, self::SENTINEL_VALUES, true)) {
            unset($query['property']);
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
            'property' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', Rule::in(['recent', 'oldest'])],
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
            'integer' => 'El campo :attribute debe ser un número entero.',
            'min' => 'El campo :attribute debe ser mayor o igual a :min.',
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
            'property' => 'propiedad',
            'sort' => 'orden',
            'page' => 'página',
        ];
    }

    /**
     * Get the filters to apply, normalized to the values the query understands.
     *
     * @return array{search: string|null, property: int|null, sort: string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->normalizedSearch(),
            'property' => $this->nullableInteger('property'),
            'sort' => $this->nullableString('sort') ?? 'recent',
        ];
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

    private function nullableInteger(string $key): ?int
    {
        $value = $this->validated($key);

        return $value === null ? null : (int) $value;
    }
}
