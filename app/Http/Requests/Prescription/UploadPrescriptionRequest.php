<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Foundation\Http\FormRequest;

class UploadPrescriptionRequest extends FormRequest
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
            'file' => ['required_without:image_base64', 'file', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'max:4096'],
            'image_base64' => ['required_without:file', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
