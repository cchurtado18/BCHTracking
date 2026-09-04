<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsolidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_type' => 'required|'.\App\Support\ServiceType::routeRule(),
            'transport_number' => 'required|string|max:80',
            'notes' => 'nullable|string|max:1000',
            'preregistration_ids' => 'nullable|array',
            'preregistration_ids.*' => 'integer|exists:preregistrations,id',
        ];
    }

    public function messages(): array
    {
        $label = \App\Support\ServiceType::transportNumberLabel($this->input('service_type'));

        return [
            'transport_number.required' => 'Indique el '.$label.'.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('transport_number')) {
            $this->merge([
                'transport_number' => strtoupper(trim((string) $this->input('transport_number'))),
            ]);
        }
    }
}
