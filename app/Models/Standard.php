<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Standard extends Model
{
    use HasTranslations;

    protected $fillable = [
        'title',
        'description',
        'sort_order',
        'is_active',
    ];

    public array $translatable = [
        'title',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
