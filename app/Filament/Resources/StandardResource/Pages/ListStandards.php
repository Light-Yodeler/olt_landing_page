<?php

namespace App\Filament\Resources\StandardResource\Pages;

use App\Filament\Resources\StandardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStandards extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = StandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
