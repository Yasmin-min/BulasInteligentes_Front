<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class StoreAllergyRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'allergen' => ['required', 'string', 'max:255'],
            'reaction' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'string', 'in:mild,moderate,severe,critical'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Prepare input values.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('allergen')) {
            $this->merge([
                'allergen' => trim((string) $this->input('allergen')),
            ]);
        }
    }
}
