<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Services\AgencyService;
use App\Services\DriverService;
use Illuminate\Support\Facades\Auth;

class ExecutiveDashboardController extends Controller
{
    public function __construct(
        protected AgencyService $agencyService,
        protected DriverService $driverService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $branchIds = $this->agencyService->executiveBranchIds($user);
        $executive = $user->agencyExecutive?->load(['agency', 'branches.zones']);

        $drivers = Driver::zoneDrivers()
            ->with(['user', 'activeAssignment.zone', 'agencyBranch'])
            ->whereIn('agency_branch_id', $branchIds ?: [0])
            ->whereHas('user')
            ->get();

        $driverIds = $drivers->pluck('id');

        $stats = [
            'drivers_total' => $drivers->count(),
            'drivers_online' => $drivers->where('availability', 'Online')->count(),
            'drivers_offline' => $drivers->where('availability', 'Offline')->count(),
            'drivers_transit' => $drivers->where('availability', 'Transit')->count(),
            'orders_assigned' => Order::query()->whereIn('driver_id', $driverIds)->whereIn('status', ['assigned', 'out', 'transit'])->count(),
            'orders_completed' => Order::query()->whereIn('driver_id', $driverIds)->where('status', 'completed')->count(),
        ];

        return view('content.dashboard.executive-dashboard', [
            'executive' => $executive,
            'stats' => $stats,
            'drivers' => $drivers->take(10)->map(fn (Driver $d) => $this->driverService->shapeZoneDriver($d)),
            'branches' => $executive?->branches ?? collect(),
        ]);
    }
}
