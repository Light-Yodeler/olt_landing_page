<?php

namespace App\Filament\Resources\VisitorTrackingResource\Widgets;

use App\Models\VisitorTracking;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VisitorStatsOverview extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function boot(): void
    {
        abort_unless(static::canView(), 403);
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 403);
    }

    protected function getStats(): array
    {
        $today = Carbon::today()->toDateString();
        $thirtyDaysAgo = Carbon::today()->subDays(29)->toDateString();

        $allTime = VisitorTracking::query()
            ->selectRaw('COALESCE(SUM(visit_count), 0) as total_hits, COUNT(DISTINCT visitor_hash) as unique_visitors')
            ->first();

        $totalHitsAllTime = (int) ($allTime?->total_hits ?? 0);
        $uniqueVisitorsAllTime = (int) ($allTime?->unique_visitors ?? 0);

        $dailyRecords = VisitorTracking::query()
            ->where('visit_date', '>=', $thirtyDaysAgo)
            ->groupBy('visit_date')
            ->selectRaw('visit_date, COALESCE(SUM(visit_count), 0) as hits, COUNT(*) as unique_count')
            ->get()
            ->keyBy(fn ($item) => is_string($item->visit_date) ? substr($item->visit_date, 0, 10) : $item->visit_date->toDateString());

        $last30DaysHits = 0;
        $last30DaysUnique = 0;
        foreach ($dailyRecords as $record) {
            $last30DaysHits += (int) $record->hits;
            $last30DaysUnique += (int) $record->unique_count;
        }

        $todayHits = (int) ($dailyRecords[$today]?->hits ?? 0);
        $todayUnique = (int) ($dailyRecords[$today]?->unique_count ?? 0);

        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $trendData[] = (int) ($dailyRecords[$date]?->hits ?? 0);
        }

        return [
            Stat::make('Total Visits (All-Time)', number_format($totalHitsAllTime))
                ->description(number_format($uniqueVisitorsAllTime) . ' Unique Visitors')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),

            Stat::make('Visits (Last 30 Days)', number_format($last30DaysHits))
                ->description(number_format($last30DaysUnique) . ' Unique Visitors (30 Days)')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->chart($trendData)
                ->color('success'),

            Stat::make('Visits Today (WITA)', number_format($todayHits))
                ->description(number_format($todayUnique) . ' Unique Visitors Today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
        ];
    }
}
