<?php

namespace App\Http\Requests\TreatmentPlan;

use Illuminate\Foundation\Http\FormRequest;

class RecordDoseRequest extends FormRequest
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
            'taken_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:taken,skipped,rescheduled'],
            'notes' => ['nullable', 'string'],
            'reschedule_to' => ['nullable', 'date', 'after:now'],
        ];
    }
}
