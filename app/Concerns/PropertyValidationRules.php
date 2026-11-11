<?php

namespace App\Concerns;

use App\Enums\Currency;
use App\Enums\PropertyOperation;
use App\Enums\PropertyType;
use Illuminate\Validation\Rule;

trait PropertyValidationRules
{
    /**
     * Get the validation rules shared by publishing and editing properties.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function propertyRules(): array
    {
        return [
            'operation' => ['required', Rule::enum(PropertyOperation::class)],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'department_code' => ['required', 'string', Rule::exists('departments', 'code')],
            'province_code' => [
                'required',
                'string',
                Rule::exists('provinces', 'code')
                    ->where('department_code', $this->submittedLocationCode('department_code')),
            ],
            'district_code' => [
                'required',
                'string',
                Rule::exists('districts', 'code')
                    ->where('province_code', $this->submittedLocationCode('province_code')),
            ],
            'bedrooms' => ['required', 'integer', 'min:0'],
            'bathrooms' => ['required', 'integer', 'min:0'],
            'area' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function propertyMessages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'enum' => 'El valor seleccionado para :attribute no es válido.',
            'exists' => 'El valor seleccionado para :attribute no es válido.',
            'operation.enum' => 'El valor seleccionado para operación no es válido.',
            'type.enum' => 'El valor seleccionado para tipo no es válido.',
            'currency.enum' => 'El valor seleccionado para moneda no es válido.',
            'gt' => 'El campo :attribute debe ser mayor que cero.',
            'min' => 'El campo :attribute debe ser mayor o igual a :min.',
            'max' => 'El campo :attribute no debe superar :max caracteres.',
            'array' => 'El campo :attribute debe ser una lista.',
            'list' => 'El campo :attribute debe ser una lista ordenada.',
            'image' => 'Cada foto debe ser una imagen válida.',
            'mimes' => 'Cada foto debe ser JPG, PNG o WebP.',
        ];
    }

    /**
     * Read a submitted location code only when it is a single string value.
     *
     * The dependent levels are validated against the code submitted for their
     * parent, so an unexpected value is treated as an unmatched parent.
     */
    private function submittedLocationCode(string $field): string
    {
        $value = $this->input($field);

        return is_string($value) ? $value : '';
    }

    /**
     * @return array<string, string>
     */
    protected function propertyAttributes(): array
    {
        return [
            'operation' => 'operación',
            'type' => 'tipo',
            'title' => 'título',
            'description' => 'descripción',
            'price' => 'precio',
            'currency' => 'moneda',
            'department_code' => 'departamento',
            'province_code' => 'provincia',
            'district_code' => 'distrito',
            'bedrooms' => 'dormitorios',
            'bathrooms' => 'baños',
            'area' => 'área',
        ];
    }
}
