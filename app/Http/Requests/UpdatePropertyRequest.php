<?php

namespace App\Http\Requests;

use App\Concerns\PropertyValidationRules;
use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePropertyRequest extends FormRequest
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
        $routeProperty = $this->route('property');
        $propertyId = $routeProperty instanceof Property
            ? $routeProperty->id
            : (is_string($routeProperty) ? $routeProperty : null);

        return [
            ...$this->propertyRules(),
            'photo_order' => ['nullable', 'array', 'list', 'max:8'],
            'photo_order.*' => [
                'integer',
                'distinct',
                Rule::exists('property_photos', 'id')->where('property_id', $propertyId),
            ],
            'photos' => ['nullable', 'array', 'list', 'max:8'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var array<int, mixed>|null $photoOrder */
            $photoOrder = $this->input('photo_order', []);
            /** @var array<int, mixed>|null $photos */
            $photos = $this->file('photos', $this->input('photos'));

            $total = count(is_array($photoOrder) ? $photoOrder : []) + count(is_array($photos) ? $photos : []);

            if ($total < 1) {
                $validator->errors()->add('photos', 'Debes adjuntar al menos una foto.');
            }

            if ($total > 8) {
                $validator->errors()->add('photos', 'Puedes adjuntar como máximo 8 fotos.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->propertyMessages(),
            'photos.max' => 'Puedes adjuntar como máximo 8 fotos.',
            'photos.*.max' => 'Cada foto debe pesar como máximo 5 MB.',
            'photo_order.max' => 'Puedes adjuntar como máximo 8 fotos.',
            'photo_order.*.distinct' => 'Cada foto debe aparecer una sola vez.',
            'photo_order.*.exists' => 'Una de las fotos seleccionadas no es válida.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...$this->propertyAttributes(),
            'photo_order' => 'fotos',
            'photo_order.*' => 'foto',
            'photos' => 'fotos',
            'photos.*' => 'foto',
        ];
    }
}
