<?php

namespace Database\Seeders;

use App\Models\NegativeWord;
use App\Models\PositiveWord;
use Illuminate\Database\Seeder;

class SentimentLexiconSeeder extends Seeder
{
    public function run(): void
    {
        $positiveWords = [
            'growth' => 1.00,
            'increase' => 1.00,
            'increased' => 1.00,
            'improve' => 1.00,
            'improved' => 1.00,
            'improving' => 1.00,
            'recovery' => 1.00,
            'recover' => 1.00,
            'stable' => 1.00,
            'stability' => 1.00,
            'strong' => 1.00,
            'resilient' => 1.00,
            'surplus' => 1.00,
            'profit' => 1.00,
            'boost' => 1.00,
            'expansion' => 1.00,
            'investment' => 1.00,
            'agreement' => 1.00,
            'partnership' => 1.00,
            'cooperation' => 1.00,
            'efficiency' => 1.00,
            'opportunity' => 1.00,
            'trade deal' => 1.00,
            'supply growth' => 1.00,
            'logistics improvement' => 1.00,
            'export growth' => 1.00,
            'market recovery' => 1.00,
            'economic growth' => 1.00,
            'supply chain resilience' => 1.00,
            'port expansion' => 1.00,
        ];

        $negativeWords = [
            'crisis' => 1.00,
            'conflict' => 1.00,
            'war' => 1.00,
            'strike' => 1.00,
            'delay' => 1.00,
            'delayed' => 1.00,
            'disruption' => 1.00,
            'disrupted' => 1.00,
            'shortage' => 1.00,
            'inflation' => 1.00,
            'recession' => 1.00,
            'sanction' => 1.00,
            'sanctions' => 1.00,
            'tariff' => 1.00,
            'risk' => 1.00,
            'flood' => 1.00,
            'storm' => 1.00,
            'earthquake' => 1.00,
            'attack' => 1.00,
            'tension' => 1.00,
            'decline' => 1.00,
            'collapse' => 1.00,
            'blocked' => 1.00,
            'weak' => 1.00,
            'loss' => 1.00,
            'uncertainty' => 1.00,
            'port congestion' => 1.00,
            'shipment delay' => 1.00,
            'supply disruption' => 1.00,
            'trade war' => 1.00,
            'logistics crisis' => 1.00,
            'export ban' => 1.00,
            'import restriction' => 1.00,
            'supply chain disruption' => 1.00,
            'shipping delay' => 1.00,
            'cargo delay' => 1.00,
        ];

        foreach ($positiveWords as $word => $weight) {
            PositiveWord::query()->updateOrCreate(
                [
                    'word' => $word,
                    'language' => 'en',
                ],
                [
                    'weight' => $weight,
                ]
            );
        }

        foreach ($negativeWords as $word => $weight) {
            NegativeWord::query()->updateOrCreate(
                [
                    'word' => $word,
                    'language' => 'en',
                ],
                [
                    'weight' => $weight,
                ]
            );
        }
    }
}