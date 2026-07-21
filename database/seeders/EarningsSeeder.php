<?php

namespace Database\Seeders;

use App\Models\PlatformOrderEarning;
use App\Models\PlatformTransaction;
use Illuminate\Database\Seeder;

class EarningsSeeder extends Seeder
{
    public function run(): void
    {
        $data = include database_path('seeders/data/earnings.php');

        foreach ($data['order_earnings'] ?? [] as $row) {
            PlatformOrderEarning::updateOrCreate(
                [
                    'order_code' => $row['order_id'],
                    'earned_at' => $row['date'] ?? null,
                ],
                [
                    'store_name' => $row['store'] ?? null,
                    'customer' => $row['customer'] ?? null,
                    'driver_name' => $row['driver'] ?? null,
                    'order_amount' => (float) ($row['order_amount'] ?? 0),
                    'delivery_fee' => (float) ($row['delivery_fee'] ?? 0),
                    'refund' => (float) ($row['refund'] ?? 0),
                    'net_earning' => (float) ($row['net_earning'] ?? 0),
                    'status' => $row['status'] ?? 'succeeded',
                ]
            );
        }

        foreach ($data['transactions'] ?? [] as $txn) {
            PlatformTransaction::updateOrCreate(
                ['code' => $txn['id']],
                [
                    'occurred_at' => $txn['date'] ?? null,
                    'type' => $txn['type'],
                    'order_code' => $txn['order_id'] ?? null,
                    'driver_name' => $txn['driver'] ?? null,
                    'amount' => (float) ($txn['amount'] ?? 0),
                    'status' => $txn['status'] ?? 'succeeded',
                ]
            );
        }
    }
}
