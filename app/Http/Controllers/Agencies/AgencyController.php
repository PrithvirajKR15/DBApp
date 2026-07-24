<?php

namespace App\Http\Controllers\Agencies;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgencyBranchRequest;
use App\Http\Requests\StoreAgencyExecutiveRequest;
use App\Http\Requests\StoreAgencyRequest;
use App\Http\Requests\UpdateAgencyBranchRequest;
use App\Http\Requests\UpdateAgencyRequest;
use App\Models\Agency;
use App\Models\AgencyBranch;
use App\Models\AgencyExecutive;
use App\Services\AgencyService;
use App\Services\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AgencyController extends Controller
{
    public function __construct(
        protected AgencyService $agencyService,
        protected DriverService $driverService
    ) {}

    public function pending()
    {
        return view('content.pages.agencies', [
            'mode' => 'pending',
            'pageTitle' => 'Pending Agency Requests',
        ]);
    }

    public function approved()
    {
        return view('content.pages.agencies', [
            'mode' => 'approved',
            'pageTitle' => 'Approved Agencies',
        ]);
    }

    public function index()
    {
        return view('content.pages.agencies', [
            'mode' => 'all',
            'pageTitle' => 'Third Party Agencies',
        ]);
    }

    public function show(int $id)
    {
        $user = auth()->user();
        $agency = $this->agencyService->visibleAgenciesQuery($user)->findOrFail($id);

        return view('content.pages.agency-detail', [
            'agencyId' => $agency->id,
            'canEdit' => $this->agencyService->canEditAgency($user, $agency),
            'zones' => $this->agencyService->zonesForUser($user),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $this->agencyService->visibleAgenciesQuery($user);

        $status = $request->query('status');
        if ($status === 'pending') {
            $query->where('status', Agency::STATUS_PENDING);
        } elseif ($status === 'approved' || $status === 'active') {
            $query->where('status', Agency::STATUS_ACTIVE);
        } elseif ($status === 'rejected') {
            $query->where('status', Agency::STATUS_REJECTED);
        }

        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $paginator = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => true,
            'agencies' => collect($paginator->items())->map(function (Agency $agency) use ($user) {
                $shaped = $this->agencyService->shapeAgency($agency);

                return array_merge($shaped, [
                    'can_edit' => $this->agencyService->canEditAgency($user, $agency),
                ]);
            })->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'can_create' => $user->isAdmin() || $user->isStoreAdmin(),
            'zones' => $this->agencyService->zonesForUser($user)->map(fn ($z) => [
                'id' => $z->id,
                'name' => $z->name,
                'code' => $z->code,
            ])->values(),
        ]);
    }

    public function showExecutive(Request $request, int $executiveId)
    {
        $user = $request->user();
        $executive = AgencyExecutive::with(['user', 'agency', 'branches.zones'])->findOrFail($executiveId);
        $this->agencyService->assertCanViewExecutive($user, $executive);

        $agency = $this->agencyService->visibleAgenciesQuery($user)->findOrFail($executive->agency_id);

        return view('content.pages.agency-executive', [
            'executiveId' => $executive->id,
            'executive' => $this->agencyService->shapeExecutive($executive),
            'agencyName' => $agency->name,
            'backUrl' => $user->isStoreAdmin() && ! $user->isAdmin()
                ? route('store-agencies.show', $agency->id)
                : route('agencies.show', $agency->id),
        ]);
    }

    public function executiveDrivers(Request $request, int $executiveId): JsonResponse
    {
        $user = $request->user();
        $executive = AgencyExecutive::with('branches')->findOrFail($executiveId);
        $this->agencyService->assertCanViewExecutive($user, $executive);

        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $paginator = $this->agencyService->executiveDriversQuery($executive)
            ->with(['user', 'activeAssignment.zone', 'agencyBranch.agency', 'orders'])
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'drivers' => collect($paginator->items())->map(
                fn ($driver) => $this->driverService->shapeZoneDriver($driver)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function store(StoreAgencyRequest $request): JsonResponse
    {
        try {
            $agency = $this->agencyService->createAgency($request->user(), $request->validated());

            return response()->json([
                'status' => true,
                'message' => $agency->status === Agency::STATUS_ACTIVE
                    ? 'Agency created and activated.'
                    : 'Agency submitted for Admin approval.',
                'agency' => $this->agencyService->shapeAgency($agency->fresh(['creator', 'store', 'branches.zones'])),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateAgencyRequest $request, int $id): JsonResponse
    {
        $agency = Agency::findOrFail($id);

        try {
            $agency = $this->agencyService->updateAgency($request->user(), $agency, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Agency updated.',
                'agency' => $this->agencyService->shapeAgency($agency),
            ]);
        } catch (\Throwable $e) {
            $code = $e->getCode() === 403 ? 403 : 500;

            return response()->json(['status' => false, 'message' => $e->getMessage()], $code);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $agency = Agency::findOrFail($id);

        try {
            $this->agencyService->deleteAgency($request->user(), $agency);

            return response()->json(['status' => true, 'message' => 'Agency deleted.']);
        } catch (\Throwable $e) {
            $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            return response()->json(['status' => false, 'message' => $e->getMessage()], is_int($code) ? $code : 500);
        }
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $agency = Agency::findOrFail($id);
        $agency = $this->agencyService->approve($request->user(), $agency);

        return response()->json([
            'status' => true,
            'message' => 'Agency approved.',
            'agency' => $this->agencyService->shapeAgency($agency->load(['creator', 'store', 'branches.zones'])),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['rejection_reason' => 'nullable|string|max:1000']);
        $agency = Agency::findOrFail($id);
        $agency = $this->agencyService->reject(
            $request->user(),
            $agency,
            $request->input('rejection_reason')
        );

        return response()->json([
            'status' => true,
            'message' => 'Agency rejected.',
            'agency' => $this->agencyService->shapeAgency($agency->load(['creator', 'store', 'branches.zones'])),
        ]);
    }

    public function detail(Request $request, int $id): JsonResponse
    {
        $agency = $this->agencyService->visibleAgenciesQuery($request->user())->findOrFail($id);

        return response()->json([
            'status' => true,
            'agency' => $this->agencyService->shapeAgency($agency),
            'can_edit' => $this->agencyService->canEditAgency($request->user(), $agency),
            'zones' => $this->agencyService->zonesForUser($request->user())->map(fn ($z) => [
                'id' => $z->id,
                'name' => $z->name,
                'code' => $z->code,
            ])->values(),
        ]);
    }

    public function storeBranch(StoreAgencyBranchRequest $request, int $id): JsonResponse
    {
        $agency = Agency::findOrFail($id);

        try {
            $branches = $this->agencyService->createBranches($request->user(), $agency, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Hub created with selected zones.',
                'branches' => $branches->map(fn ($b) => $this->agencyService->shapeBranch($b))->values(),
                'branch' => $this->agencyService->shapeBranch($branches->first()),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $code = $e->getCode() === 403 ? 403 : 500;

            return response()->json(['status' => false, 'message' => $e->getMessage()], $code);
        }
    }

    public function updateBranch(UpdateAgencyBranchRequest $request, int $branchId): JsonResponse
    {
        $branch = AgencyBranch::with('agency')->findOrFail($branchId);

        try {
            $branch = $this->agencyService->updateBranch($request->user(), $branch, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Branch updated.',
                'branch' => $this->agencyService->shapeBranch($branch),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $code = $e->getCode() === 403 ? 403 : 500;

            return response()->json(['status' => false, 'message' => $e->getMessage()], $code);
        }
    }

    public function destroyBranch(Request $request, int $branchId): JsonResponse
    {
        $branch = AgencyBranch::with('agency')->findOrFail($branchId);

        try {
            $this->agencyService->deleteBranch($request->user(), $branch);

            return response()->json(['status' => true, 'message' => 'Branch deleted.']);
        } catch (\Throwable $e) {
            $code = $e->getCode() === 403 ? 403 : 500;

            return response()->json(['status' => false, 'message' => $e->getMessage()], $code);
        }
    }

    public function storeExecutive(StoreAgencyExecutiveRequest $request, int $id): JsonResponse
    {
        $agency = Agency::findOrFail($id);
        $data = $request->validated();
        $branchIds = array_map('intval', $data['branch_ids'] ?? []);

        try {
            $executive = $this->agencyService->createExecutive($request->user(), $agency, $data, $branchIds);

            return response()->json([
                'status' => true,
                'message' => 'Executive created.',
                'executive' => $this->agencyService->shapeExecutive($executive),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $code = $e->getCode() === 403 ? 403 : 500;

            return response()->json(['status' => false, 'message' => $e->getMessage()], $code);
        }
    }

    public function syncExecutiveBranches(Request $request, int $executiveId): JsonResponse
    {
        $request->validate([
            'branch_ids' => 'required|array|min:1',
            'branch_ids.*' => 'integer|exists:agency_branches,id',
        ]);

        $executive = AgencyExecutive::with('agency')->findOrFail($executiveId);
        $executive = $this->agencyService->syncExecutiveBranches(
            $request->user(),
            $executive,
            array_map('intval', $request->input('branch_ids'))
        );

        return response()->json([
            'status' => true,
            'message' => 'Executive branches updated.',
            'executive' => $this->agencyService->shapeExecutive($executive),
        ]);
    }

    public function branchesForZone(Request $request): JsonResponse
    {
        $zoneId = $request->query('zone_id') ? (int) $request->query('zone_id') : null;
        $agencyId = $request->query('agency_id') ? (int) $request->query('agency_id') : null;

        // Agencies come from all eligible hubs (not zone-filtered), so the Agency
        // dropdown always lists companies from the agencies table.
        $agencySource = $this->agencyService->branchesForDriverForm($request->user(), null);
        $agencies = $agencySource
            ->unique(fn ($b) => $b['agency_id'])
            ->map(fn ($b) => [
                'id' => $b['agency_id'],
                'name' => $b['agency_name'],
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $branches = collect();
        if ($agencyId) {
            $branches = $this->agencyService
                ->branchesForDriverForm($request->user(), $zoneId)
                ->where('agency_id', $agencyId)
                ->values();

            // If zone filter emptied the hub list, fall back to all hubs for that agency.
            if ($zoneId && $branches->isEmpty()) {
                $branches = $this->agencyService
                    ->branchesForDriverForm($request->user(), null)
                    ->where('agency_id', $agencyId)
                    ->values();
            }
        }

        return response()->json([
            'status' => true,
            'agencies' => $agencies,
            'branches' => $branches,
        ]);
    }
}
