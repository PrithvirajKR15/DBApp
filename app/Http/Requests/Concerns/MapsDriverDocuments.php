<?php

namespace App\Http\Requests\Concerns;

trait MapsDriverDocuments
{
    /**
     * @return array<string, \Illuminate\Http\UploadedFile|null>
     */
    public function documentFiles(): array
    {
        return [
            'aadhaar_front' => $this->file('driver-aadhaar-front'),
            'aadhaar_back' => $this->file('driver-aadhaar-back'),
            'dl_front' => $this->file('driver-dl-front'),
            'dl_back' => $this->file('driver-dl-back'),
            'pan_card' => $this->file('driver-pan-card'),
            'vehicle_rc' => $this->file('driver-vehicle-rc'),
            'vehicle_insurance' => $this->file('driver-vehicle-insurance'),
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function baseDriverRules(bool $documentsRequired = true): array
    {
        $documentRule = $documentsRequired ? 'required' : 'nullable';

        return [
            'driver-first-name' => 'required|string|max:100',
            'driver-last-name' => 'nullable|string|max:100',
            'driver-email' => 'required|email',
            'driver-phone' => 'required|digits:10',
            'driver-dob' => 'nullable|date',
            'driver-gender' => 'nullable|in:Male,Female,Other',
            'driver-address' => 'nullable|string|max:500',
            'driver-shift' => 'required|string|max:100',
            'driver-status' => 'required|in:Pending,Active,Suspended',
            'driver-availability' => 'nullable|in:Online,Offline',
            'working_days' => 'nullable|array',
            'working_days.*' => 'string|in:Mon,Tue,Wed,Thu,Fri,Sat,Sun',
            'driver-plate-number' => 'required|string|max:50',
            'driver-vehicle-brand' => 'required|string|max:100',
            'driver-vehicle-model' => 'required|string|max:100',
            'driver-vehicle-type' => 'nullable|string|max:50',
            'driver-vehicle-fuel' => 'nullable|string|max:50',
            'driver-license-number' => 'required|string|max:100',
            'driver-avatar-file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'driver-aadhaar-front' => "{$documentRule}|file|mimes:jpg,jpeg,png,pdf|max:5120",
            'driver-aadhaar-back' => "{$documentRule}|file|mimes:jpg,jpeg,png,pdf|max:5120",
            'driver-dl-front' => "{$documentRule}|file|mimes:jpg,jpeg,png,pdf|max:5120",
            'driver-dl-back' => "{$documentRule}|file|mimes:jpg,jpeg,png,pdf|max:5120",
            'driver-pan-card' => "{$documentRule}|file|mimes:jpg,jpeg,png,pdf|max:5120",
            'driver-vehicle-rc' => "{$documentRule}|file|mimes:jpg,jpeg,png,pdf|max:5120",
            'driver-vehicle-insurance' => "{$documentRule}|file|mimes:jpg,jpeg,png,pdf|max:5120",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function baseDriverPayload(): array
    {
        return [
            'first_name' => $this->input('driver-first-name'),
            'last_name' => $this->input('driver-last-name'),
            'email' => $this->input('driver-email'),
            'mobile' => $this->input('driver-phone'),
            'dob' => $this->input('driver-dob'),
            'gender' => $this->input('driver-gender'),
            'address' => $this->input('driver-address'),
            'shift' => $this->input('driver-shift'),
            'status' => $this->input('driver-status'),
            'availability' => $this->input('driver-availability', 'Offline'),
            'working_days' => $this->input('working_days', []),
            'vehicle_type' => $this->input('driver-vehicle-type'),
            'vehicle_brand' => $this->input('driver-vehicle-brand'),
            'vehicle_model' => $this->input('driver-vehicle-model'),
            'plate_number' => $this->input('driver-plate-number'),
            'vehicle_fuel' => $this->input('driver-vehicle-fuel'),
            'license_number' => $this->input('driver-license-number'),
        ];
    }
}
