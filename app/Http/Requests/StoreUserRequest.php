<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user-first-name'   => 'required|string|max:100',
            'user-last-name'    => 'nullable|string|max:100',
            'user-email'        => 'required|email|unique:users,email',
            'user-phone'        => 'required|digits:10|unique:users,mobile',
            'user-dob'          => 'nullable|date',
            'user-gender'       => 'nullable|in:Male,Female,Other',
            'user-address'      => 'nullable|string|max:255',
            'user-role'         => 'required|exists:roles,id',
            'user-store'        => 'nullable|string',
            'user-avatar-file'  => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'password'          => 'required|string|min:8|confirmed',
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes(
            'user-store',
            'required',
            function ($input) {
                return strtolower($input->{'user-role'}) === 'store admin';
            }
        );
    }
}
