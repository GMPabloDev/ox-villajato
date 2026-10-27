<?php

namespace App\Http\Requests;

use App\Concerns\PropertyValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    use PropertyValidationRules;

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
            ...$this->propertyRules(),
            'photos' => ['required', 'array', 'list', 'min:1', 'max:8'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->propertyMessages(),
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
            ...$this->propertyAttributes(),
            'photos' => 'fotos',
            'photos.*' => 'foto',
        ];
    }
}
