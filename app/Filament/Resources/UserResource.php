<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageUsers() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageUsers() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('username')
                    ->label('Username')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('role')
                    ->label('User Role')
                    ->options([
                        User::ROLE_SUPERADMIN => 'Super Admin (Full Access & User Management)',
                        User::ROLE_ADMIN => 'Admin (Content, Contacts & Media Management)',
                        User::ROLE_STAFF => 'Staff (Visual Content & Descriptions Only)',
                    ])
                    ->default(User::ROLE_STAFF)
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(8)
                    ->helperText('Leave empty when editing to keep the existing password.'),
                Forms\Components\Toggle::make('must_change_password')
                    ->label('Must Change Password on Next Login')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'danger' => User::ROLE_SUPERADMIN,
                        'primary' => User::ROLE_ADMIN,
                        'success' => User::ROLE_STAFF,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        User::ROLE_SUPERADMIN => 'Super Admin',
                        User::ROLE_ADMIN => 'Admin',
                        User::ROLE_STAFF => 'Staff',
                        default => ucfirst($state),
                    }),
                Tables\Columns\IconColumn::make('must_change_password')
                    ->label('Requires PW Change')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        User::ROLE_SUPERADMIN => 'Super Admin',
                        User::ROLE_ADMIN => 'Admin',
                        User::ROLE_STAFF => 'Staff',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => $record->id !== auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
