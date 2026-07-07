<?php

namespace App\Services;

use App\Models\Country;
use App\Models\NewsCache;
use App\Models\NewsSentiment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class NewsService
{
    private const CACHE_MINUTES = 60;
    private const MAX_ARTICLES = 10;

    public function __construct(
        private readonly NewsSentimentService $newsSentimentService
    ) {
    }

    public function getLatestNews(
        Country $country,
        bool $forceRefresh = false
    ): Collection {
        if (!$forceRefresh) {
            $cachedNews = $this->getFreshCachedNews($country);

            if ($cachedNews->isNotEmpty()) {
                return $cachedNews;
            }
        }

        return $this->fetchStoreAndAnalyze($country);
    }

    private function getFreshCachedNews(Country $country): Collection
    {
        return NewsCache::query()
            ->with(['country', 'sentiment'])
            ->where('country_id', $country->id)
            ->where('fetched_at', '>=', now()->subMinutes(self::CACHE_MINUTES))
            ->latest('published_at')
            ->limit(self::MAX_ARTICLES)
            ->get();
    }

    private function fetchStoreAndAnalyze(Country $country): Collection
    {
        $apiKey = config('services.gnews.api_key');

        if (
            empty($apiKey) ||
            $apiKey === 'ISI_API_KEY_KAMU_DI_SINI' ||
            $apiKey === 'ISI_API_KEY_GNEWS_KAMU'
        ) {
            throw new RuntimeException(
                'GNews API key belum diisi di file .env.'
            );
        }

        $baseUrl = config(
            'services.gnews.base_url',
            'https://gnews.io/api/v4'
        );

        $response = Http::acceptJson()
            ->timeout(30)
            ->retry(3, 1000)
            ->get(
                rtrim($baseUrl, '/') . '/search',
                [
                    'q' => $this->buildQuery($country),
                    'lang' => 'en',
                    'max' => self::MAX_ARTICLES,
                    'in' => 'title,description,content',
                    'nullable' => 'description,content,image',
                    'apikey' => $apiKey,
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal mengambil berita dari GNews. Status API: '
                . $response->status()
                . '. Response: '
                . $response->body()
            );
        }

        $payload = $response->json();
        $articles = $payload['articles'] ?? [];

        $this->deleteOldNewsForCountry($country);

        $storedNews = collect();

        foreach ($articles as $article) {
            if (empty($article['title']) || empty($article['url'])) {
                continue;
            }

            $newsCache = NewsCache::updateOrCreate(
                [
                    'url' => $article['url'],
                ],
                [
                    'country_id' => $country->id,
                    'title' => $article['title'],
                    'description' => $article['description'] ?? null,
                    'source_name' => $article['source']['name'] ?? null,
                    'author' => $article['source']['url'] ?? null,
                    'image_url' => $article['image'] ?? null,
                    'published_at' => $this->parseDate(
                        $article['publishedAt'] ?? null
                    ),
                    'fetched_at' => now(),
                ]
            );

            $this->newsSentimentService->analyzeNews($newsCache);

            $storedNews->push(
                $newsCache->fresh(['country', 'sentiment'])
            );
        }

        return $storedNews
            ->sortByDesc('published_at')
            ->values();
    }

    private function buildQuery(Country $country): string
    {
        $countryName = trim($country->name);

        $query = '"' . $countryName . '" logistics OR "'
            . $countryName . '" trade OR "'
            . $countryName . '" economy';

        if (strlen($query) > 190) {
            return '"' . $countryName . '" economy';
        }

        return $query;
    }

    private function deleteOldNewsForCountry(Country $country): void
    {
        $newsIds = NewsCache::query()
            ->where('country_id', $country->id)
            ->pluck('id');

        if ($newsIds->isEmpty()) {
            return;
        }

        NewsSentiment::query()
            ->whereIn('news_cache_id', $newsIds)
            ->delete();

        NewsCache::query()
            ->whereIn('id', $newsIds)
            ->delete();
    }

    private function parseDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (Throwable) {
            return null;
        }
    }
}