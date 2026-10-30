<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class HeroSlide extends Model
{
    use HasTranslations;

    protected $fillable = [
        'image_path',
        'caption',
        'sort_order',
        'is_active',
    ];

    public array $translatable = [
        'caption',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
