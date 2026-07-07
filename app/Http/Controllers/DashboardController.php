<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\EconomicIndicator;
use App\Services\RiskScoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const INDICATOR_CODES = [
        'gdp' => 'NY.GDP.MKTP.CD',
        'inflation' => 'FP.CPI.TOTL.ZG',
        'population' => 'SP.POP.TOTL',
        'exports' => 'NE.EXP.GNFS.CD',
        'imports' => 'NE.IMP.GNFS.CD',
    ];

    public function index(
        Request $request,
        RiskScoringService $riskScoringService
    ): View {
        $countries = Country::query()
            ->alphabetical()
            ->get();

        $selectedIsoCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $selectedCountry = Country::query()
            ->byIsoCode($selectedIsoCode)
            ->first();

        if (!$selectedCountry) {
            $selectedCountry = Country::query()
                ->where('iso3_code', 'IDN')
                ->firstOrFail();
        }

        $economicData = $this->getEconomicData(
            $selectedCountry
        );

        $rawData = [
            'weather' => [
                'precipitation' => 25,
                'wind_speed' => 65,
                'weather_code' => 95,
            ],

            'inflation' => [
                'rate' => $economicData['inflation']['value'] ?? 0,
            ],

            'currency' => [
                'change_percentage' => 8,
            ],

            'news' => [
                'positive_count' => 2,
                'negative_count' => 8,
            ],
        ];

        $weatherScore = $riskScoringService->calculateWeatherScore(
            $rawData['weather']['precipitation'],
            $rawData['weather']['wind_speed'],
            $rawData['weather']['weather_code']
        );

        $inflationScore = $riskScoringService->calculateInflationScore(
            $rawData['inflation']['rate']
        );

        $currencyScore = $riskScoringService->calculateCurrencyScore(
            $rawData['currency']['change_percentage']
        );

        $newsScore = $riskScoringService->calculateNewsScore(
            $rawData['news']['positive_count'],
            $rawData['news']['negative_count']
        );

        $totalScore = $riskScoringService->calculateTotalScore(
            $weatherScore,
            $inflationScore,
            $currencyScore,
            $newsScore
        );

        $weatherLevel = $riskScoringService->determineRiskLevel(
            $weatherScore
        );

        $inflationLevel = $riskScoringService->determineRiskLevel(
            $inflationScore
        );

        $currencyLevel = $riskScoringService->determineRiskLevel(
            $currencyScore
        );

        $newsLevel = $riskScoringService->determineRiskLevel(
            $newsScore
        );

        $riskLevel = $riskScoringService->determineRiskLevel(
            $totalScore
        );

        return view('dashboard', [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,

            'economicData' => $economicData,
            'hasEconomicData' => $this->hasEconomicData($economicData),

            'rawData' => $rawData,

            'weatherScore' => $weatherScore,
            'inflationScore' => $inflationScore,
            'currencyScore' => $currencyScore,
            'newsScore' => $newsScore,

            'weatherLevel' => $weatherLevel,
            'inflationLevel' => $inflationLevel,
            'currencyLevel' => $currencyLevel,
            'newsLevel' => $newsLevel,

            'totalScore' => $totalScore,
            'riskLevel' => $riskLevel,
        ]);
    }

    private function getEconomicData(
        Country $country
    ): array {
        $indicators = EconomicIndicator::query()
            ->where('country_id', $country->id)
            ->whereIn(
                'indicator_code',
                array_values(self::INDICATOR_CODES)
            )
            ->latestAvailable()
            ->get()
            ->unique('indicator_code')
            ->keyBy('indicator_code');

        $economicData = [];

        foreach (self::INDICATOR_CODES as $key => $code) {
            $indicator = $indicators->get($code);

            $economicData[$key] = [
                'code' => $code,
                'value' => $indicator
                    ? (float) $indicator->value
                    : null,
                'year' => $indicator?->year,
                'source' => $indicator?->source,
                'fetched_at' => $indicator?->fetched_at,
            ];
        }

        return $economicData;
    }

    private function hasEconomicData(
        array $economicData
    ): bool {
        foreach ($economicData as $indicator) {
            if ($indicator['value'] !== null) {
                return true;
            }
        }

        return false;
    }
}