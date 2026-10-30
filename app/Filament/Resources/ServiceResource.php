<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    use Translatable;

    protected static ?string $model = Service::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Landing Page';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('category_code')
                ->label('Category Code')
                ->required()
                ->helperText('e.g. SCOOTER RENTAL or SEWA MOTOR'),
            Select::make('icon')
                ->label('Lucide Icon Name')
                ->options([
                    'bike' => 'Bike (Motorbike)',
                    'car' => 'Car (Transport)',
                    'mountain' => 'Mountain (Trekking)',
                    'waves' => 'Waves (Surfing / Ocean)',
                    'compass' => 'Compass',
                    'map-pin' => 'Map Pin',
                ])
                ->default('bike')
                ->required(),
            TextInput::make('title')
                ->label('Service Title')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->required(),
            Repeater::make('features')
                ->label('Feature Bullets')
                ->simple(TextInput::make('feature')->required())
                ->defaultItems(2),
            TextInput::make('button_text')
                ->label('Button Text')
                ->placeholder('e.g. Check Scooters'),
            Textarea::make('whatsapp_message')
                ->label('WhatsApp Pre-fill Message')
                ->rows(2),
            TextInput::make('sort_order')
                ->label('Sort Order')
                ->numeric()
                ->default(0)
                ->required(),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('icon')
                    ->label('Icon')
                    ->badge(),
                TextColumn::make('category_code')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
