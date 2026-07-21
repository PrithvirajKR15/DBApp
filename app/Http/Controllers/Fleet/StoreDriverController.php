<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStoreDriverRequest;
use App\Http\Requests\UpdateStoreDriverRequest;
use App\Models\Store;
use App\Services\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreDriverController extends Controller
{
    public function __construct(
        protected DriverService $driverService
    ) {}

    public function index()
    {
        $drivers = $this->driverService->listStoreDrivers()->values()->all();
        $stores = Store::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('content.pages.drivers', [
            'driverType' => 'store',
            'drivers' => $drivers,
            'stores' => $stores,
            'useDatabase' => true,
        ]);
    }

    public function list(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'drivers' => $this->driverService->listStoreDrivers()->values(),
        ]);
    }

    public function store(StoreStoreDriverRequest $request): JsonResponse
    {
        try {
            $driver = $this->driverService->createStoreDriver(
                $request->driverPayload(),
                $request->file('driver-avatar-file'),
                $request->documentFiles()
            );

            return response()->json([
                'status' => true,
                'message' => 'Store driver created successfully.',
                'driver' => $this->driverService->shapeStoreDriver($driver),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateStoreDriverRequest $request, string $code): JsonResponse
    {
        try {
            $driver = $this->driverService->updateStoreDriver(
                $code,
                $request->driverPayload(),
                $request->file('driver-avatar-file'),
                $request->documentFiles()
            );

            return response()->json([
                'status' => true,
                'message' => 'Store driver updated successfully.',
                'driver' => $this->driverService->shapeStoreDriver($driver),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $code): JsonResponse
    {
        try {
            $this->driverService->deleteStoreDriver($code);

            return response()->json([
                'status' => true,
                'message' => 'Store driver deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:Pending,Active,Suspended',
            'availability' => 'nullable|in:Online,Offline',
        ]);

        if (! $request->filled('status') && ! $request->filled('availability')) {
            return response()->json([
                'status' => false,
                'message' => 'Provide status and/or availability.',
            ], 422);
        }

        try {
            $driver = $this->driverService->findStoreDriverByCode($code);

            if ($request->filled('status')) {
                $driver->user->update(['status' => $request->input('status')]);
            }

            if ($request->filled('availability')) {
                $driver->update(['availability' => $request->input('availability')]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Driver status updated successfully.',
                'driver' => $this->driverService->shapeStoreDriver($driver->fresh(['user', 'activeAssignment.store', 'orders'])),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
