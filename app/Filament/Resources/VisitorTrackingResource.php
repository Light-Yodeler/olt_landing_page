<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitorTrackingResource\Pages;
use App\Filament\Resources\VisitorTrackingResource\Widgets\MonthlyVisitorsChart;
use App\Filament\Resources\VisitorTrackingResource\Widgets\VisitorStatsOverview;
use App\Models\VisitorTracking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VisitorTrackingResource extends Resource
{
    protected static ?string $model = VisitorTracking::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Activity & Logs';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Visitor Tracking';

    protected static ?string $modelLabel = 'Visitor Tracking';

    protected static ?string $pluralModelLabel = 'Visitor Trackings';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getNavigationItems(): array
    {
        return array_map(
            fn (NavigationItem $item) => $item->visible(fn (): bool => static::shouldRegisterNavigation()),
            parent::getNavigationItems(),
        );
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canReplicate(Model $record): bool
    {
        return false;
    }

    public static function canRestore(Model $record): bool
    {
        return false;
    }

    public static function canRestoreAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('visit_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('visitor_hash')
                    ->label('Visitor Hash')
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('Visitor hash copied to clipboard')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 12) . '...')
                    ->tooltip(fn (VisitorTracking $record): string => $record->visitor_hash),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('IP address copied to clipboard')
                    ->searchable()
                    ->sortable()
                    ->placeholder(fn (VisitorTracking $record): string => $record->masked_ip ?? 'Hidden'),

                Tables\Columns\TextColumn::make('visit_count')
                    ->label('Total Hits')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('is_returning')
                    ->label('Visitor Category')
                    ->state(fn (VisitorTracking $record): string => $record->isReturning() ? 'Returning' : 'First Visit')
                    ->badge()
                    ->colors([
                        'info' => 'Returning',
                        'success' => 'First Visit',
                    ])
                    ->icons([
                        'heroicon-m-arrow-path' => 'Returning',
                        'heroicon-m-user' => 'First Visit',
                    ]),

                Tables\Columns\TextColumn::make('first_visited_at')
                    ->label('First Seen')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_visited_at')
                    ->label('Last Seen')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(40)
                    ->tooltip(fn (VisitorTracking $record): ?string => $record->user_agent)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_visited_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('visit_date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Visited From'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('Visited Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $q, $date): Builder => $q->whereDate('visit_date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $q, $date): Builder => $q->whereDate('visit_date', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('visitor_type')
                    ->label('Visitor Category')
                    ->options([
                        'returning' => 'Returning Visitors',
                        'single' => 'First-Time Visitors',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'returning' => $query->where('visit_count', '>', 1),
                            'single' => $query->where('visit_count', 1),
                            default => $query,
                        };
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('No visitor tracking records recorded')
            ->emptyStateDescription('Landing page visits will be logged here as visitors access the site.')
            ->emptyStateIcon('heroicon-o-globe-alt');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitorTrackings::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            VisitorStatsOverview::class,
            MonthlyVisitorsChart::class,
        ];
    }
}
