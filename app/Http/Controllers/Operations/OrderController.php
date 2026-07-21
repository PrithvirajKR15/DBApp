<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Services\OperationsDataService;

class OrderController extends Controller
{
    public function __construct(
        protected OperationsDataService $operations
    ) {}

    public function index()
    {
        return view('content.pages.orders', [
            'ordersData' => $this->operations->ordersPageData(),
        ]);
    }

    public function show(string $id)
    {
        $order = $this->operations->findOrderForDetail($id);
        $delivered = $order && ($order['delivery'] ?? '') === 'delivered';

        if ($delivered) {
            return redirect()->route(
                'operations-orders-completed',
                array_merge(['id' => $id], request()->query())
            );
        }

        return view('content.pages.order-detail', [
            'orderId' => $id,
            'order' => $order,
            'ordersData' => $this->operations->ordersPageData(),
        ]);
    }

    public function completed(string $id)
    {
        $order = $this->operations->findOrderForDetail($id);
        $batchesData = $this->operations->batchesPageData();

        return view('content.pages.completed-delivery-detail', [
            'orderId' => $id,
            'order' => $order,
            'ordersData' => $this->operations->ordersPageData(),
            'batchesData' => $batchesData,
        ]);
    }
}
