<?php

namespace App\Http\Requests\TreatmentPlan;

use Illuminate\Foundation\Http\FormRequest;

class SuggestTreatmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'max:2000'],
            'start_at' => ['nullable', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('summary')) {
            $this->merge([
                'summary' => trim((string) $this->input('summary')),
            ]);
        }
    }
}
