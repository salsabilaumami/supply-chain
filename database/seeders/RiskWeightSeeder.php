<?php

namespace Database\Seeders;

use App\Models\RiskWeight;
use Illuminate\Database\Seeder;

class RiskWeightSeeder extends Seeder
{
    public function run(): void
    {
        RiskWeight::updateOrCreate(
            ['component_name' => 'weather'],
            ['weight' => 0.30, 'is_active' => true]
        );

        RiskWeight::updateOrCreate(
            ['component_name' => 'inflation'],
            ['weight' => 0.20, 'is_active' => true]
        );

        RiskWeight::updateOrCreate(
            ['component_name' => 'news'],
            ['weight' => 0.40, 'is_active' => true]
        );

        RiskWeight::updateOrCreate(
            ['component_name' => 'currency'],
            ['weight' => 0.10, 'is_active' => true]
        );
    }
}