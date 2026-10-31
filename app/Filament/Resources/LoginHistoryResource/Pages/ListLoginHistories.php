<?php

namespace App\Filament\Resources\LoginHistoryResource\Pages;

use App\Filament\Resources\LoginHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListLoginHistories extends ListRecords
{
    protected static string $resource = LoginHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
