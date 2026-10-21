<?php

namespace Modules\Order\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Order\Models\Order;

class OrderDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        Order::factory()->for($user)->create();
        Order::factory()->for($user)->confirmed()->create();
        Order::factory()->for($user)->cancelled()->create();
    }
}
