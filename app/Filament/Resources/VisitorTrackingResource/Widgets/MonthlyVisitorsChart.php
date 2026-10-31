<?php

namespace App\Filament\Resources\VisitorTrackingResource\Widgets;

use App\Models\VisitorTracking;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MonthlyVisitorsChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Kunjungan 30 Hari Terakhir';

    protected static ?string $maxHeight = '280px';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $dates = [];
        $labels = [];
        $hitsData = [];
        $uniqueData = [];

        // Pre-fill last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $carbonDate = Carbon::today()->subDays($i);
            $dateString = $carbonDate->toDateString();
            $dates[$dateString] = [
                'label' => $carbonDate->format('d M'),
                'hits' => 0,
                'unique' => 0,
            ];
        }

        $thirtyDaysAgo = Carbon::today()->subDays(29)->toDateString();

        $records = VisitorTracking::whereDate('visit_date', '>=', $thirtyDaysAgo)
            ->selectRaw('visit_date, sum(visit_count) as total_hits, count(*) as unique_visitors')
            ->groupBy('visit_date')
            ->get();

        foreach ($records as $record) {
            $dateKey = is_string($record->visit_date)
                ? substr($record->visit_date, 0, 10)
                : $record->visit_date->toDateString();

            if (isset($dates[$dateKey])) {
                $dates[$dateKey]['hits'] = (int) $record->total_hits;
                $dates[$dateKey]['unique'] = (int) $record->unique_visitors;
            }
        }

        foreach ($dates as $item) {
            $labels[] = $item['label'];
            $hitsData[] = $item['hits'];
            $uniqueData[] = $item['unique'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Kunjungan (Hits)',
                    'data' => $hitsData,
                    'borderColor' => '#0ea5e9', // Ocean-500
                    'backgroundColor' => 'rgba(14, 165, 233, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Pengunjung Unik',
                    'data' => $uniqueData,
                    'borderColor' => '#f59e0b', // AmberGlow-500
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
