<?php

namespace App\Services;

class RiskScoringService
{
    public const WEATHER_WEIGHT = 0.30;
    public const INFLATION_WEIGHT = 0.20;
    public const CURRENCY_WEIGHT = 0.10;
    public const NEWS_WEIGHT = 0.40;

    public function calculateWeatherScore(
        float $precipitation = 0,
        float $windSpeed = 0,
        int $weatherCode = 0
    ): float {
        $score = 0;

        if ($precipitation <= 0) {
            $score += 5;
        } elseif ($precipitation <= 2) {
            $score += 20;
        } elseif ($precipitation <= 10) {
            $score += 45;
        } elseif ($precipitation <= 25) {
            $score += 65;
        } else {
            $score += 85;
        }

        if ($windSpeed >= 60) {
            $score += 15;
        } elseif ($windSpeed >= 40) {
            $score += 10;
        } elseif ($windSpeed >= 25) {
            $score += 5;
        }

        if (in_array($weatherCode, [95, 96, 99], true)) {
            $score += 20;
        } elseif (in_array($weatherCode, [61, 63, 65, 66, 67, 80, 81, 82], true)) {
            $score += 10;
        }

        return $this->normalizeScore($score);
    }

    public function calculateInflationScore(?float $inflation): float
    {
        if ($inflation === null) {
            return 30.0;
        }

        $inflation = abs($inflation);

        return match (true) {
            $inflation <= 2 => 10.0,
            $inflation <= 5 => 25.0,
            $inflation <= 8 => 50.0,
            $inflation <= 12 => 75.0,
            default => 90.0,
        };
    }

    public function calculateCurrencyScore(
        ?float $changePercentage,
        ?float $storedCurrencyRisk = null
    ): float {
        if ($storedCurrencyRisk !== null) {
            return $this->normalizeScore($storedCurrencyRisk);
        }

        if ($changePercentage === null) {
            return 20.0;
        }

        $change = abs($changePercentage);

        return match (true) {
            $change <= 1 => 10.0,
            $change <= 3 => 25.0,
            $change <= 6 => 45.0,
            $change <= 10 => 70.0,
            default => 90.0,
        };
    }

    public function calculateNewsScore(
        int $positiveCount = 0,
        int $neutralCount = 0,
        int $negativeCount = 0,
        ?float $averageRiskScore = null
    ): float {
        if ($averageRiskScore !== null && $averageRiskScore > 0) {
            return $this->normalizeScore($averageRiskScore);
        }

        $total = $positiveCount + $neutralCount + $negativeCount;

        if ($total <= 0) {
            return 35.0;
        }

        $score = (
            ($positiveCount * 15)
            + ($neutralCount * 35)
            + ($negativeCount * 75)
        ) / $total;

        return $this->normalizeScore($score);
    }

    public function calculateTotalScore(
        float $weatherScore,
        float $inflationScore,
        float $currencyScore,
        float $newsScore
    ): float {
        $weatherScore = $this->normalizeScore($weatherScore);
        $inflationScore = $this->normalizeScore($inflationScore);
        $currencyScore = $this->normalizeScore($currencyScore);
        $newsScore = $this->normalizeScore($newsScore);

        $totalScore =
            ($weatherScore * self::WEATHER_WEIGHT)
            + ($inflationScore * self::INFLATION_WEIGHT)
            + ($currencyScore * self::CURRENCY_WEIGHT)
            + ($newsScore * self::NEWS_WEIGHT);

        return $this->normalizeScore($totalScore);
    }

    public function calculateDetailedScore(
        float $weatherScore,
        float $inflationScore,
        float $currencyScore,
        float $newsScore
    ): array {
        $weatherScore = $this->normalizeScore($weatherScore);
        $inflationScore = $this->normalizeScore($inflationScore);
        $currencyScore = $this->normalizeScore($currencyScore);
        $newsScore = $this->normalizeScore($newsScore);

        $weatherWeighted = $weatherScore * self::WEATHER_WEIGHT;
        $inflationWeighted = $inflationScore * self::INFLATION_WEIGHT;
        $currencyWeighted = $currencyScore * self::CURRENCY_WEIGHT;
        $newsWeighted = $newsScore * self::NEWS_WEIGHT;

        $totalScore = $this->calculateTotalScore(
            $weatherScore,
            $inflationScore,
            $currencyScore,
            $newsScore
        );

        return [
            'weather_score' => $weatherScore,
            'inflation_score' => $inflationScore,
            'currency_score' => $currencyScore,
            'news_score' => $newsScore,
            'weather_weighted' => round($weatherWeighted, 2),
            'inflation_weighted' => round($inflationWeighted, 2),
            'currency_weighted' => round($currencyWeighted, 2),
            'news_weighted' => round($newsWeighted, 2),
            'total_score' => $totalScore,
            'risk_level' => $this->getRiskLevel($totalScore),
            'risk_label' => $this->getRiskLabel($totalScore),
        ];
    }

    public function getRiskLevel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'critical',
            $score >= 50 => 'high',
            $score >= 25 => 'moderate',
            default => 'low',
        };
    }

    public function determineRiskLevel(float $score): string
    {
        return $this->getRiskLevel($score);
    }

    public function getRiskLabel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'Critical Risk',
            $score >= 50 => 'High Risk',
            $score >= 25 => 'Medium Risk',
            default => 'Low Risk',
        };
    }

    public function determineRiskLabel(float $score): string
    {
        return $this->getRiskLabel($score);
    }

    public function getRecommendation(
        float $score,
        string $countryName
    ): string {
        return match (true) {
            $score >= 75 =>
                "{$countryName} berada pada kategori Critical Risk. Keputusan supply chain perlu dievaluasi ulang karena potensi gangguan sangat tinggi.",

            $score >= 50 =>
                "{$countryName} berada pada kategori High Risk. Perlu mitigasi terhadap cuaca, inflasi, kurs, dan sentimen berita sebelum mengambil keputusan supply chain.",

            $score >= 25 =>
                "{$countryName} berada pada kategori Medium Risk. Negara masih dapat dipantau, tetapi perlu monitoring berkala terhadap perubahan indikator.",

            default =>
                "{$countryName} berada pada kategori Low Risk. Kondisi relatif aman untuk pemantauan rantai pasok, tetapi tetap perlu evaluasi berkala.",
        };
    }

    private function normalizeScore(float $score): float
    {
        return round(min(100, max(0, $score)), 2);
    }
}