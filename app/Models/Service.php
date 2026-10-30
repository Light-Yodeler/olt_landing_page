<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Service extends Model
{
    use HasTranslations;

    protected $fillable = [
        'category_code',
        'icon',
        'title',
        'description',
        'features',
        'button_text',
        'whatsapp_message',
        'sort_order',
        'is_active',
    ];

    public array $translatable = [
        'category_code',
        'title',
        'description',
        'features',
        'button_text',
        'whatsapp_message',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
