<?php

namespace App\Filament\Resources\VisitorTrackingResource\Widgets;

use App\Models\VisitorTracking;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MonthlyVisitorsChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly Visitors Trend (Last 30 Days)';

    protected static ?string $maxHeight = '280px';

    protected int | string | array $columnSpan = 'full';

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
        parent::mount();
    }

    protected function getData(): array
    {
        $dates = [];
        $labels = [];
        $hitsData = [];
        $uniqueData = [];

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

        $records = VisitorTracking::query()
            ->toBase()
            ->where('visit_date', '>=', $thirtyDaysAgo)
            ->groupBy('visit_date')
            ->orderBy('visit_date', 'asc')
            ->selectRaw('visit_date, sum(visit_count) as total_hits, count(*) as unique_visitors')
            ->get();

        foreach ($records as $record) {
            $dateKey = is_string($record->visit_date)
                ? substr($record->visit_date, 0, 10)
                : Carbon::parse($record->visit_date)->toDateString();

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
                    'label' => 'Total Visits (Hits)',
                    'data' => $hitsData,
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Unique Visitors',
                    'data' => $uniqueData,
                    'borderColor' => '#f59e0b',
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
