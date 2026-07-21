<?php

namespace Database\Seeders;

use App\Models\BankTransfer;
use App\Models\DriverPayout;
use App\Models\DriverPayoutOrder;
use App\Models\DriverPayoutProfile;
use Illuminate\Database\Seeder;

class PayoutSeeder extends Seeder
{
    public function run(): void
    {
        $data = include database_path('seeders/data/payouts.php');

        foreach ($data['drivers'] ?? [] as $driver) {
            $profile = DriverPayoutProfile::updateOrCreate(
                ['driver_code' => $driver['id']],
                [
                    'name' => $driver['name'],
                    'phone' => $driver['phone'] ?? null,
                    'type' => $driver['type'] ?? null,
                    'zone' => $driver['zone'] ?? null,
                    'avatar' => $driver['avatar'] ?? null,
                    'lifetime_paid' => (float) ($driver['lifetime_paid'] ?? 0),
                    'pending_amount' => (float) ($driver['pending_amount'] ?? 0),
                    'paid_orders' => (int) ($driver['paid_orders'] ?? 0),
                    'last_payout_at' => $driver['last_payout_at'] ?? null,
                    'status' => $driver['status'] ?? 'active',
                ]
            );

            foreach ($driver['history'] ?? [] as $payout) {
                $payoutModel = DriverPayout::updateOrCreate(
                    ['code' => $payout['id']],
                    [
                        'driver_payout_profile_id' => $profile->id,
                        'paid_at' => $payout['paid_at'] ?? null,
                        'period' => $payout['period'] ?? null,
                        'method' => $payout['method'] ?? null,
                        'reference' => $payout['reference'] ?? null,
                        'status' => $payout['status'] ?? 'paid',
                    ]
                );

                $payoutModel->orders()->delete();
                foreach ($payout['orders'] ?? [] as $order) {
                    DriverPayoutOrder::create([
                        'driver_payout_id' => $payoutModel->id,
                        'order_code' => $order['order_id'],
                        'delivered_at' => $order['delivered_at'] ?? null,
                        'delivery_fee' => (float) ($order['delivery_fee'] ?? 0),
                        'bonus' => (float) ($order['bonus'] ?? 0),
                        'deduction' => (float) ($order['deduction'] ?? 0),
                        'net' => (float) ($order['net'] ?? 0),
                    ]);
                }
            }
        }

        foreach ($data['payouts'] ?? [] as $transfer) {
            BankTransfer::updateOrCreate(
                ['code' => $transfer['id']],
                [
                    'requested_date' => $transfer['requested_date'],
                    'bank' => $transfer['bank'],
                    'account_ending' => $transfer['account_ending'] ?? null,
                    'amount' => (float) ($transfer['amount'] ?? 0),
                    'status' => $transfer['status'] ?? 'pending',
                    'settled_date' => $transfer['settled_date'] ?? null,
                ]
            );
        }
    }
}
