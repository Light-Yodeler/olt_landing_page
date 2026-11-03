<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\ImageOptimizerService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;

class ManageSiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Site Settings';
    protected static ?string $title = 'Site Settings & Brand Identity';
    protected static string $view = 'filament.pages.manage-site-settings';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageSiteSettings() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageSiteSettings() ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $setting = SiteSetting::first();
        if ($setting) {
            $this->form->fill($setting->attributesToArray());
        } else {
            $this->form->fill();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('Identity & Contact')
                            ->schema([
                                FileUpload::make('logo_path')
                                    ->label('Company Logo')
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->disk('public')
                                    ->directory('site')
                                    ->maxSize(15360)
                                    ->saveUploadedFileUsing(fn (UploadedFile $file) => ImageOptimizerService::optimizeAndStore($file, 'site', 800)),
                                FileUpload::make('favicon_path')
                                    ->label('Favicon (.ico, .png, .svg)')
                                    ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/svg+xml'])
                                    ->disk('public')
                                    ->directory('site')
                                    ->maxSize(5120)
                                    ->saveUploadedFileUsing(fn (UploadedFile $file) => ImageOptimizerService::optimizeAndStore($file, 'site', 256)),
                                TextInput::make('company_name.en')->label('Company Name (EN)')->required(),
                                TextInput::make('company_name.id')->label('Company Name (ID)')->required(),
                                Textarea::make('tagline.en')->label('Tagline (EN)')->rows(2),
                                Textarea::make('tagline.id')->label('Tagline (ID)')->rows(2),
                                TextInput::make('whatsapp_number')->label('WhatsApp Number (ex: 6287865432190)')->required(),
                                Textarea::make('whatsapp_default_message.en')->label('Default WhatsApp Message (EN)')->rows(2),
                                Textarea::make('whatsapp_default_message.id')->label('Default WhatsApp Message (ID)')->rows(2),
                                TextInput::make('address')->label('Physical Address')->columnSpanFull(),
                                TextInput::make('footer_copyright.en')->label('Footer Copyright (EN)'),
                                TextInput::make('footer_copyright.id')->label('Footer Copyright (ID)'),
                            ])->columns(2),

                        Tabs\Tab::make('Hero & Greeting')
                            ->schema([
                                TextInput::make('hero_badge.en')->label('Hero Badge (EN)'),
                                TextInput::make('hero_badge.id')->label('Hero Badge (ID)'),
                                TextInput::make('hero_headline.en')->label('Headline (EN)'),
                                TextInput::make('hero_headline.id')->label('Headline (ID)'),
                                Textarea::make('hero_description.en')->label('Opening Text (EN)')->rows(3),
                                Textarea::make('hero_description.id')->label('Opening Text (ID)')->rows(3),
                                Repeater::make('hero_highlights.en')
                                    ->label('Hero Card Highlights (EN) - 4 Points')
                                    ->simple(TextInput::make('point')->required()),
                                Repeater::make('hero_highlights.id')
                                    ->label('Hero Card Highlights (ID) - 4 Poin')
                                    ->simple(TextInput::make('point')->required()),
                            ])->columns(2),

                        Tabs\Tab::make('About Us')
                            ->schema([
                                TextInput::make('about_badge.en')->label('About Section Badge (EN)'),
                                TextInput::make('about_badge.id')->label('About Section Badge (ID)'),
                                TextInput::make('about_title.en')->label('About Title (EN)'),
                                TextInput::make('about_title.id')->label('About Title (ID)'),
                                Textarea::make('about_body.en')->label('About Body (EN)')->rows(6),
                                Textarea::make('about_body.id')->label('About Body (ID)')->rows(6),
                            ])->columns(2),

                        Tabs\Tab::make('Consultation Banner')
                            ->schema([
                                TextInput::make('cta_title.en')->label('Banner Title (EN)'),
                                TextInput::make('cta_title.id')->label('Banner Title (ID)'),
                                Textarea::make('cta_description.en')->label('Banner Description (EN)')->rows(3),
                                Textarea::make('cta_description.id')->label('Banner Description (ID)')->rows(3),
                                TextInput::make('cta_button_text.en')->label('Button Label (EN)'),
                                TextInput::make('cta_button_text.id')->label('Button Label (ID)'),
                                Textarea::make('cta_whatsapp_message.en')->label('WhatsApp Message (EN)')->rows(2),
                                Textarea::make('cta_whatsapp_message.id')->label('WhatsApp Message (ID)')->rows(2),
                            ])->columns(2),

                        Tabs\Tab::make('SEO & Social Media')
                            ->schema([
                                TextInput::make('meta_title.en')->label('Meta Title (EN)'),
                                TextInput::make('meta_title.id')->label('Meta Title (ID)'),
                                Textarea::make('meta_description.en')->label('Meta Description (EN)')->rows(2),
                                Textarea::make('meta_description.id')->label('Meta Description (ID)')->rows(2),
                                FileUpload::make('og_image_path')
                                    ->label('OpenGraph Share Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('site')
                                    ->maxSize(15360)
                                    ->saveUploadedFileUsing(fn (UploadedFile $file) => ImageOptimizerService::optimizeAndStore($file, 'site', 1200, 80)),
                                TextInput::make('instagram_url')->label('Instagram URL'),
                                TextInput::make('facebook_url')->label('Facebook URL'),
                                TextInput::make('tiktok_url')->label('TikTok URL'),
                                TextInput::make('tripadvisor_url')->label('TripAdvisor URL'),
                                TextInput::make('google_maps_url')->label('Google Maps URL')->columnSpanFull(),
                            ])->columns(2),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $state = $this->form->getState();
        $setting = SiteSetting::first();

        if ($setting) {
            $setting->update($state);
        } else {
            SiteSetting::create($state);
        }

        Notification::make()
            ->title('Site settings updated successfully')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('submit'),
        ];
    }
}
