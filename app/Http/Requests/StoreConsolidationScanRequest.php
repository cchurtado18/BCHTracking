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
            'service_type' => 'required|in:AIR,SEA',
            'notes' => 'nullable|string|max:1000',
            'entry_codes' => 'required|array|min:1',
            'entry_codes.*' => 'required|string|max:191',
        ];
    }
}
