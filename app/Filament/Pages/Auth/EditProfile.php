<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form->schema([
            $this->getNameFormComponent(),
            TextInput::make('username')
                ->label('Username')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),
            $this->getPasswordFormComponent()
                ->required(fn (): bool => (bool) auth()->user()?->must_change_password),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['password'] ?? null)) {
            $data['must_change_password'] = false;
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Profil & Password Berhasil Disimpan')
            ->body('Perubahan berhasil disimpan. Selamat datang di Panel Admin OLT!');
    }

    protected function getRedirectUrl(): ?string
    {
        return filament()->getUrl();
    }
}
