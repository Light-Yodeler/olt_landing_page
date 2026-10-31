<?php

namespace App\Filament\Resources\VisitorTrackingResource\Pages;

use App\Filament\Resources\VisitorTrackingResource;
use App\Filament\Resources\VisitorTrackingResource\Widgets\MonthlyVisitorsChart;
use App\Filament\Resources\VisitorTrackingResource\Widgets\VisitorStatsOverview;
use Filament\Resources\Pages\ListRecords;

class ListVisitorTrackings extends ListRecords
{
    protected static string $resource = VisitorTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VisitorStatsOverview::class,
            MonthlyVisitorsChart::class,
        ];
    }
}
