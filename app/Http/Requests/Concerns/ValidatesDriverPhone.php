<?php

namespace App\Http\Requests\Concerns;

use App\Models\User;

trait ValidatesDriverPhone
{
    protected function mobileExists(string $phone, ?int $ignoreUserId = null): bool
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

    protected function attachPhoneValidation($validator, ?int $ignoreUserId = null): void
    {
        $validator->after(function ($validator) use ($ignoreUserId) {
            $phone = $this->input('driver-phone');

            if ($phone && $this->mobileExists($phone, $ignoreUserId)) {
                $validator->errors()->add('driver-phone', 'This phone number is already registered.');
            }
        });
    }
}
