<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\EconomicIndicator;
use App\Models\ExchangeRate;
use App\Models\GlobalPort;
use App\Models\NewsCache;
use App\Models\RiskScore;
use App\Models\User;
use App\Models\WeatherData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $users = $this->getUsers();
        $latestRisks = $this->getLatestRisks();
        $latestNews = $this->getLatestNews();
        $latestPorts = $this->getLatestPorts();

        return view('admin.dashboard', [
            'stats' => $this->getStats(),
            'datasetCards' => $this->getDatasetCards(),
            'users' => $users,
            'latestRisks' => $latestRisks,
            'latestNews' => $latestNews,
            'latestPorts' => $latestPorts,
            'lastUpdated' => now()->format('d M Y H:i'),
        ]);
    }

    public function updateUserStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        if ($request->user()->id === $user->id && $validated['status'] !== 'active') {
            return back()->with('error', 'Status akun admin yang sedang digunakan tidak boleh dinonaktifkan.');
        }

        $user->status = $validated['status'];
        $user->save();

        return back()->with('success', 'Status pengguna berhasil diperbarui.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'Akun admin yang sedang digunakan tidak boleh dihapus.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    public function destroyPort(GlobalPort $globalPort): RedirectResponse
    {
        $globalPort->delete();

        return back()->with('success', 'Data pelabuhan berhasil dihapus.');
    }

    public function destroyNews(NewsCache $newsCache): RedirectResponse
    {
        $newsCache->delete();

        return back()->with('success', 'Artikel berita berhasil dihapus.');
    }

    private function getStats(): array
    {
        $allUsers = $this->safeCollection(function () {
            return User::query()
                ->orderBy('name')
                ->get();
        });

        $adminCount = $allUsers
            ->filter(fn (User $user) => $this->isAdminUser($user))
            ->count();

        return [
            'total_users' => $allUsers->count(),
            'admin_users' => $adminCount,
            'regular_users' => max(0, $allUsers->count() - $adminCount),
            'countries' => $this->safeCount(Country::class),
            'economic_data' => $this->safeCount(EconomicIndicator::class),
            'weather_data' => $this->safeCount(WeatherData::class),
            'currency_data' => $this->safeCount(ExchangeRate::class),
            'news_data' => $this->safeCount(NewsCache::class),
            'risk_scores' => $this->safeCount(RiskScore::class),
            'ports' => $this->safeCount(GlobalPort::class),
        ];
    }

    private function getDatasetCards(): array
    {
        return [
            [
                'label' => 'Negara',
                'value' => $this->safeCount(Country::class),
                'description' => 'Profil negara tersimpan',
                'icon' => 'bi-globe2',
            ],
            [
                'label' => 'Data Ekonomi',
                'value' => $this->safeCount(EconomicIndicator::class),
                'description' => 'Indikator World Bank',
                'icon' => 'bi-bar-chart-line',
            ],
            [
                'label' => 'Data Cuaca',
                'value' => $this->safeCount(WeatherData::class),
                'description' => 'Rekaman kondisi cuaca',
                'icon' => 'bi-cloud-lightning-rain',
            ],
            [
                'label' => 'Nilai Tukar',
                'value' => $this->safeCount(ExchangeRate::class),
                'description' => 'Rekaman perubahan kurs',
                'icon' => 'bi-currency-exchange',
            ],
            [
                'label' => 'Berita',
                'value' => $this->safeCount(NewsCache::class),
                'description' => 'Artikel berita tersimpan',
                'icon' => 'bi-newspaper',
            ],
            [
                'label' => 'Risk Score',
                'value' => $this->safeCount(RiskScore::class),
                'description' => 'Riwayat perhitungan risiko',
                'icon' => 'bi-shield-exclamation',
            ],
            [
                'label' => 'Pelabuhan',
                'value' => $this->safeCount(GlobalPort::class),
                'description' => 'Data pelabuhan global',
                'icon' => 'bi-geo-alt',
            ],
        ];
    }

    private function getUsers(): Collection
    {
        return $this->safeCollection(function () {
            return User::query()
                ->orderByDesc('created_at')
                ->limit(12)
                ->get()
                ->map(function (User $user) {
                    $isAdmin = $this->isAdminUser($user);
                    $status = $user->status ?: 'active';

                    if (!in_array($status, ['active', 'inactive'], true)) {
                        $status = 'inactive';
                    }

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'raw_role' => $isAdmin ? 'admin' : 'user',
                        'role' => $isAdmin ? 'Administrator' : 'Pengguna',
                        'role_class' => $isAdmin ? 'admin-role-badge' : 'user-role-badge',
                        'status' => $status,
                        'status_label' => $this->statusLabel($status),
                        'status_class' => $this->statusClass($status),
                        'initial' => strtoupper(substr((string) $user->name, 0, 1)),
                        'created_at' => $user->created_at
                            ? $user->created_at->format('d M Y H:i')
                            : '-',
                        'updated_at' => $user->updated_at
                            ? $user->updated_at->format('d M Y H:i')
                            : '-',
                    ];
                });
        });
    }

    private function getLatestRisks(): Collection
    {
        return $this->safeCollection(function () {
            $risks = RiskScore::query()
                ->orderByDesc('calculated_at')
                ->limit(8)
                ->get();

            $countryIds = $risks
                ->pluck('country_id')
                ->filter()
                ->unique()
                ->values();

            $countries = Country::query()
                ->whereIn('id', $countryIds)
                ->get()
                ->keyBy('id');

            return $risks
                ->map(function (RiskScore $risk) use ($countries) {
                    $country = $countries->get($risk->country_id);

                    return [
                        'country_name' => $country?->name ?? 'Negara tidak tersedia',
                        'country_iso3' => $country?->iso3_code ?? '-',
                        'total_score' => round((float) $risk->total_score, 2),
                        'risk_level' => $risk->risk_level,
                        'risk_label' => $this->riskLevelLabel($risk->risk_level),
                        'calculated_at' => $risk->calculated_at
                            ? $risk->calculated_at->format('d M Y H:i')
                            : '-',
                    ];
                });
        });
    }

    private function getLatestNews(): Collection
    {
        return $this->safeCollection(function () {
            $news = NewsCache::query()
                ->orderByDesc('published_at')
                ->limit(8)
                ->get();

            $countryIds = $news
                ->pluck('country_id')
                ->filter()
                ->unique()
                ->values();

            $countries = Country::query()
                ->whereIn('id', $countryIds)
                ->get()
                ->keyBy('id');

            return $news
                ->map(function (NewsCache $item) use ($countries) {
                    $country = $countries->get($item->country_id);

                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'source_name' => $item->source_name,
                        'country_name' => $country?->name ?? '-',
                        'country_iso3' => $country?->iso3_code ?? '-',
                        'image_url' => $item->image_url ?? null,
                        'url' => $item->url,
                        'published_at' => $item->published_at
                            ? $item->published_at->format('d M Y H:i')
                            : '-',
                    ];
                });
        });
    }

    private function getLatestPorts(): Collection
    {
        return $this->safeCollection(function () {
            $ports = GlobalPort::query()
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get();

            $countryIds = $ports
                ->pluck('country_id')
                ->filter()
                ->unique()
                ->values();

            $countries = Country::query()
                ->whereIn('id', $countryIds)
                ->get()
                ->keyBy('id');

            return $ports
                ->map(function (GlobalPort $port) use ($countries) {
                    $country = $countries->get($port->country_id);

                    return [
                        'id' => $port->id,
                        'name' => $port->name,
                        'code' => $port->code ?: '-',
                        'city' => $port->city ?: '-',
                        'type' => $port->type ?: '-',
                        'country_name' => $country?->name ?? '-',
                        'country_iso3' => $country?->iso3_code ?? '-',
                        'latitude' => $port->latitude,
                        'longitude' => $port->longitude,
                        'risk_score' => round((float) ($port->risk_score ?? 0), 2),
                        'risk_level' => $port->risk_level ?: 'low',
                        'risk_label' => $this->riskLevelLabel($port->risk_level ?: 'low'),
                        'updated_at' => $port->updated_at
                            ? $port->updated_at->format('d M Y H:i')
                            : '-',
                    ];
                });
        });
    }

    private function isAdminUser(User $user): bool
    {
        if (method_exists($user, 'isAdmin')) {
            return (bool) $user->isAdmin();
        }

        if (Schema::hasColumn('users', 'role')) {
            return strtolower((string) $user->role) === 'admin'
                || strtolower((string) $user->role) === 'administrator';
        }

        if (Schema::hasColumn('users', 'is_admin')) {
            return (bool) $user->is_admin;
        }

        return false;
    }

    private function safeCount(string $modelClass): int
    {
        try {
            return $modelClass::query()->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function safeCollection(callable $callback): Collection
    {
        try {
            $result = $callback();

            return $result instanceof Collection
                ? $result
                : collect($result);
        } catch (Throwable) {
            return collect();
        }
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
            default => 'Tidak Aktif',
        };
    }

    private function statusClass(?string $status): string
    {
        return match ($status) {
            'active' => 'admin-status-active',
            'inactive' => 'admin-status-inactive',
            default => 'admin-status-inactive',
        };
    }

    private function riskLevelLabel(?string $level): string
    {
        return match ($level) {
            'critical' => 'Risiko Kritis',
            'high' => 'Risiko Tinggi',
            'moderate', 'medium' => 'Risiko Sedang',
            'low' => 'Risiko Rendah',
            default => 'Belum dihitung',
        };
    }
}