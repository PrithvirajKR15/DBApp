<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgencyBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isStoreAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'zone_ids' => 'sometimes|array|min:1',
            'zone_ids.*' => 'integer|exists:zones,id',
            'name' => 'sometimes|required|string|max:150',
            'cost_per_km' => 'sometimes|required|numeric|min:0',
            'minimum_order_charge' => 'sometimes|required|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}
