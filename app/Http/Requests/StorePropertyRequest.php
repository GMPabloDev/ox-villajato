<?php

namespace App\Http\Requests;

use App\Enums\Currency;
use App\Enums\PropertyOperation;
use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'operation' => ['required', Rule::enum(PropertyOperation::class)],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'city' => ['required', 'string', 'max:255'],
            'zone' => ['required', 'string', 'max:255'],
            'bedrooms' => ['required', 'integer', 'min:0'],
            'bathrooms' => ['required', 'integer', 'min:0'],
            'area' => ['required', 'numeric', 'gt:0'],
            'photos' => ['required', 'array', 'min:1', 'max:8'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'enum' => 'El valor seleccionado para :attribute no es válido.',
            'operation.enum' => 'El valor seleccionado para operación no es válido.',
            'type.enum' => 'El valor seleccionado para tipo no es válido.',
            'currency.enum' => 'El valor seleccionado para moneda no es válido.',
            'gt' => 'El campo :attribute debe ser mayor que cero.',
            'min' => 'El campo :attribute debe ser mayor o igual a :min.',
            'max' => 'El campo :attribute no debe superar :max caracteres.',
            'array' => 'El campo :attribute debe ser una lista.',
            'image' => 'Cada foto debe ser una imagen válida.',
            'mimes' => 'Cada foto debe ser JPG, PNG o WebP.',
            'photos.min' => 'Debes adjuntar al menos una foto.',
            'photos.max' => 'Puedes adjuntar como máximo 8 fotos.',
            'photos.*.max' => 'Cada foto debe pesar como máximo 5 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'operation' => 'operación',
            'type' => 'tipo',
            'title' => 'título',
            'description' => 'descripción',
            'price' => 'precio',
            'currency' => 'moneda',
            'city' => 'ciudad',
            'zone' => 'zona',
            'bedrooms' => 'dormitorios',
            'bathrooms' => 'baños',
            'area' => 'área',
            'photos' => 'fotos',
            'photos.*' => 'foto',
        ];
    }
}
