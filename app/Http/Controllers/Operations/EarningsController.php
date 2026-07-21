<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Services\OperationsDataService;

class EarningsController extends Controller
{
    public function __construct(
        protected OperationsDataService $operations
    ) {}

    public function index()
    {
        return view('content.pages.earnings', [
            'data' => $this->operations->earningsPageData(),
        ]);
    }
}
