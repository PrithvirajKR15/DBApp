<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreZoneDriverRequest;
use App\Http\Requests\UpdateZoneDriverRequest;
use App\Models\Zone;
use App\Services\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoneDriverController extends Controller
{
    public function __construct(
        protected DriverService $driverService
    ) {}

    public function index()
    {
        $zones = Zone::orderBy('name')->get(['id', 'code', 'name']);

        return view('content.pages.drivers', [
            'driverType' => 'zone',
            'zones' => $zones,
        ]);
    }

    public function list(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'drivers' => $this->driverService->listZoneDrivers()->values(),
        ]);
    }

    public function store(StoreZoneDriverRequest $request): JsonResponse
    {
        try {
            $driver = $this->driverService->createZoneDriver(
                $request->driverPayload(),
                $request->file('driver-avatar-file'),
                $request->documentFiles()
            );

            return response()->json([
                'status' => true,
                'message' => 'Zone driver created successfully.',
                'driver' => $this->driverService->shapeZoneDriver($driver),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateZoneDriverRequest $request, string $code): JsonResponse
    {
        try {
            $driver = $this->driverService->updateZoneDriver(
                $code,
                $request->driverPayload(),
                $request->file('driver-avatar-file'),
                $request->documentFiles()
            );

            return response()->json([
                'status' => true,
                'message' => 'Zone driver updated successfully.',
                'driver' => $this->driverService->shapeZoneDriver($driver),
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
            $this->driverService->deleteZoneDriver($code);

            return response()->json([
                'status' => true,
                'message' => 'Zone driver deleted successfully.',
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
            $driver = $this->driverService->findZoneDriverByCode($code);

            if ($request->filled('status')) {
                $driver->user->update(['status' => $request->input('status')]);
            }

            if ($request->filled('availability')) {
                $driver->update(['availability' => $request->input('availability')]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Driver status updated successfully.',
                'driver' => $this->driverService->shapeZoneDriver(
                    $driver->fresh(['user', 'activeAssignment.zone', 'orders'])
                ),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
