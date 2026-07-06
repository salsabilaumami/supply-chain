<?php

namespace App\Services;

class RiskScoringService
{
    private array $weights = [
        'weather' => 0.25,
        'inflation' => 0.25,
        'currency' => 0.20,
        'news' => 0.30,
    ];

    public function calculateTotalScore(
        float $weatherScore,
        float $inflationScore,
        float $currencyScore,
        float $newsScore
    ): float {
        $totalScore =
            ($weatherScore * $this->weights['weather']) +
            ($inflationScore * $this->weights['inflation']) +
            ($currencyScore * $this->weights['currency']) +
            ($newsScore * $this->weights['news']);

        return round($totalScore, 2);
    }

    public function determineRiskLevel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'critical',
            $score >= 50 => 'high',
            $score >= 25 => 'medium',
            default => 'low',
        };
    }

    public function calculateWeatherScore(
        float $precipitation,
        float $windSpeed,
        int $weatherCode
    ): float {
        $precipitationScore = match (true) {
            $precipitation >= 50 => 100,
            $precipitation >= 20 => 80,
            $precipitation >= 10 => 60,
            $precipitation >= 2.5 => 30,
            default => 10,
        };

        $windScore = match (true) {
            $windSpeed >= 90 => 100,
            $windSpeed >= 60 => 80,
            $windSpeed >= 40 => 60,
            $windSpeed >= 20 => 30,
            default => 10,
        };

        $weatherConditionScore = match (true) {
            in_array($weatherCode, [95, 96, 99]) => 100,
            in_array($weatherCode, [80, 81, 82]) => 75,
            in_array($weatherCode, [61, 63, 65, 66, 67]) => 60,
            in_array($weatherCode, [45, 48]) => 40,
            default => 10,
        };

        $weatherScore =
            ($precipitationScore * 0.35) +
            ($windScore * 0.35) +
            ($weatherConditionScore * 0.30);

                return round($weatherScore, 2);
    }

       public function calculateInflationScore(float $inflationRate): float
    {
        $inflationScore = match (true) {
            $inflationRate < 0 => 55,
            $inflationRate <= 2 => 10,
            $inflationRate <= 4 => 25,
            $inflationRate <= 6 => 45,
            $inflationRate <= 10 => 70,
            $inflationRate <= 20 => 85,
            default => 100,
        };

        return (float) $inflationScore;
    }

        public function calculateCurrencyScore(float $changePercentage): float
    {
        $absoluteChange = abs($changePercentage);

        $currencyScore = match (true) {
            $absoluteChange <= 1 => 10,
            $absoluteChange <= 3 => 25,
            $absoluteChange <= 5 => 45,
            $absoluteChange <= 10 => 70,
            $absoluteChange <= 20 => 85,
            default => 100,
        };

        return (float) $currencyScore;
    }

    public function calculateNewsScore(
        int $positiveCount,
        int $negativeCount
    ): float {
        $totalWords = $positiveCount + $negativeCount;

        if ($totalWords === 0) {
            return 50.0;
        }

        $negativeRatio = $negativeCount / $totalWords;

        return round($negativeRatio * 100, 2);
    }
}