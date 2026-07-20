<?php

namespace App\Services;

use App\Models\Country;
use App\Models\NegativeWord;
use App\Models\NewsCache;
use App\Models\NewsSentiment;
use App\Models\PositiveWord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class NewsService
{
    private const MAX_ARTICLES = 12;

    private const RECENT_DAYS = 10;

    private const CATEGORIES = [
        'all' => [
            'label' => 'Semua Berita',
            'terms' => [
                'supply chain',
                'logistics',
                'trade',
                'economy',
                'export',
                'import',
                'shipping',
                'port',
                'cargo',
                'inflation',
            ],
        ],
        'logistics' => [
            'label' => 'Logistics',
            'terms' => [
                'logistics',
                'supply chain',
                'distribution',
                'warehouse',
                'freight',
                'cargo',
                'delivery',
                'transportation',
            ],
        ],
        'trade' => [
            'label' => 'Trade',
            'terms' => [
                'trade',
                'export',
                'import',
                'tariff',
                'customs',
                'trade deal',
                'trade war',
                'restriction',
            ],
        ],
        'shipping' => [
            'label' => 'Shipping',
            'terms' => [
                'shipping',
                'port',
                'vessel',
                'container',
                'cargo ship',
                'maritime',
                'port congestion',
                'shipment',
            ],
        ],
        'economy' => [
            'label' => 'Economy',
            'terms' => [
                'economy',
                'inflation',
                'market',
                'business',
                'investment',
                'recession',
                'growth',
                'currency',
            ],
        ],
        'geopolitical' => [
            'label' => 'Geopolitical',
            'terms' => [
                'geopolitical',
                'conflict',
                'war',
                'sanction',
                'tension',
                'border',
                'crisis',
                'political risk',
            ],
        ],
    ];

    public function getLatestNews(
        Country $country,
        bool $forceRefresh = false,
        ?string $category = null
    ): Collection {
        $category = $this->normalizeCategory($category);

        if (!$forceRefresh) {
            $cachedNews = $this->getCachedNews($country);

            if ($cachedNews->isNotEmpty()) {
                $filteredNews = $this->filterByCategory(
                    $cachedNews,
                    $category
                );

                if ($category === 'all' || $filteredNews->count() >= 4) {
                    return $filteredNews;
                }
            }
        }

        try {
            $articles = $this->fetchFromGNews(
                $country,
                $category
            );

            if ($articles->isEmpty()) {
                return $this->filterByCategory(
                    $this->getCachedNews($country),
                    $category
                );
            }

            $storedArticles = $this->storeArticles(
                $country,
                $articles
            );

            return $this->filterByCategory(
                $storedArticles,
                $category
            );
        } catch (Throwable $exception) {
            $cachedNews = $this->filterByCategory(
                $this->getCachedNews($country),
                $category
            );

            if ($cachedNews->isNotEmpty()) {
                return $cachedNews;
            }

            throw new RuntimeException(
                'Gagal mengambil berita dari GNews: ' . $exception->getMessage()
            );
        }
    }

    public function getCategories(): array
    {
        return collect(self::CATEGORIES)
            ->map(fn (array $category) => $category['label'])
            ->all();
    }

    public function classifyArticle(NewsCache $news): string
    {
        $text = $this->buildArticleText($news);

        $bestCategory = 'economy';
        $bestScore = 0;

        foreach (self::CATEGORIES as $key => $category) {
            if ($key === 'all') {
                continue;
            }

            $score = 0;

            foreach ($category['terms'] as $term) {
                if (str_contains($text, Str::lower($term))) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCategory = $key;
            }
        }

        return $bestCategory;
    }

    public function categoryLabel(string $category): string
    {
        return self::CATEGORIES[$category]['label']
            ?? self::CATEGORIES['economy']['label'];
    }

    private function fetchFromGNews(
        Country $country,
        string $category = 'all'
    ): Collection {
        $apiKey = config('services.gnews.api_key') ?: env('GNEWS_API_KEY');

        if (!$apiKey) {
            throw new RuntimeException('API key GNews belum tersedia di .env.');
        }

        $baseUrl = rtrim(
            (string) config('services.gnews.base_url', 'https://gnews.io/api/v4'),
            '/'
        );

        $endpoint = $baseUrl . '/search';

        $queries = $this->buildSearchQueries(
            $country,
            $category
        );

        $articles = collect();

        foreach ($queries as $query) {
            $response = Http::timeout(30)
                ->retry(1, 1000)
                ->acceptJson()
                ->get($endpoint, [
                    'q' => $query,
                    'lang' => 'en',
                    'max' => self::MAX_ARTICLES,
                    'in' => 'title,description,content',
                    'from' => now()->subDays(self::RECENT_DAYS)->utc()->format('Y-m-d\TH:i:s\Z'),
                    'to' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
                    'sortby' => 'publishedAt',
                    'nullable' => 'description,content,image',
                    'apikey' => $apiKey,
                ]);

            if (!$response->successful()) {
                throw new RuntimeException(
                    'GNews HTTP ' . $response->status() . ': ' . $response->body()
                );
            }

            $currentArticles = collect($response->json('articles', []));

            $articles = $articles->merge($currentArticles);

            if ($articles->count() >= self::MAX_ARTICLES) {
                break;
            }
        }

        return $articles
            ->filter(fn ($article) => !empty($article['url']))
            ->unique(fn ($article) => $article['url'])
            ->sortByDesc(fn ($article) => $article['publishedAt'] ?? '')
            ->take(self::MAX_ARTICLES)
            ->values();
    }

    private function buildSearchQueries(
        Country $country,
        string $category = 'all'
    ): array {
        $countryName = trim((string) $country->name);
        $countryName = str_replace('"', '', $countryName);

        if ($countryName === '') {
            $countryName = 'global';
        }

        $category = $this->normalizeCategory($category);
        $terms = self::CATEGORIES[$category]['terms'] ?? self::CATEGORIES['all']['terms'];

        $primaryTerms = collect($terms)
            ->take(6)
            ->map(fn (string $term) => $this->formatSearchTerm($term))
            ->implode(' OR ');

        $secondaryTerms = collect(self::CATEGORIES['all']['terms'])
            ->take(6)
            ->map(fn (string $term) => $this->formatSearchTerm($term))
            ->implode(' OR ');

        return [
            Str::limit(
                '"' . $countryName . '" AND (' . $primaryTerms . ')',
                190,
                ''
            ),
            Str::limit(
                '"' . $countryName . '" AND (' . $secondaryTerms . ')',
                190,
                ''
            ),
            Str::limit(
                '"' . $countryName . '" AND (business OR market OR port OR cargo OR inflation OR investment)',
                190,
                ''
            ),
        ];
    }

    private function formatSearchTerm(string $term): string
    {
        $term = trim($term);

        if (str_contains($term, ' ')) {
            return '"' . str_replace('"', '', $term) . '"';
        }

        return $term;
    }

    private function storeArticles(
        Country $country,
        Collection $articles
    ): Collection {
        $stored = collect();

        $hasImageColumn = Schema::hasColumn('news_caches', 'image_url');
        $hasAuthorColumn = Schema::hasColumn('news_caches', 'author');
        $hasContentColumn = Schema::hasColumn('news_caches', 'content');
        $hasRawPayloadColumn = Schema::hasColumn('news_caches', 'raw_payload');
        $hasFetchedAtColumn = Schema::hasColumn('news_caches', 'fetched_at');

        foreach ($articles as $article) {
            $url = $article['url'] ?? null;

            if (!$url) {
                continue;
            }

            $news = NewsCache::query()
                ->firstOrNew([
                    'url' => $url,
                ]);

            $news->country_id = $country->id;
            $news->title = $article['title'] ?? 'Judul tidak tersedia';
            $news->description = $article['description'] ?? null;
            $news->source_name = data_get($article, 'source.name', 'GNews');
            $news->published_at = $this->parseDate($article['publishedAt'] ?? null);

            if ($hasImageColumn) {
                $news->image_url = $article['image'] ?? null;
            }

            if ($hasAuthorColumn) {
                $news->author = data_get($article, 'source.name');
            }

            if ($hasContentColumn) {
                $news->content = $article['content'] ?? null;
            }

            if ($hasRawPayloadColumn) {
                $news->raw_payload = $article;
            }

            if ($hasFetchedAtColumn) {
                $news->fetched_at = now();
            }

            $news->save();

            $this->storeSentiment($news);

            $stored->push(
                $news->fresh('sentiment')
            );
        }

        return $stored
            ->filter()
            ->sortByDesc('published_at')
            ->values();
    }

    private function storeSentiment(NewsCache $news): void
    {
        $analysis = $this->analyzeArticle($news);

        $sentiment = NewsSentiment::query()
            ->firstOrNew([
                'news_cache_id' => $news->id,
            ]);

        $sentiment->sentiment = $analysis['sentiment'];
        $sentiment->positive_score = $analysis['positive_score'];
        $sentiment->negative_score = $analysis['negative_score'];
        $sentiment->neutral_score = $analysis['neutral_score'];
        $sentiment->risk_score = $analysis['risk_score'];
        $sentiment->analyzed_at = now();
        $sentiment->save();
    }

    private function analyzeArticle(NewsCache $news): array
    {
        $text = $this->buildArticleText($news);

        $positiveKeywords = $this->getPositiveKeywords();
        $negativeKeywords = $this->getNegativeKeywords();

        $positiveScore = $this->countKeywordHits(
            $text,
            $positiveKeywords
        );

        $negativeScore = $this->countKeywordHits(
            $text,
            $negativeKeywords
        );

        $neutralScore = max(1, 10 - abs($negativeScore - $positiveScore));

        $sentiment = match (true) {
            $negativeScore > $positiveScore => 'negative',
            $positiveScore > $negativeScore => 'positive',
            default => 'neutral',
        };

        $riskScore = match ($sentiment) {
            'negative' => min(95, 45 + ($negativeScore * 8)),
            'positive' => max(10, 35 - ($positiveScore * 4)),
            default => 35,
        };

        return [
            'sentiment' => $sentiment,
            'positive_score' => $positiveScore,
            'negative_score' => $negativeScore,
            'neutral_score' => $neutralScore,
            'risk_score' => round((float) $riskScore, 2),
        ];
    }

    private function buildArticleText(NewsCache $news): string
    {
        return Str::lower(
            trim(
                ($news->title ?? '')
                . ' '
                . ($news->description ?? '')
                . ' '
                . ($news->content ?? '')
                . ' '
                . ($news->source_name ?? '')
            )
        );
    }

    private function filterByCategory(
        Collection $news,
        string $category
    ): Collection {
        if ($category === 'all') {
            return $news->values();
        }

        return $news
            ->filter(fn (NewsCache $item) => $this->classifyArticle($item) === $category)
            ->values();
    }

    private function getPositiveKeywords(): array
    {
        if (Schema::hasTable('positive_words')) {
            $words = PositiveWord::query()
                ->where('language', 'en')
                ->orderBy('word')
                ->pluck('word')
                ->filter()
                ->map(fn ($word) => Str::lower(trim((string) $word)))
                ->values()
                ->all();

            if (!empty($words)) {
                return $words;
            }
        }

        return [
            'growth',
            'increase',
            'recovery',
            'agreement',
            'investment',
            'expansion',
            'improve',
            'stable',
            'partnership',
            'profit',
            'boost',
            'surplus',
            'resilient',
            'strong',
            'cooperation',
            'trade deal',
            'supply growth',
            'logistics improvement',
            'export growth',
            'market recovery',
        ];
    }

    private function getNegativeKeywords(): array
    {
        if (Schema::hasTable('negative_words')) {
            $words = NegativeWord::query()
                ->where('language', 'en')
                ->orderBy('word')
                ->pluck('word')
                ->filter()
                ->map(fn ($word) => Str::lower(trim((string) $word)))
                ->values()
                ->all();

            if (!empty($words)) {
                return $words;
            }
        }

        return [
            'crisis',
            'conflict',
            'war',
            'strike',
            'delay',
            'disruption',
            'shortage',
            'inflation',
            'recession',
            'sanction',
            'tariff',
            'risk',
            'port congestion',
            'flood',
            'storm',
            'earthquake',
            'attack',
            'tension',
            'decline',
            'shipment delay',
            'supply disruption',
            'trade war',
            'logistics crisis',
            'export ban',
            'import restriction',
        ];
    }

    private function countKeywordHits(
        string $text,
        array $keywords
    ): int {
        $score = 0;

        foreach ($keywords as $keyword) {
            $keyword = Str::lower(trim((string) $keyword));

            if ($keyword === '') {
                continue;
            }

            if (str_contains($text, $keyword)) {
                $score++;
            }
        }

        return $score;
    }

    private function getCachedNews(Country $country): Collection
    {
        return NewsCache::query()
            ->with('sentiment')
            ->where('country_id', $country->id)
            ->latest('published_at')
            ->limit(self::MAX_ARTICLES)
            ->get();
    }

    private function parseDate(?string $date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeCategory(?string $category): string
    {
        $category = Str::lower(trim((string) $category));

        if (!array_key_exists($category, self::CATEGORIES)) {
            return 'all';
        }

        return $category;
    }
}