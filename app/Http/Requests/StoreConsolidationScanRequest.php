<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsolidationScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_type' => 'required|'.\App\Support\ServiceType::routeRule(),
            'transport_number' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:1000',
            'entry_codes' => 'required|array|min:1',
            'entry_codes.*' => 'required|string|max:191',
        ];
    }

    protected function prepareForValidation(): void
    {
        $value = strtoupper(trim((string) $this->input('transport_number')));
        $this->merge([
            'transport_number' => $value !== '' ? $value : null,
        ]);
    }
}
