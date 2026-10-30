<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Destination extends Model
{
    use HasTranslations;

    protected $fillable = [
        'image_path',
        'name',
        'category_badge',
        'description',
        'location',
        'button_text',
        'whatsapp_message',
        'sort_order',
        'is_active',
    ];

    public array $translatable = [
        'name',
        'category_badge',
        'description',
        'location',
        'button_text',
        'whatsapp_message',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
