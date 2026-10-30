<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageOptimizerService
{
    public static function optimizeAndStore(
        UploadedFile $file,
        string $directory = 'uploads',
        int $maxWidth = 1920,
        int $quality = 82
    ): string {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['svg', 'ico'])) {
            return $file->store($directory, 'public');
        }

        $image = Image::read($file);

        if ($image->width() > $maxWidth) {
            $image->scaleDown(width: $maxWidth);
        }

        $encoded = $image->toWebp($quality);
        $filename = $directory . '/' . Str::random(40) . '.webp';
        Storage::disk('public')->put($filename, (string) $encoded);

        return $filename;
    }
}
