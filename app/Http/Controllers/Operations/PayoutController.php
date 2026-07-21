<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Services\OperationsDataService;

class PayoutController extends Controller
{
    public function __construct(
        protected OperationsDataService $operations
    ) {}

    public function index()
    {
        return view('content.pages.payouts', [
            'data' => $this->operations->payoutsPageData(),
        ]);
    }

    public function driver(string $id)
    {
        $driver = $this->operations->findPayoutDriver($id);
        abort_unless($driver, 404);

        return view('content.pages.driver-payout-detail', [
            'driver' => $driver,
        ]);
    }
}
