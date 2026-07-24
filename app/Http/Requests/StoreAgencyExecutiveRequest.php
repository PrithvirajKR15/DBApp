<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgencyExecutiveRequest extends FormRequest
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
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'mobile' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6|max:100',
            'address' => 'nullable|string|max:500',
            'branch_ids' => 'required|array|min:1',
            'branch_ids.*' => 'integer|exists:agency_branches,id',
        ];
    }
}
