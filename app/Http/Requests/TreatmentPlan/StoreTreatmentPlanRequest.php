<?php

namespace App\Http\Requests\TreatmentPlan;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreatmentPlanRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'source' => ['nullable', 'string', 'in:manual,ocr,imported'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_id' => ['nullable', 'integer', 'exists:medications,id'],
            'items.*.medication_name' => ['required_without:items.*.medication_id', 'string', 'max:255'],
            'items.*.dosage' => ['nullable', 'string', 'max:255'],
            'items.*.route' => ['nullable', 'string', 'max:255'],
            'items.*.instructions' => ['nullable', 'string'],
            'items.*.first_dose_at' => ['nullable', 'date'],
            'items.*.interval_minutes' => ['nullable', 'integer', 'min:30'],
            'items.*.total_doses' => ['nullable', 'integer', 'min:1'],
            'items.*.duration_days' => ['nullable', 'integer', 'min:1'],
            'items.*.specific_times' => ['nullable', 'array', 'min:1'],
            'items.*.specific_times.*' => ['regex:/^([01]\\d|2[0-3]):[0-5]\\d$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('title')) {
            $this->merge([
                'title' => trim((string) $this->input('title')),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                $hasInterval = ! empty($item['interval_minutes']);
                $hasSpecificTimes = ! empty($item['specific_times']);

                if (! $hasInterval && ! $hasSpecificTimes) {
                    $validator->errors()->add("items.{$index}.interval_minutes", 'Informe interval_minutes ou specific_times.');
                }
            }
        });
    }
}
