<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StandardResource\Pages;
use App\Models\Standard;
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

class StandardResource extends Resource
{
    use Translatable;

    protected static ?string $model = Standard::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Landing Page';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')
                ->label('Pillar Title')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label('Description')
                ->rows(4)
                ->required(),
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
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60),
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
            'index' => Pages\ListStandards::route('/'),
            'create' => Pages\CreateStandard::route('/create'),
            'edit' => Pages\EditStandard::route('/{record}/edit'),
        ];
    }
}
