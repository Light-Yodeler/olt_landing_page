<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DestinationResource\Pages;
use App\Models\Destination;
use App\Services\ImageOptimizerService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;

class DestinationResource extends Resource
{
    use Translatable;

    protected static ?string $model = Destination::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Landing Page';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            FileUpload::make('image_path')
                ->label('Destination Image')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->disk('public')
                ->directory('destinations')
                ->required()
                ->maxSize(15360)
                ->saveUploadedFileUsing(fn (UploadedFile $file) => ImageOptimizerService::optimizeAndStore($file, 'destinations', 1600, 82)),
            TextInput::make('name')
                ->label('Destination Name')
                ->required()
                ->maxLength(255),
            TextInput::make('category_badge')
                ->label('Category Badge')
                ->placeholder('e.g. South Coast / Pesisir Selatan'),
            TextInput::make('location')
                ->label('Location / Area')
                ->placeholder('e.g. Central Lombok / Lombok Tengah'),
            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->required(),
            TextInput::make('button_text')
                ->label('Action Button Text')
                ->placeholder('e.g. Inquire Tour →'),
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
                ImageColumn::make('image_path')
                    ->label('Photo')
                    ->disk('public')
                    ->square(),
                TextColumn::make('name')
                    ->label('Destination')
                    ->searchable(),
                TextColumn::make('category_badge')
                    ->label('Category'),
                TextColumn::make('location')
                    ->label('Location'),
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
            'index' => Pages\ListDestinations::route('/'),
            'create' => Pages\CreateDestination::route('/create'),
            'edit' => Pages\EditDestination::route('/{record}/edit'),
        ];
    }
}
