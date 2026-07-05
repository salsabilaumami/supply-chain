<?php

namespace Database\Seeders;

use App\Models\NegativeWord;
use Illuminate\Database\Seeder;

class NegativeWordSeeder extends Seeder
{
    public function run(): void
    {
        $words = [
            'war',
            'crisis',
            'inflation',
            'delay',
            'disaster',
            'conflict',
            'shortage',
            'congestion',
            'decrease',
            'decline',
            'loss',
            'risk',
            'danger',
            'disruption',
            'attack',
            'sanction',
            'unstable',
            'recession',
            'collapse',
            'damage',
        ];

        foreach ($words as $word) {
            NegativeWord::updateOrCreate(
                ['word' => $word],
                [
                    'language' => 'en',
                    'weight' => 1,
                ]
            );
        }
    }
}