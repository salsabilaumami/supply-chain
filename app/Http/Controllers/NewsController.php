<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\NewsCache;
use App\Services\NewsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class NewsController extends Controller
{
    public function index(
        Request $request,
        NewsService $newsService
    ): View {
        return view('news.index', $this->buildNewsData(
            $request,
            $newsService
        ));
    }

    public function show(
        Request $request,
        NewsService $newsService
    ): JsonResponse {
        $data = $this->buildNewsData(
            $request,
            $newsService
        );

        return response()->json([
            'success' => true,
            'message' => 'Data intelijen berita berhasil dimuat.',
            'selected_country' => $data['selectedCountry']
                ? $this->formatCountry($data['selectedCountry'])
                : null,
            'summary' => $data['summary'],
            'news' => $data['newsItems'],
            'chart_data' => $data['chartData'],
            'api_error' => $data['apiError'],
        ]);
    }

    private function buildNewsData(
        Request $request,
        NewsService $newsService
    ): array {
        $countries = Country::query()
            ->orderBy('name')
            ->get();

        $selectedCountry = $this->resolveSelectedCountry(
            $request,
            $countries
        );

        $apiError = null;

        $newsItems = $selectedCountry
            ? $this->getNewsItems($selectedCountry)
            : collect();

        $shouldRefresh = $selectedCountry
            && (
                $request->boolean('refresh')
                || $newsItems->isEmpty()
            );

        if ($shouldRefresh) {
            try {
                $newsService->getLatestNews(
                    $selectedCountry,
                    true
                );

                $newsItems = $this->getNewsItems($selectedCountry);
            } catch (Throwable $exception) {
                $apiError = $exception->getMessage();
            }
        }

        $summary = $this->buildSummary($newsItems);

        return [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'newsItems' => $newsItems,
            'summary' => $summary,
            'chartData' => $this->buildChartData($summary),
            'apiError' => $apiError,
        ];
    }

    private function resolveSelectedCountry(
        Request $request,
        Collection $countries
    ): ?Country {
        if ($countries->isEmpty()) {
            return null;
        }

        $selectedIsoCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $selectedCountry = Country::query()
            ->where('iso3_code', $selectedIsoCode)
            ->orWhere('iso2_code', $selectedIsoCode)
            ->first();

        if ($selectedCountry) {
            return $selectedCountry;
        }

        $indonesia = Country::query()
            ->where('iso3_code', 'IDN')
            ->first();

        return $indonesia ?: $countries->first();
    }

    private function getNewsItems(Country $country): Collection
    {
        return NewsCache::query()
            ->with('sentiment')
            ->where('country_id', $country->id)
            ->latest('published_at')
            ->limit(12)
            ->get()
            ->map(fn (NewsCache $news) => $this->formatNews($news))
            ->values();
    }

    private function buildSummary(Collection $newsItems): array
    {
        $positiveCount = $newsItems
            ->where('sentiment', 'positive')
            ->count();

        $neutralCount = $newsItems
            ->where('sentiment', 'neutral')
            ->count();

        $negativeCount = $newsItems
            ->where('sentiment', 'negative')
            ->count();

        $averageRisk = $newsItems->isNotEmpty()
            ? round((float) $newsItems->avg('risk_score'), 2)
            : 0.0;

        return [
            'total_articles' => $newsItems->count(),
            'positive_count' => $positiveCount,
            'neutral_count' => $neutralCount,
            'negative_count' => $negativeCount,
            'average_risk_score' => $averageRisk,
            'risk_label' => $this->riskScoreLabel($averageRisk),
        ];
    }

    private function buildChartData(array $summary): array
    {
        return [
            'sentiment' => [
                'labels' => [
                    'Positif',
                    'Netral',
                    'Negatif',
                ],
                'values' => [
                    $summary['positive_count'] ?? 0,
                    $summary['neutral_count'] ?? 0,
                    $summary['negative_count'] ?? 0,
                ],
            ],
        ];
    }

    private function formatNews(NewsCache $news): array
    {
        $sentiment = $news->sentiment?->sentiment ?? 'neutral';

        $riskScore = $news->sentiment
            ? round((float) $news->sentiment->risk_score, 2)
            : 50.0;

        return [
            'id' => $news->id,
            'title' => $news->title,
            'description' => $news->description,
            'url' => $news->url,
            'image_url' => $news->image_url ?? null,
            'source_name' => $news->source_name,
            'author' => $news->author ?? null,
            'published_at' => $news->published_at
                ? $news->published_at->format('d M Y H:i')
                : null,
            'sentiment' => $sentiment,
            'sentiment_label' => $this->sentimentLabel($sentiment),
            'positive_score' => $news->sentiment?->positive_score ?? 0,
            'negative_score' => $news->sentiment?->negative_score ?? 0,
            'neutral_score' => $news->sentiment?->neutral_score ?? 0,
            'risk_score' => $riskScore,
        ];
    }

    private function riskScoreLabel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'Risiko Kritis',
            $score >= 50 => 'Risiko Tinggi',
            $score >= 25 => 'Risiko Sedang',
            default => 'Risiko Rendah',
        };
    }

    private function sentimentLabel(?string $sentiment): string
    {
        return match ($sentiment) {
            'positive' => 'Positif',
            'negative' => 'Negatif',
            'neutral' => 'Netral',
            default => 'Netral',
        };
    }

    private function formatCountry(Country $country): array
    {
        return [
            'id' => $country->id,
            'name' => $country->name,
            'official_name' => $country->official_name,
            'iso2_code' => $country->iso2_code,
            'iso3_code' => $country->iso3_code,
            'capital' => $country->capital,
            'region' => $country->region,
            'subregion' => $country->subregion,
            'flag_url' => $country->flag_url,
        ];
    }
}