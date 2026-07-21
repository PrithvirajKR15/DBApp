<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Services\DriverService;

class DriverPageController extends Controller
{
    public function __construct(
        protected DriverService $driverService
    ) {}

    public function profile(string $id)
    {
        $driver = $this->driverService->shapeDriver(
            $this->driverService->findDriverByCode($id)
        );

        return view('content.pages.driver-profile', [
            'driver' => $driver,
            'driverId' => $id,
        ]);
    }

    public function review(string $id)
    {
        $driver = $this->driverService->shapeDriver(
            $this->driverService->findDriverByCode($id)
        );

        return view('content.pages.driver-review', [
            'driver' => $driver,
            'driverId' => $id,
        ]);
    }
}
