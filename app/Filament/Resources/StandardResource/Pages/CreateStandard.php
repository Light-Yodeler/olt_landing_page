<?php

namespace App\Filament\Resources\StandardResource\Pages;

use App\Filament\Resources\StandardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStandard extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = StandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }
}
