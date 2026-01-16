<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * AnalyticsManagerService for the Forge package.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Forge\Services;

use Database\DB;
use Forge\Enums\LicenceStatus;
use Forge\Models\Licence;
use Helpers\DateTimeHelper;
use Helpers\File\Cache;
use UnitEnum;

class AnalyticsManagerService
{
    /**
     * The aggregation interval.
     */
    protected string $interval = 'daily';

    /**
     * The client ID scope.
     */
    protected ?string $clientId = null;

    /**
     * The reseller/owner ID scope.
     */
    protected ?string $resellerId = null;

    public function daily(): self
    {
        $this->interval = 'daily';

        return $this;
    }

    public function monthly(): self
    {
        $this->interval = 'monthly';

        return $this;
    }

    public function yearly(): self
    {
        $this->interval = 'yearly';

        return $this;
    }

    public function forClient(int|string $id): self
    {
        $this->clientId = (string) $id;

        return $this;
    }

    /**
     * Scope analytics to a specific reseller/owner.
     */
    public function forReseller(int|string $id): self
    {
        $this->resellerId = (string) $id;

        return $this;
    }

    protected function getDateFormat(): string
    {
        return match ($this->interval) {
            'monthly' => '%Y-%m',
            'yearly' => '%Y',
            default => '%Y-%m-%d',
        };
    }

    /**
     * Reset fluent options to defaults.
     */
    protected function resetFluentOptions(): void
    {
        $this->interval = 'daily';
        $this->clientId = null;
        $this->resellerId = null;
    }

    public function mintingStats(?string $start = null, ?string $end = null): array
    {
        $cacheKey = 'forge_analytics_minting_stats_' . ($start ?? 'all') . '_' . ($end ?? 'all') . '_' . ($this->clientId ?? 'all') . '_' . ($this->resellerId ?? 'all');

        return Cache::create()->remember($cacheKey, 3600, function () use ($start, $end) {
            $query = Licence::query();

            if ($this->clientId) {
                $query->where('client_id', $this->clientId);
            }

            if ($this->resellerId) {
                $query->join('client', 'client.id', '=', 'forge_licence.client_id')
                    ->where('client.owner_id', $this->resellerId);
            }

            if ($start && $end) {
                $query->whereBetween('forge_licence.created_at', [$start, $end]);
            }

            $total = (clone $query)->count();
            $statusBreakdown = $query->select('forge_licence.status')
                ->selectRaw('count(*) as count')
                ->groupBy('forge_licence.status')
                ->get();

            $breakdown = [];
            foreach ($statusBreakdown as $row) {
                $status = $row->status instanceof UnitEnum ? $row->status->value : $row->status;
                $breakdown[$status] = $row->count;
            }

            $this->resetFluentOptions();

            return [
                'total' => $total,
                'status' => $breakdown
            ];
        });
    }

    public function expirationForecast(int $days = 30): int
    {
        $cacheKey = "forge_analytics_expiration_forecast_{$days}_" . ($this->clientId ?? 'all') . "_" . ($this->resellerId ?? 'all');

        return Cache::create()->remember($cacheKey, 3600, function () use ($days) {
            $query = Licence::query()
                ->where('forge_licence.status', LicenceStatus::Active->value)
                ->whereBetween('forge_licence.expires_at', [DateTimeHelper::now()->toDateTimeString(), DateTimeHelper::now()->addDays($days)->toDateTimeString()]);

            if ($this->clientId) {
                $query->where('client_id', $this->clientId);
            }

            if ($this->resellerId) {
                $query->join('client', 'client.id', '=', 'forge_licence.client_id')
                    ->where('client.owner_id', $this->resellerId);
            }

            $count = $query->count();
            $this->resetFluentOptions();

            return $count;
        });
    }

    public function productPopularity(?string $start = null, ?string $end = null): array
    {
        $cacheKey = 'forge_analytics_product_popularity_' . ($start ?? 'all') . '_' . ($end ?? 'all') . '_' . ($this->clientId ?? 'all') . '_' . ($this->resellerId ?? 'all');

        return Cache::create()->remember($cacheKey, 3600, function () use ($start, $end) {
            $query = Licence::query();

            if ($this->clientId) {
                $query->where('client_id', $this->clientId);
            }

            if ($this->resellerId) {
                $query->join('client', 'client.id', '=', 'forge_licence.client_id')
                    ->where('client.owner_id', $this->resellerId);
            }

            if ($start && $end) {
                $query->whereBetween('forge_licence.created_at', [$start, $end]);
            }

            $results = $query->select('forge_licence.product_id')
                ->selectRaw('count(*) as count')
                ->groupBy('forge_licence.product_id')
                ->get();

            $popularity = [];
            foreach ($results as $row) {
                $popularity[$row->product_id] = $row->count;
            }

            $this->resetFluentOptions();

            return $popularity;
        });
    }

    public function mintingTrends(string $start, string $end, ?string $interval = null): array
    {
        $this->interval = $interval ?? $this->interval;
        $cacheKey = "forge_analytics_minting_trends_{$start}_{$end}_{$this->interval}_" . ($this->clientId ?? 'all') . "_" . ($this->resellerId ?? 'all');

        return Cache::create()->remember($cacheKey, 3600, function () use ($start, $end) {
            $format = $this->getDateFormat();

            $query = DB::table('forge_licence');

            if ($this->clientId) {
                $query->where('client_id', $this->clientId);
            }

            if ($this->resellerId) {
                $query->join('client', 'client.id', '=', 'forge_licence.client_id')
                    ->where('client.owner_id', $this->resellerId);
            }

            $results = $query
                ->select(DB::raw("DATE_FORMAT(forge_licence.created_at, '{$format}') as date"))
                ->selectRaw('count(*) as count')
                ->whereBetween('forge_licence.created_at', [$start, $end])
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get();

            $trends = [];
            foreach ($results as $row) {
                $trends[(string) $row['date']] = (int) $row['count'];
            }

            $this->resetFluentOptions();

            return $trends;
        });
    }

    public function activationTrends(string $start, string $end, ?string $interval = null): array
    {
        $this->interval = $interval ?? $this->interval;
        $cacheKey = "forge_analytics_activation_trends_{$start}_{$end}_{$this->interval}_" . ($this->clientId ?? 'all') . "_" . ($this->resellerId ?? 'all');

        return Cache::create()->remember($cacheKey, 3600, function () use ($start, $end) {
            $format = $this->getDateFormat();

            $query = DB::table('forge_licence');

            if ($this->clientId) {
                $query->where('client_id', $this->clientId);
            }

            if ($this->resellerId) {
                $query->join('client', 'client.id', '=', 'forge_licence.client_id')
                    ->where('client.owner_id', $this->resellerId);
            }

            $results = $query
                ->select(DB::raw("DATE_FORMAT(forge_licence.activated_at, '{$format}') as date"))
                ->selectRaw('count(*) as count')
                ->whereNotNull('forge_licence.activated_at')
                ->whereBetween('forge_licence.activated_at', [$start, $end])
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get();

            $trends = [];
            foreach ($results as $row) {
                $trends[(string) $row['date']] = (int) $row['count'];
            }

            $this->resetFluentOptions();

            return $trends;
        });
    }
}
