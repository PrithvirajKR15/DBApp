<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgencyBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isStoreAdmin();
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('zone_ids') && $this->filled('zone_id')) {
            $this->merge([
                'zone_ids' => [(int) $this->input('zone_id')],
            ]);
        }

        if (! $this->filled('name') && $this->filled('name_prefix')) {
            $this->merge(['name' => $this->input('name_prefix')]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'zone_ids' => 'required|array|min:1',
            'zone_ids.*' => 'integer|exists:zones,id',
            'name' => 'required|string|max:150',
            'name_prefix' => 'nullable|string|max:100',
            'cost_per_km' => 'required|numeric|min:0',
            'minimum_order_charge' => 'required|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}
