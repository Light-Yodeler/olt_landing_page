<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SiteSetting extends Model
{
    use HasTranslations;

    protected $fillable = [
        'logo_path',
        'favicon_path',
        'company_name',
        'tagline',
        'whatsapp_number',
        'whatsapp_default_message',
        'address',
        'instagram_url',
        'facebook_url',
        'tiktok_url',
        'tripadvisor_url',
        'google_maps_url',
        'footer_copyright',
        'hero_badge',
        'hero_headline',
        'hero_description',
        'hero_highlights',
        'about_badge',
        'about_title',
        'about_body',
        'cta_title',
        'cta_description',
        'cta_button_text',
        'cta_whatsapp_message',
        'meta_title',
        'meta_description',
        'og_image_path',
    ];

    public array $translatable = [
        'company_name',
        'tagline',
        'whatsapp_default_message',
        'footer_copyright',
        'hero_badge',
        'hero_headline',
        'hero_description',
        'hero_highlights',
        'about_badge',
        'about_title',
        'about_body',
        'cta_title',
        'cta_description',
        'cta_button_text',
        'cta_whatsapp_message',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'hero_highlights' => 'array',
        ];
    }
}
