<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreZoneDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'driver-first-name' => 'required|string|max:100',
            'driver-last-name' => 'nullable|string|max:100',
            'driver-email' => 'required|email|unique:users,email',
            'driver-phone' => 'required|digits:10',
            'driver-dob' => 'nullable|date',
            'driver-gender' => 'nullable|in:Male,Female,Other',
            'driver-address' => 'nullable|string|max:500',
            'driver-zone' => 'required|exists:zones,id',
            'service_areas' => 'nullable|array',
            'service_areas.*' => 'string|exists:zones,code',
            'partner_type' => 'nullable|in:independent,third-party',
            'agency_name' => 'nullable|string|max:150',
            'agency_id' => 'nullable|string|max:100',
            'driver-shift' => 'required|string|max:100',
            'driver-status' => 'required|in:Pending,Active,Rejected,Suspended',
            'driver-availability' => 'nullable|in:Online,Offline,Transit',
            'working_days' => 'nullable|array',
            'working_days.*' => 'string|in:Mon,Tue,Wed,Thu,Fri,Sat,Sun',
            'driver-plate-number' => 'required|string|max:50',
            'driver-vehicle-brand' => 'required|string|max:100',
            'driver-vehicle-model' => 'required|string|max:100',
            'driver-vehicle-type' => 'nullable|string|max:50',
            'driver-vehicle-fuel' => 'nullable|string|max:50',
            'driver-license-number' => 'required|string|max:100',
            'driver-avatar-file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'driver-aadhaar-front' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'driver-aadhaar-back' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'driver-dl-front' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'driver-dl-back' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'driver-pan-card' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'driver-vehicle-rc' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'driver-vehicle-insurance' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $phone = $this->input('driver-phone');

            if ($phone && $this->mobileExists($phone)) {
                $validator->errors()->add('driver-phone', 'This phone number is already registered.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function driverPayload(): array
    {
        return [
            'first_name' => $this->input('driver-first-name'),
            'last_name' => $this->input('driver-last-name'),
            'email' => $this->input('driver-email'),
            'mobile' => $this->input('driver-phone'),
            'dob' => $this->input('driver-dob'),
            'gender' => $this->input('driver-gender'),
            'address' => $this->input('driver-address'),
            'zone_id' => (int) $this->input('driver-zone'),
            'service_areas' => $this->input('service_areas', []),
            'partner_type' => $this->input('partner_type', 'independent'),
            'agency_name' => $this->input('agency_name'),
            'agency_id' => $this->input('agency_id'),
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

    private function mobileExists(string $phone, ?int $ignoreUserId = null): bool
    {
        $formatted = '+91 ' . substr($phone, 0, 5) . ' ' . substr($phone, 5);

        $query = User::query()->where(function ($q) use ($phone, $formatted) {
            $q->where('mobile', $phone)
                ->orWhere('mobile', $formatted)
                ->orWhere('mobile', '+91' . $phone);
        });

        if ($ignoreUserId) {
            $query->where('id', '!=', $ignoreUserId);
        }

        return $query->exists();
    }
}
