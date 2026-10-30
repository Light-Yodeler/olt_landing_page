<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HeroSlideResource\Pages;
use App\Models\HeroSlide;
use App\Services\ImageOptimizerService;
use Filament\Forms\Components\FileUpload;
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

class HeroSlideResource extends Resource
{
    use Translatable;

    protected static ?string $model = HeroSlide::class;
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Landing Page';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            FileUpload::make('image_path')
                ->label('Slide Image')
                ->image()
                ->disk('public')
                ->directory('hero-slides')
                ->required()
                ->maxSize(15360)
                ->saveUploadedFileUsing(fn (UploadedFile $file) => ImageOptimizerService::optimizeAndStore($file, 'hero-slides', 1920, 85)),
            TextInput::make('caption')
                ->label('Caption / Alt Text')
                ->maxLength(255),
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
                    ->label('Thumbnail')
                    ->disk('public')
                    ->square(),
                TextColumn::make('caption')
                    ->label('Caption / Alt')
                    ->limit(50),
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
            'index' => Pages\ListHeroSlides::route('/'),
            'create' => Pages\CreateHeroSlide::route('/create'),
            'edit' => Pages\EditHeroSlide::route('/{record}/edit'),
        ];
    }
}
