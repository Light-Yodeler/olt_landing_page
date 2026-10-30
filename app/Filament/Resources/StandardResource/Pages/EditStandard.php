<?php

namespace App\Filament\Resources\StandardResource\Pages;

use App\Filament\Resources\StandardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStandard extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = StandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
