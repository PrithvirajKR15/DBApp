<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Services\BatchService;
use App\Services\OperationsDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BatchController extends Controller
{
    public function __construct(
        protected OperationsDataService $operations,
        protected BatchService $batches
    ) {}

    public function index()
    {
        return view('content.pages.delivery-batches', [
            'data' => $this->operations->batchesPageData(),
        ]);
    }

    public function generate()
    {
        return view('content.pages.delivery-batch-generate', [
            'data' => $this->operations->batchesPageData(),
        ]);
    }

    public function settings()
    {
        return view('content.pages.delivery-batch-settings', [
            'data' => $this->operations->batchesPageData(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'string'],
            'store_name' => ['nullable', 'string'],
            'batches' => ['required', 'array', 'min:1'],
            'batches.*.id' => ['required', 'string'],
            'batches.*.orders' => ['required', 'array', 'min:1'],
        ]);

        try {
            $created = $this->batches->storeGeneratedBatches($request->all());
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json([
            'message' => count($created) . ' batch(es) saved.',
            'batches' => $created,
        ]);
    }

    public function assign(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'driver_code' => ['required', 'string'],
        ]);

        $batch = $this->batches->assignStoreDriver($code, (string) $validated['driver_code']);

        return response()->json([
            'message' => 'Store driver assigned to batch.',
            'batch' => $batch,
        ]);
    }

    public function saveSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orders_per_batch' => ['required', 'integer', 'min:1', 'max:50'],
            'accept_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'max_distance_km' => ['required', 'numeric', 'min:1'],
            'max_route_minutes' => ['required', 'integer', 'min:10'],
            'slot_window' => ['nullable', 'string', 'max:50'],
        ]);

        $settings = $this->batches->updateSettings($validated);

        return response()->json([
            'message' => 'Batch configuration saved.',
            'settings' => $settings,
        ]);
    }
}
