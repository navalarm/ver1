<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'tokens' => 10,
                'price' => 999, // $9.99
                'description' => 'Perfect for trying out our service',
                'is_active' => true,
            ],
            [
                'name' => 'Basic',
                'tokens' => 50,
                'price' => 3999, // $39.99
                'description' => 'Great for regular users',
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'tokens' => 150,
                'price' => 9999, // $99.99
                'description' => 'Best value for power users',
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'tokens' => 500,
                'price' => 29999, // $299.99
                'description' => 'For businesses and teams',
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
