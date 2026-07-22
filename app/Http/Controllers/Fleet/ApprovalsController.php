<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Services\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalsController extends Controller
{
    public function __construct(
        protected DriverService $driverService
    ) {}

    public function index()
    {
        $zones = Zone::orderBy('name')->get(['id', 'code', 'name']);

        return view('content.pages.approvals', [
            'zones' => $zones,
        ]);
    }

    public function list(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'drivers' => $this->driverService->listApprovalDrivers()->values(),
            'pending_count' => $this->driverService->countApprovalDriversByStatus('Pending'),
            'rejected_count' => $this->driverService->countApprovalDriversByStatus('Rejected'),
        ]);
    }

    public function updateStatus(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:Pending,Active,Rejected',
        ]);

        try {
            $driver = $this->driverService->updateDriverAccountStatus($code, [
                'status' => $request->input('status'),
            ]);

            $message = match ($request->input('status')) {
                'Active' => 'Driver approved successfully.',
                'Rejected' => 'Driver application rejected.',
                default => 'Driver status updated successfully.',
            };

            return response()->json([
                'status' => true,
                'message' => $message,
                'driver' => $this->driverService->shapeApprovalDriver($driver),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codes' => 'required|array|min:1',
            'codes.*' => 'string',
            'status' => 'required|in:Pending,Active,Rejected',
        ]);

        $updated = 0;
        $failed = [];

        foreach ($validated['codes'] as $code) {
            try {
                $this->driverService->updateDriverAccountStatus($code, [
                    'status' => $validated['status'],
                ]);
                $updated++;
            } catch (\Throwable $e) {
                $failed[] = ['code' => $code, 'message' => $e->getMessage()];
            }
        }

        return response()->json([
            'status' => $updated > 0,
            'message' => $validated['status'] === 'Active'
                ? "Approved {$updated} application(s)."
                : "Rejected {$updated} application(s).",
            'updated' => $updated,
            'failed' => $failed,
            'drivers' => $this->driverService->listApprovalDrivers()->values(),
            'pending_count' => $this->driverService->countApprovalDriversByStatus('Pending'),
            'rejected_count' => $this->driverService->countApprovalDriversByStatus('Rejected'),
        ], $updated > 0 ? 200 : 422);
    }
}
