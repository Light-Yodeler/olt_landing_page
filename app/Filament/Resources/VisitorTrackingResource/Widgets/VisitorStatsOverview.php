<?php

namespace App\Filament\Resources\VisitorTrackingResource\Widgets;

use App\Models\VisitorTracking;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VisitorStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today()->toDateString();
        $thirtyDaysAgo = Carbon::today()->subDays(29)->toDateString();

        $totalHitsAllTime = (int) VisitorTracking::sum('visit_count');
        $uniqueVisitorsAllTime = (int) VisitorTracking::distinct('visitor_hash')->count('visitor_hash');

        $last30DaysHits = (int) VisitorTracking::whereDate('visit_date', '>=', $thirtyDaysAgo)->sum('visit_count');
        $last30DaysUnique = (int) VisitorTracking::whereDate('visit_date', '>=', $thirtyDaysAgo)->count();

        $todayHits = (int) VisitorTracking::whereDate('visit_date', $today)->sum('visit_count');
        $todayUnique = (int) VisitorTracking::whereDate('visit_date', $today)->count();

        // 7-day sparkline trend data
        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $trendData[] = (int) VisitorTracking::whereDate('visit_date', $date)->sum('visit_count');
        }

        return [
            Stat::make('Total Kunjungan (All-Time)', number_format($totalHitsAllTime))
                ->description(number_format($uniqueVisitorsAllTime) . ' Pengunjung Unik')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),

            Stat::make('Kunjungan 30 Hari Terakhir', number_format($last30DaysHits))
                ->description(number_format($last30DaysUnique) . ' Pengunjung Unik (30 Hari)')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->chart($trendData)
                ->color('success'),

            Stat::make('Kunjungan Hari Ini (WITA)', number_format($todayHits))
                ->description(number_format($todayUnique) . ' Pengunjung Unik Hari Ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),
        ];
    }
}
