<?php

namespace Modules\Order\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Enums\Currency;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;

class OrderDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate([
            'email' => 'demo@example.com',
        ], [
            'name' => 'Demo User',
            'password' => Hash::make('password'),
        ]);

        foreach (OrderStatus::cases() as $status) {
            Order::query()->firstOrCreate([
                'number' => 'DEMO-'.strtoupper($status->value),
            ], [
                'user_id' => $user->getKey(),
                'status' => $status,
                'total_amount' => '100.00',
                'currency' => Currency::USD,
                'notes' => "Demo {$status->value} order.",
            ]);
        }
    }
}
