<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Services\NewsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class NewsController extends Controller
{
    public function index(
        Request $request,
        NewsService $newsService
    ): View {
        $countries = Country::query()
            ->orderBy('name')
            ->get();

        $selectedIsoCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $selectedCountry = Country::query()
            ->where(function ($query) use ($selectedIsoCode) {
                $query->where('iso3_code', $selectedIsoCode)
                    ->orWhere('iso2_code', $selectedIsoCode);
            })
            ->first();

        if (!$selectedCountry) {
            $selectedCountry = Country::query()
                ->where('iso3_code', 'IDN')
                ->firstOrFail();
        }

        $news = collect();
        $errorMessage = null;

        try {
            $news = $newsService->getLatestNews(
                $selectedCountry,
                $request->boolean('refresh')
            );
        } catch (Throwable $exception) {
            $errorMessage = $exception->getMessage();
        }

        return view('news.index', [
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'news' => $news,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function show(
        Request $request,
        NewsService $newsService
    ): JsonResponse {
        $countryCode = strtoupper(
            trim($request->string('country', 'IDN')->toString())
        );

        $country = Country::query()
            ->where(function ($query) use ($countryCode) {
                $query->where('iso3_code', $countryCode)
                    ->orWhere('iso2_code', $countryCode);
            })
            ->firstOrFail();

        $news = $newsService->getLatestNews(
            $country,
            $request->boolean('refresh')
        );

        return response()->json([
            'country' => [
                'id' => $country->id,
                'name' => $country->name,
                'iso2_code' => $country->iso2_code,
                'iso3_code' => $country->iso3_code,
            ],
            'articles' => $news->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'url' => $item->url,
                    'source_name' => $item->source_name,
                    'image_url' => $item->image_url,
                    'published_at' => $item->published_at,
                    'sentiment' => $item->sentiment ? [
                        'positive_score' => (int) $item->sentiment->positive_score,
                        'negative_score' => (int) $item->sentiment->negative_score,
                        'neutral_score' => (int) $item->sentiment->neutral_score,
                        'sentiment' => $item->sentiment->sentiment,
                        'risk_score' => (float) $item->sentiment->risk_score,
                        'analyzed_at' => $item->sentiment->analyzed_at,
                    ] : null,
                ];
            })->values(),
        ]);
    }
}