<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\MapsDriverDocuments;
use App\Http\Requests\Concerns\ValidatesDriverPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreZoneDriverRequest extends FormRequest
{
    use MapsDriverDocuments, ValidatesDriverPhone;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->baseDriverRules(true), [
            'driver-email' => 'required|email|unique:users,email',
            'driver-zone' => 'required|exists:zones,id',
            'service_areas' => 'nullable|array',
            'service_areas.*' => 'string|exists:zones,code',
            'partner_type' => 'nullable|in:independent,third-party',
            'agency_name' => 'nullable|string|max:150',
            'agency_id' => 'nullable|string|max:100',
        ]);
    }

    public function withValidator($validator): void
    {
        $this->attachPhoneValidation($validator);
    }

    /**
     * @return array<string, mixed>
     */
    public function driverPayload(): array
    {
        return array_merge($this->baseDriverPayload(), [
            'zone_id' => (int) $this->input('driver-zone'),
            'service_areas' => $this->input('service_areas', []),
            'partner_type' => $this->input('partner_type', 'independent'),
            'agency_name' => $this->input('agency_name'),
            'agency_id' => $this->input('agency_id'),
        ]);
    }
}
