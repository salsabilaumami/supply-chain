<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\CountryMonitoringController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.store');

    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register'])
        ->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', 'App\Http\Controllers\DashboardController@index')
        ->name('dashboard');

    Route::get('/api/dashboard', 'App\Http\Controllers\DashboardController@show')
        ->name('api.dashboard.show');

    Route::get('/api/risk', 'App\Http\Controllers\DashboardController@show')
        ->name('api.risk.show');

    Route::get('/countries', [CountryMonitoringController::class, 'index'])
        ->name('countries.index');

    Route::post('/countries/sync-all', [CountryMonitoringController::class, 'syncAll'])
        ->name('countries.sync-all');

    Route::post('/countries/sync-economic', [CountryMonitoringController::class, 'syncEconomic'])
        ->name('countries.sync-economic');

    Route::post('/countries/sync-weather', [CountryMonitoringController::class, 'syncWeather'])
        ->name('countries.sync-weather');

    Route::post('/countries/sync-currency', [CountryMonitoringController::class, 'syncCurrency'])
        ->name('countries.sync-currency');

    Route::post('/countries/sync-news', [CountryMonitoringController::class, 'syncNews'])
        ->name('countries.sync-news');

    Route::post('/countries/calculate-risk', [CountryMonitoringController::class, 'calculateRisk'])
        ->name('countries.calculate-risk');

    Route::get('/api/countries', [CountryMonitoringController::class, 'show'])
        ->name('api.countries.show');

    Route::get('/weather', [WeatherController::class, 'index'])
        ->name('weather.index');

    Route::get('/api/weather', [WeatherController::class, 'show'])
        ->name('api.weather.show');

    Route::get('/currency', [CurrencyController::class, 'index'])
        ->name('currency.index');

    Route::get('/api/currency', [CurrencyController::class, 'show'])
        ->name('api.currency.show');

    Route::get('/news', [NewsController::class, 'index'])
        ->name('news.index');

    Route::get('/api/news', [NewsController::class, 'show'])
        ->name('api.news.show');

    Route::get('/ports', [PortController::class, 'index'])
        ->name('ports.index');

    Route::get('/api/ports', [PortController::class, 'show'])
        ->name('api.ports.show');

    Route::get('/comparison', [ComparisonController::class, 'index'])
        ->name('comparison.index');

    Route::get('/api/comparison', [ComparisonController::class, 'show'])
        ->name('api.comparison.show');

    Route::get('/watchlist', [WatchlistController::class, 'index'])
        ->name('watchlist.index');

    Route::get('/api/watchlist', [WatchlistController::class, 'show'])
        ->name('api.watchlist.show');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/admin', [AdminDashboardController::class, 'index'])
            ->name('admin.dashboard');
    });
});