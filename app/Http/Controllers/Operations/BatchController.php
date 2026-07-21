<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Services\OperationsDataService;

class BatchController extends Controller
{
    public function __construct(
        protected OperationsDataService $operations
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
}
