<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginHistoryResource\Pages;
use App\Models\LoginHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LoginHistoryResource extends Resource
{
    protected static ?string $model = LoginHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Activity & Logs';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Login History';

    protected static ?string $modelLabel = 'Login History';

    protected static ?string $pluralModelLabel = 'Login Histories';

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
                Tables\Columns\TextColumn::make('attempted_identifier')
                    ->label('User / Identifier')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->description(fn (LoginHistory $record): string => $record->user ? ($record->user->name . ' (' . $record->user->role . ')') : 'Unregistered / Unknown'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => LoginHistory::STATUS_SUCCESS,
                        'danger' => LoginHistory::STATUS_FAILED,
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => LoginHistory::STATUS_SUCCESS,
                        'heroicon-m-x-circle' => LoginHistory::STATUS_FAILED,
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('IP address copied to clipboard'),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(50)
                    ->tooltip(fn (LoginHistory $record): ?string => $record->user_agent)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('login_at')
                    ->label('Timestamp')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('login_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        LoginHistory::STATUS_SUCCESS => 'Success',
                        LoginHistory::STATUS_FAILED => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'username')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('login_at')
                    ->form([
                        Forms\Components\DatePicker::make('login_from')
                            ->label('Attempted From'),
                        Forms\Components\DatePicker::make('login_until')
                            ->label('Attempted Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['login_from'],
                                fn (Builder $q, $date): Builder => $q->whereDate('login_at', '>=', $date),
                            )
                            ->when(
                                $data['login_until'],
                                fn (Builder $q, $date): Builder => $q->whereDate('login_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('No login history records found')
            ->emptyStateDescription('Authentication attempts will appear here automatically when users attempt to log in.')
            ->emptyStateIcon('heroicon-o-shield-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoginHistories::route('/'),
        ];
    }
}
