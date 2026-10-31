<?php

namespace App\Filament\Resources\VisitorTrackingResource\Pages;

use App\Filament\Resources\VisitorTrackingResource;
use Filament\Resources\Pages\ListRecords;

class ListVisitorTrackings extends ListRecords
{
    protected static string $resource = VisitorTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
