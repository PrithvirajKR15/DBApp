<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Services\BatchService;
use App\Services\OperationsDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $request->validate([
            'store_id' => ['required', 'string'],
            'store_name' => ['nullable', 'string'],
            'overflow_count' => ['nullable', 'integer', 'min:0'],
            'batches' => ['required', 'array', 'min:1'],
            'batches.*.id' => ['required', 'string'],
            'batches.*.driver_code' => ['required', 'string'],
            'batches.*.orders' => ['required', 'array', 'min:1'],
        ]);

        $result = $this->batches->storeGeneratedBatches($request->all());

        return response()->json([
            'message' => count($result['batches']).' batch(es) saved in group '.$result['group']['id'].'.',
            'group' => $result['group'],
            'batches' => $result['batches'],
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

    public function moveOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_code' => ['required', 'string'],
            'from_batch' => ['required', 'string'],
            'to_batch' => ['required', 'string'],
        ]);

        $result = $this->batches->moveOrder(
            (string) $validated['order_code'],
            (string) $validated['from_batch'],
            (string) $validated['to_batch']
        );

        return response()->json([
            'message' => 'Order moved to the selected driver batch.',
            'from' => $result['from'],
            'to' => $result['to'],
        ]);
    }

    public function reorderStops(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'order_codes' => ['required', 'array', 'min:1'],
            'order_codes.*' => ['required', 'string'],
        ]);

        $batch = $this->batches->reorderStops($code, $validated['order_codes']);

        return response()->json([
            'message' => 'Stop sequence updated.',
            'batch' => $batch,
        ]);
    }

    public function complete(string $code): JsonResponse
    {
        $batch = $this->batches->completeBatch($code);

        return response()->json([
            'message' => 'Batch marked delivered.',
            'batch' => $batch,
        ]);
    }

    public function cancel(string $code): JsonResponse
    {
        $batch = $this->batches->cancelBatch($code);

        return response()->json([
            'message' => 'Batch cancelled.',
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
