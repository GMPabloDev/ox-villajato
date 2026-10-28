<?php

namespace App\Http\Requests;

use App\Concerns\NormalizesEmail;
use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgentRequest extends FormRequest
{
    use NormalizesEmail, ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->normalizeEmail();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->profileRules();
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no debe superar 255 caracteres.',
            'email.required' => 'El correo es obligatorio.',
            'email.string' => 'El correo debe ser texto.',
            'email.email' => 'El correo no es válido.',
            'email.max' => 'El correo no debe superar 255 caracteres.',
            'email.unique' => 'Ya existe una cuenta con este correo.',
        ];
    }
}
