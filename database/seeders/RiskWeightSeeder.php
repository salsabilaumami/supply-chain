<?php

namespace Database\Seeders;

use App\Models\RiskWeight;
use Illuminate\Database\Seeder;

class RiskWeightSeeder extends Seeder
{
    public function run(): void
    {
        $weights = [
            [
                'component_name' => 'weather',
                'weight' => 0.25,
                'is_active' => true,
            ],
            [
                'component_name' => 'inflation',
                'weight' => 0.25,
                'is_active' => true,
            ],
            [
                'component_name' => 'currency',
                'weight' => 0.20,
                'is_active' => true,
            ],
            [
                'component_name' => 'news',
                'weight' => 0.30,
                'is_active' => true,
            ],
        ];

        foreach ($weights as $weight) {
            RiskWeight::updateOrCreate(
                ['component_name' => $weight['component_name']],
                [
                    'weight' => $weight['weight'],
                    'is_active' => $weight['is_active'],
                ]
            );
        }
    }
}