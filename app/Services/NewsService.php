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
    private const MAX_ARTICLES = 10;

    private const RECENT_DAYS = 7;

    public function getLatestNews(
        Country $country,
        bool $forceRefresh = false
    ): Collection {
        if (!$forceRefresh) {
            $cachedNews = $this->getCachedNews($country);

            if ($cachedNews->isNotEmpty()) {
                return $cachedNews;
            }
        }

        try {
            $articles = $this->fetchFromGNews($country);

            if ($articles->isEmpty()) {
                return $this->getCachedNews($country);
            }

            return $this->storeArticles(
                $country,
                $articles
            );
        } catch (Throwable $exception) {
            $cachedNews = $this->getCachedNews($country);

            if ($cachedNews->isNotEmpty()) {
                return $cachedNews;
            }

            throw new RuntimeException(
                'Gagal mengambil berita dari GNews: ' . $exception->getMessage()
            );
        }
    }

    private function fetchFromGNews(Country $country): Collection
    {
        $apiKey = config('services.gnews.api_key') ?: env('GNEWS_API_KEY');

        if (!$apiKey) {
            throw new RuntimeException('API key GNews belum tersedia di .env.');
        }

        $baseUrl = rtrim(
            (string) config('services.gnews.base_url', 'https://gnews.io/api/v4'),
            '/'
        );

        $endpoint = $baseUrl . '/search';

        $queries = $this->buildSearchQueries($country);
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

    private function buildSearchQueries(Country $country): array
    {
        $countryName = trim((string) $country->name);
        $countryName = str_replace('"', '', $countryName);

        if ($countryName === '') {
            $countryName = 'global';
        }

        return [
            Str::limit(
                '"' . $countryName . '" AND (supply chain OR logistics OR trade OR economy OR export OR import OR shipping)',
                190,
                ''
            ),
            Str::limit(
                '"' . $countryName . '" AND (business OR market OR port OR cargo OR inflation OR investment)',
                190,
                ''
            ),
            Str::limit(
                '"' . $countryName . '" AND (trade OR economy OR logistics)',
                190,
                ''
            ),
        ];
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
        $text = Str::lower(
            trim(
                ($news->title ?? '')
                . ' '
                . ($news->description ?? '')
                . ' '
                . ($news->content ?? '')
            )
        );

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
            'negative' => min(90, 45 + ($negativeScore * 8)),
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
}