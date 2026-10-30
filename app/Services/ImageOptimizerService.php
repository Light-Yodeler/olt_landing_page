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
        $mime = $file->getMimeType();

        if (in_array($extension, ['svg', 'ico']) || in_array($mime, ['image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'])) {
            $safeExt = $extension === 'svg' ? 'svg' : 'ico';
            $filename = $directory . '/' . Str::random(40) . '.' . $safeExt;
            Storage::disk('public')->put($filename, file_get_contents($file->getRealPath()));

            return $filename;
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
