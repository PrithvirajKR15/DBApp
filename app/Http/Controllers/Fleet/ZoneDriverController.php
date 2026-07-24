<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreZoneDriverRequest;
use App\Http\Requests\UpdateZoneDriverRequest;
use App\Models\AgencyBranch;
use App\Models\Zone;
use App\Services\AgencyService;
use App\Services\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ZoneDriverController extends Controller
{
    public function __construct(
        protected DriverService $driverService,
        protected AgencyService $agencyService
    ) {}

    public function index()
    {
        $user = auth()->user();
        $zones = $this->agencyService->zonesForUser($user);
        if ($user->isAdmin()) {
            $zones = Zone::orderBy('name')->get(['id', 'code', 'name']);
        }

        return view('content.pages.drivers', [
            'driverType' => 'zone',
            'zones' => $zones,
            'isExecutiveContext' => $user->isExecutive(),
            'agencyBranches' => $this->agencyService->branchesForDriverForm($user),
            'listUrl' => $user->isExecutive() ? route('executive-drivers.list') : route('fleet-drivers-zone.list'),
            'createUrl' => $user->isExecutive() ? route('executive-drivers.store') : route('fleet-drivers-zone.store'),
            'updateUrlTemplate' => $user->isExecutive()
                ? url('/executive/drivers/__CODE__/update')
                : url('/fleet/drivers/zone/__CODE__/update'),
            'deleteUrlTemplate' => $user->isExecutive()
                ? url('/executive/drivers/__CODE__')
                : url('/fleet/drivers/zone/__CODE__'),
            'statusUrlTemplate' => $user->isExecutive()
                ? url('/executive/drivers/__CODE__/status')
                : url('/fleet/drivers/zone/__CODE__/status'),
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
            $this->assertExecutiveMayUsePayload($request->user(), $request->driverPayload());

            $driver = $this->driverService->createZoneDriver(
                $request->driverPayload(),
                $request->file('driver-avatar-file'),
                $request->documentFiles()
            );

            return response()->json([
                'status' => true,
                'message' => 'Zone driver created successfully.',
                'driver' => $this->driverService->shapeZoneDriver($driver->load(['agencyBranch.agency'])),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
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
            $this->assertExecutiveMayUsePayload($request->user(), $request->driverPayload());
            $this->assertExecutiveOwnsDriver($request->user(), $code);

            $driver = $this->driverService->updateZoneDriver(
                $code,
                $request->driverPayload(),
                $request->file('driver-avatar-file'),
                $request->documentFiles()
            );

            return response()->json([
                'status' => true,
                'message' => 'Zone driver updated successfully.',
                'driver' => $this->driverService->shapeZoneDriver($driver->load(['agencyBranch.agency'])),
            ]);
        } catch (ValidationException $e) {
            throw $e;
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
            $this->assertExecutiveOwnsDriver(auth()->user(), $code);
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
            $this->assertExecutiveOwnsDriver($request->user(), $code);
            $driver = $this->driverService->findZoneDriverByCode($code);

            if ($request->filled('status')) {
                $driver->user->update([
                    'status' => $this->driverService->normalizeAccountStatus($request->input('status')),
                ]);
            }

            if ($request->filled('availability')) {
                $driver->update(['availability' => $request->input('availability')]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Driver status updated successfully.',
                'driver' => $this->driverService->shapeZoneDriver(
                    $driver->fresh(['user', 'activeAssignment.zone', 'agencyBranch.agency', 'orders'])
                ),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertExecutiveMayUsePayload($user, array $payload): void
    {
        if (! $user?->isExecutive()) {
            return;
        }

        if (($payload['partner_type'] ?? '') !== 'third-party') {
            throw ValidationException::withMessages([
                'partner_type' => 'Executives can only create third-party agency drivers.',
            ]);
        }

        $branchId = (int) ($payload['agency_branch_id'] ?? 0);
        $allowed = $this->agencyService->executiveBranchIds($user);
        if (! in_array($branchId, $allowed, true)) {
            throw ValidationException::withMessages([
                'agency_branch_id' => 'You can only assign drivers to your agency branches.',
            ]);
        }

        $branch = AgencyBranch::find($branchId);
        if ($branch && ! $branch->coversZone((int) ($payload['zone_id'] ?? 0))) {
            throw ValidationException::withMessages([
                'driver-zone' => 'Selected zone must be one of the zones covered by this hub.',
            ]);
        }
    }

    private function assertExecutiveOwnsDriver($user, string $code): void
    {
        if (! $user?->isExecutive()) {
            return;
        }

        $driver = $this->driverService->findZoneDriverByCode($code);
        $allowed = $this->agencyService->executiveBranchIds($user);
        if (! in_array((int) $driver->agency_branch_id, $allowed, true)) {
            abort(403, 'You can only manage drivers in your assigned branches.');
        }
    }
}
