<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\Country;
use App\Models\EconomicIndicator;
use App\Models\ExchangeRate;
use App\Models\NewsCache;
use App\Models\RiskScore;
use App\Models\User;
use App\Models\Watchlist;
use App\Models\WeatherData;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users_total' => User::count(),
            'admins_total' => User::where('role', User::ROLE_ADMIN)->count(),
            'active_users' => User::where('status', true)->count(),

            'countries_total' => Country::count(),

            'economic_indicators_total' => EconomicIndicator::count(),
            'weather_records_total' => WeatherData::count(),
            'exchange_rates_total' => ExchangeRate::count(),
            'news_cache_total' => NewsCache::count(),
            'risk_scores_total' => RiskScore::count(),

            'watchlists_total' => Watchlist::count(),
            'api_logs_total' => ApiLog::count(),

            'last_economic_sync' => EconomicIndicator::query()
                ->latest('fetched_at')
                ->value('fetched_at'),

            'last_weather_sync' => WeatherData::query()
                ->latest('fetched_at')
                ->value('fetched_at'),

            'last_exchange_sync' => ExchangeRate::query()
                ->latest('fetched_at')
                ->value('fetched_at'),

            'last_news_sync' => NewsCache::query()
                ->latest('fetched_at')
                ->value('fetched_at'),

            'last_api_log' => ApiLog::query()
                ->latest('created_at')
                ->value('created_at'),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}