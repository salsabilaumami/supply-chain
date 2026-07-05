<?php

namespace Database\Seeders;

use App\Models\PositiveWord;
use Illuminate\Database\Seeder;

class PositiveWordSeeder extends Seeder
{
    public function run(): void
    {
        $words = [
            'growth',
            'increase',
            'profit',
            'stable',
            'improve',
            'recovery',
            'strong',
            'surplus',
            'success',
            'positive',
            'gain',
            'rise',
            'expand',
            'efficient',
            'secure',
            'safe',
            'opportunity',
            'development',
            'progress',
            'resilient',
        ];

        foreach ($words as $word) {
            PositiveWord::updateOrCreate(
                ['word' => $word],
                [
                    'language' => 'en',
                    'weight' => 1,
                ]
            );
        }
    }
}