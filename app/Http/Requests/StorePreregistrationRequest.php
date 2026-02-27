<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePreregistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'intake_type' => 'sometimes|in:COURIER,DROP_OFF',
            'agency_id' => [
                Rule::requiredIf(fn () => ! $this->isDropoffStepSubmission() || (int) $this->input('dropoff_step') === 1),
                'exists:agencies,id',
            ],
            'tracking_external' => [
                'required_if:intake_type,COURIER',
                'nullable',
                'string',
                'max:255',
                Rule::when($this->filled('tracking_external'), [Rule::unique('preregistrations', 'tracking_external')]),
            ],
            'service_type' => [
                Rule::requiredIf(fn () => ! $this->isDropoffStepSubmission() || (int) $this->input('dropoff_step') === 1),
                'in:AIR,SEA',
            ],
            'photo' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'bultos_count' => 'sometimes|integer|min:1|max:20',
            'dropoff_step' => 'sometimes|integer|min:1|max:20',
            'bultos' => 'sometimes|array',
            'bultos.*.label_name' => 'required_with:bultos|string|max:255',
            'bultos.*.intake_weight_lbs' => 'required_with:bultos|numeric|min:0.01|max:999999.99',
            'bultos.*.dimension' => 'required_with:bultos|string|max:100',
            'bultos.*.description' => 'nullable|string|max:500',
        ];
        // Drop off paso a paso: un solo bulto por envío
        if ($this->isDropoffStepSubmission()) {
            $rules['label_name'] = 'required|string|max:255';
            $rules['intake_weight_lbs'] = 'required|numeric|min:0.01|max:999999.99';
            $rules['dimension'] = 'required|string|max:100';
            $rules['description'] = 'nullable|string|max:500';
            $rules['photo'] = 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240';
            $rules['agency_id'] = 'required_if:dropoff_step,1|exists:agencies,id';
            $rules['service_type'] = 'required_if:dropoff_step,1|in:AIR,SEA';
            return $rules;
        }
        // Single bulto (or Courier): require label_name, weight, dimension when not multi-bulto
        if (!$this->isMultiBultoDropoff()) {
            $rules['label_name'] = 'required|string|max:255';
            $rules['intake_weight_lbs'] = 'required|numeric|min:0.01|max:999999.99';
            $rules['dimension'] = 'required_if:intake_type,DROP_OFF|nullable|string|max:100';
            $rules['description'] = 'nullable|string|max:500';
        } else {
            // Una foto por bulto
            $n = min((int) $this->input('bultos_count', 1), count($this->input('bultos', [])));
            for ($i = 0; $i < $n; $i++) {
                $rules['photo_bulto_' . $i] = 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240';
            }
        }
        return $rules;
    }

    /** True when submitting one drop-off bulto at a time (step-by-step flow). */
    public function isDropoffStepSubmission(): bool
    {
        $step = (int) $this->input('dropoff_step', 0);
        $total = (int) $this->input('bultos_count', 0);
        return $this->input('intake_type') === 'DROP_OFF' && $total > 1 && $step >= 1 && $step <= $total;
    }

    /** True when DROP_OFF with bultos_count > 1 and bultos array present (all at once). */
    public function isMultiBultoDropoff(): bool
    {
        if ($this->isDropoffStepSubmission()) {
            return false;
        }
        $n = (int) $this->input('bultos_count', 1);
        $bultos = $this->input('bultos', []);
        return $this->input('intake_type') === 'DROP_OFF' && $n > 1 && is_array($bultos) && count($bultos) >= $n;
    }

    public function messages(): array
    {
        $messages = [
            'photo.uploaded' => 'La foto no pudo subirse. Suele pasar si la imagen es muy pesada (máx. 10 MB) o la conexión es lenta. Pruebe con una foto más pequeña o acérquese al Wi‑Fi.',
            'photo.max' => 'La foto no puede superar 10 MB. Reduzca el tamaño o tome otra foto.',
            'photo.mimes' => 'La foto debe ser JPG, PNG o WEBP. Si usa iPhone, en Ajustes > Cámara puede elegir "Formatos más compatibles".',
            'tracking_external.unique' => 'Este tracking ya está registrado en otro paquete. Use otro número o elimine el paquete que lo tiene.',
        ];
        if ($this->isMultiBultoDropoff()) {
            $n = min((int) $this->input('bultos_count', 1), count($this->input('bultos', [])));
            for ($i = 0; $i < $n; $i++) {
                $key = 'photo_bulto_' . $i;
                $messages[$key . '.required'] = 'La foto del bulto ' . ($i + 1) . ' es obligatoria.';
                $messages[$key . '.max'] = 'La foto del bulto ' . ($i + 1) . ' no puede superar 10 MB.';
                $messages[$key . '.mimes'] = 'La foto del bulto ' . ($i + 1) . ' debe ser JPG, PNG o WEBP.';
            }
        }
        return $messages;
    }
}
