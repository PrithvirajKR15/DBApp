<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverPageController extends Controller
{
    public function __construct(
        protected DriverService $driverService
    ) {}

    public function profile(string $id)
    {
        $driver = $this->driverService->shapeDriver(
            $this->driverService->findDriverByCode($id)
        );

        return view('content.pages.driver-profile', [
            'driver' => $driver,
            'driverId' => $id,
        ]);
    }

    public function review(string $id)
    {
        $driver = $this->driverService->shapeDriver(
            $this->driverService->findDriverByCode($id)
        );

        return view('content.pages.driver-review', [
            'driver' => $driver,
            'driverId' => $id,
        ]);
    }

    public function updateProfile(Request $request, string $id): JsonResponse
    {
        $userId = User::where('code', $id)->value('id');

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $phoneDigits = preg_replace('/\D/', '', $validated['phone']) ?? '';
        if (str_starts_with($phoneDigits, '91') && strlen($phoneDigits) === 12) {
            $phoneDigits = substr($phoneDigits, 2);
        }

        if (strlen($phoneDigits) !== 10) {
            return response()->json([
                'status' => false,
                'message' => 'Phone number must be 10 digits.',
                'errors' => ['phone' => ['Phone number must be 10 digits.']],
            ], 422);
        }

        if ($this->mobileExists($phoneDigits, $userId)) {
            return response()->json([
                'status' => false,
                'message' => 'This phone number is already registered.',
                'errors' => ['phone' => ['This phone number is already registered.']],
            ], 422);
        }

        try {
            $driver = $this->driverService->updateDriverPersonalInfo($id, [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'mobile' => $phoneDigits,
                'address' => $validated['address'] ?? null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Driver profile updated successfully.',
                'driver' => $this->driverService->shapeDriver($driver),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:Pending,Active,Rejected,Suspended',
            'availability' => 'nullable|in:Online,Offline,Transit',
        ]);

        if (! $request->filled('status') && ! $request->filled('availability')) {
            return response()->json([
                'status' => false,
                'message' => 'Provide status and/or availability.',
            ], 422);
        }

        try {
            $driver = $this->driverService->updateDriverAccountStatus($id, array_filter([
                'status' => $request->input('status'),
                'availability' => $request->input('availability'),
            ], fn ($value) => $value !== null));

            return response()->json([
                'status' => true,
                'message' => 'Driver status updated successfully.',
                'driver' => $this->driverService->shapeDriver($driver),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
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
