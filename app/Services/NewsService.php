<?php

namespace App\Services;

use App\Models\Country;
use App\Models\NewsCache;
use App\Models\NewsSentiment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class NewsService
{
    private string $endpoint = 'https://gnews.io/api/v4/search';

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
        $apiKey = config('services.gnews.api_key')
            ?: env('GNEWS_API_KEY');

        if (!$apiKey) {
            throw new RuntimeException('API key GNews belum tersedia di .env.');
        }

        $query = $this->buildSafeQuery($country);

        $response = Http::timeout(30)
            ->retry(1, 1000)
            ->get($this->endpoint, [
                'q' => $query,
                'lang' => 'en',
                'country' => strtolower((string) $country->iso2_code),
                'max' => 10,
                'apikey' => $apiKey,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                'GNews HTTP ' . $response->status() . ': ' . $response->body()
            );
        }

        return collect($response->json('articles', []));
    }

    private function buildSafeQuery(Country $country): string
    {
        $countryName = trim((string) $country->name);

        $query = implode(' ', array_filter([
            'supply chain logistics trade economy import export',
            $countryName,
        ]));

        return Str::limit($query, 180, '');
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

        $negativeKeywords = [
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
        ];

        $positiveKeywords = [
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
        ];

        $negativeScore = $this->countKeywordHits(
            $text,
            $negativeKeywords
        );

        $positiveScore = $this->countKeywordHits(
            $text,
            $positiveKeywords
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

    private function countKeywordHits(
        string $text,
        array $keywords
    ): int {
        $score = 0;

        foreach ($keywords as $keyword) {
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
            ->limit(10)
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