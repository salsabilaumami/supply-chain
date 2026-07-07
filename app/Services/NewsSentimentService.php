<?php

namespace App\Services;

use App\Models\NegativeWord;
use App\Models\NewsCache;
use App\Models\NewsSentiment;
use App\Models\PositiveWord;

class NewsSentimentService
{
    public function __construct(
        private readonly RiskScoringService $riskScoringService
    ) {
    }

    public function analyzeNews(NewsCache $newsCache): NewsSentiment
    {
        $result = $this->analyzeText(
            $newsCache->title,
            $newsCache->description
        );

        return NewsSentiment::updateOrCreate(
            [
                'news_cache_id' => $newsCache->id,
            ],
            [
                'positive_score' => $result['positive_score'],
                'negative_score' => $result['negative_score'],
                'neutral_score' => $result['neutral_score'],
                'sentiment' => $result['sentiment'],
                'risk_score' => $result['risk_score'],
                'analyzed_at' => now(),
            ]
        );
    }

    public function analyzeText(
        string $title,
        ?string $description = null
    ): array {
        $text = trim($title . ' ' . ($description ?? ''));

        $words = $this->tokenize($text);

        $positiveWords = PositiveWord::query()
            ->pluck('word')
            ->map(fn ($word) => strtolower($word))
            ->toArray();

        $negativeWords = NegativeWord::query()
            ->pluck('word')
            ->map(fn ($word) => strtolower($word))
            ->toArray();

        $positiveScore = 0;
        $negativeScore = 0;
        $neutralScore = 0;

        foreach ($words as $word) {
            if (in_array($word, $positiveWords, true)) {
                $positiveScore++;

                continue;
            }

            if (in_array($word, $negativeWords, true)) {
                $negativeScore++;

                continue;
            }

            $neutralScore++;
        }

        $sentiment = $this->determineSentiment(
            $positiveScore,
            $negativeScore
        );

        $riskScore = $this->riskScoringService->calculateNewsScore(
            $positiveScore,
            $negativeScore
        );

        return [
            'positive_score' => $positiveScore,
            'negative_score' => $negativeScore,
            'neutral_score' => $neutralScore,
            'sentiment' => $sentiment,
            'risk_score' => $riskScore,
            'total_words' => count($words),
        ];
    }

    private function determineSentiment(
        int $positiveScore,
        int $negativeScore
    ): string {
        if ($positiveScore > $negativeScore) {
            return 'positive';
        }

        if ($negativeScore > $positiveScore) {
            return 'negative';
        }

        return 'neutral';
    }

    private function tokenize(string $text): array
    {
        $text = strtolower($text);

        preg_match_all('/[a-z]+/', $text, $matches);

        return $matches[0] ?? [];
    }
}