<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\NewsCache;
use App\Services\NewsService;
use Carbon\Carbon;
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
            'message' => 'Data news intelligence berhasil dimuat.',
            'selected_country' => [
                'id' => $data['selectedCountry']->id,
                'name' => $data['selectedCountry']->name,
                'iso2_code' => $data['selectedCountry']->iso2_code,
                'iso3_code' => $data['selectedCountry']->iso3_code,
                'flag_url' => $data['selectedCountry']->flag_url,
            ],
            'selected_category' => $data['selectedCategory'],
            'summary' => $data['summary'],
            'category_counts' => $data['categoryCounts'],
            'articles' => $data['articles'],
            'chart_data' => $data['chartData'],
        ]);
    }

    private function buildNewsData(
        Request $request,
        NewsService $newsService
    ): array {
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
                ->first();
        }

        if (!$selectedCountry) {
            $selectedCountry = $countries->firstOrFail();
        }

        $categories = $newsService->getCategories();
        $selectedCategory = $this->normalizeCategory(
            $request->string('category', 'all')->toString(),
            $categories
        );

        $forceRefresh = $request->boolean('refresh');
        $apiError = null;

        try {
            $newsItems = $newsService->getLatestNews(
                $selectedCountry,
                $forceRefresh,
                $selectedCategory
            );
        } catch (Throwable $exception) {
            $newsItems = collect();
            $apiError = $exception->getMessage();
        }

        $articles = $this->formatArticles(
            $newsItems,
            $newsService,
            $categories
        );

        $categoryCounts = $this->buildCategoryCounts(
            $articles,
            $categories
        );

        $summary = $this->buildSummary($articles);

        $chartData = $this->buildChartData(
            $summary,
            $categoryCounts
        );

        return [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'articles' => $articles,
            'summary' => $summary,
            'categoryCounts' => $categoryCounts,
            'chartData' => $chartData,
            'apiError' => $apiError,
        ];
    }

    private function formatArticles(
        Collection $newsItems,
        NewsService $newsService,
        array $categories
    ): array {
        return $newsItems
            ->map(function (NewsCache $news) use ($newsService, $categories) {
                $category = $newsService->classifyArticle($news);
                $sentiment = $news->sentiment?->sentiment ?? 'neutral';
                $riskScore = round((float) ($news->sentiment?->risk_score ?? 35), 2);

                return [
                    'id' => $news->id,
                    'title' => $news->title ?? 'Judul tidak tersedia',
                    'description' => $news->description ?? 'Deskripsi berita belum tersedia.',
                    'url' => $news->url,
                    'source_name' => $news->source_name ?? 'GNews',
                    'image_url' => $news->image_url ?? null,
                    'published_at' => $this->formatDate($news->published_at),
                    'category' => $category,
                    'category_label' => $categories[$category] ?? 'Economy',
                    'sentiment' => $sentiment,
                    'sentiment_label' => $this->sentimentLabel($sentiment),
                    'sentiment_class' => $this->sentimentClass($sentiment),
                    'risk_score' => $riskScore,
                    'risk_label' => $this->riskLabel($riskScore),
                    'risk_class' => $this->riskClass($riskScore),
                    'positive_score' => (int) ($news->sentiment?->positive_score ?? 0),
                    'negative_score' => (int) ($news->sentiment?->negative_score ?? 0),
                    'neutral_score' => (int) ($news->sentiment?->neutral_score ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function buildSummary(array $articles): array
    {
        $total = count($articles);

        $positive = collect($articles)
            ->where('sentiment', 'positive')
            ->count();

        $neutral = collect($articles)
            ->where('sentiment', 'neutral')
            ->count();

        $negative = collect($articles)
            ->where('sentiment', 'negative')
            ->count();

        $averageRisk = $total > 0
            ? round(collect($articles)->avg('risk_score'), 2)
            : 0.0;

        $dominantSentiment = $this->dominantSentiment(
            $positive,
            $neutral,
            $negative
        );

        return [
            'total' => $total,
            'positive' => $positive,
            'neutral' => $neutral,
            'negative' => $negative,
            'average_risk' => $averageRisk,
            'average_risk_label' => $this->riskLabel($averageRisk),
            'average_risk_class' => $this->riskClass($averageRisk),
            'dominant_sentiment' => $dominantSentiment,
            'dominant_sentiment_label' => $this->sentimentLabel($dominantSentiment),
            'dominant_sentiment_class' => $this->sentimentClass($dominantSentiment),
            'recommendation' => $this->buildRecommendation(
                $averageRisk,
                $negative,
                $total
            ),
        ];
    }

    private function buildCategoryCounts(
        array $articles,
        array $categories
    ): array {
        $counts = [];

        foreach ($categories as $key => $label) {
            if ($key === 'all') {
                continue;
            }

            $counts[$key] = [
                'label' => $label,
                'count' => collect($articles)
                    ->where('category', $key)
                    ->count(),
            ];
        }

        return $counts;
    }

    private function buildChartData(
        array $summary,
        array $categoryCounts
    ): array {
        return [
            'sentiment' => [
                'labels' => ['Positive', 'Neutral', 'Negative'],
                'values' => [
                    $summary['positive'],
                    $summary['neutral'],
                    $summary['negative'],
                ],
            ],
            'category' => [
                'labels' => collect($categoryCounts)
                    ->pluck('label')
                    ->values()
                    ->all(),
                'values' => collect($categoryCounts)
                    ->pluck('count')
                    ->values()
                    ->all(),
            ],
        ];
    }

    private function buildRecommendation(
        float $averageRisk,
        int $negative,
        int $total
    ): string {
        if ($total === 0) {
            return 'Belum ada berita yang dapat dianalisis untuk negara terpilih.';
        }

        if ($averageRisk >= 75 || $negative >= 5) {
            return 'Risiko berita tinggi. Pantau isu geopolitik, gangguan logistik, dan kondisi perdagangan sebelum mengambil keputusan supply chain.';
        }

        if ($averageRisk >= 50 || $negative >= 3) {
            return 'Risiko berita sedang menuju tinggi. Perlu monitoring lanjutan terhadap perubahan ekonomi, logistik, dan kebijakan perdagangan.';
        }

        if ($averageRisk >= 25) {
            return 'Risiko berita masih terkendali, tetapi perkembangan terbaru tetap perlu dipantau secara berkala.';
        }

        return 'Sentimen berita relatif aman dan belum menunjukkan gangguan besar terhadap aktivitas supply chain.';
    }

    private function dominantSentiment(
        int $positive,
        int $neutral,
        int $negative
    ): string {
        $values = [
            'positive' => $positive,
            'neutral' => $neutral,
            'negative' => $negative,
        ];

        arsort($values);

        return array_key_first($values) ?? 'neutral';
    }

    private function normalizeCategory(
        string $category,
        array $categories
    ): string {
        $category = strtolower(trim($category));

        if (!array_key_exists($category, $categories)) {
            return 'all';
        }

        return $category;
    }

    private function formatDate(mixed $date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('d M Y H:i');
        } catch (Throwable) {
            return null;
        }
    }

    private function sentimentLabel(string $sentiment): string
    {
        return match ($sentiment) {
            'positive' => 'Positive',
            'negative' => 'Negative',
            default => 'Neutral',
        };
    }

    private function sentimentClass(string $sentiment): string
    {
        return match ($sentiment) {
            'positive' => 'sentiment-positive',
            'negative' => 'sentiment-negative',
            default => 'sentiment-neutral',
        };
    }

    private function riskLabel(float $score): string
    {
        return match (true) {
            $score >= 75 => 'Critical Risk',
            $score >= 50 => 'High Risk',
            $score >= 25 => 'Medium Risk',
            default => 'Low Risk',
        };
    }

    private function riskClass(float $score): string
    {
        return match (true) {
            $score >= 75 => 'risk-critical',
            $score >= 50 => 'risk-high',
            $score >= 25 => 'risk-medium',
            default => 'risk-low',
        };
    }
}