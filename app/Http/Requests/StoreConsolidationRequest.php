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
            'service_type' => 'required|in:AIR,SEA',
            'notes' => 'nullable|string|max:1000',
            'preregistration_ids' => 'nullable|array',
            'preregistration_ids.*' => 'integer|exists:preregistrations,id',
        ];
    }
}
