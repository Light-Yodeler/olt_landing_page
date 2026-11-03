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
            $content = (string) file_get_contents($file->getRealPath());

            if ($safeExt === 'svg') {
                $content = self::sanitizeSvg($content);
            }

            Storage::disk('public')->put($filename, $content);

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

    /**
     * Sanitize SVG content by stripping scripts, objects, and event handlers.
     */
    public static function sanitizeSvg(string $svgContent): string
    {
        // Strip <script> tags and any contents inside
        $sanitized = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $svgContent);
        $sanitized = preg_replace('/<script\b[^>]*>/i', '', (string) $sanitized);

        // Strip embedded active objects (iframe, embed, object, foreignObject)
        $sanitized = preg_replace('/<(iframe|embed|object|foreignObject)\b[^>]*>(.*?)<\/\1>/is', '', (string) $sanitized);
        $sanitized = preg_replace('/<(iframe|embed|object|foreignObject)\b[^>]*\/?\s*>/i', '', (string) $sanitized);

        // Strip event handler attributes (onload, onerror, onclick, etc.)
        $sanitized = preg_replace('/\s*on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', (string) $sanitized);

        // Strip javascript: pseudo-protocols in href or xlink:href
        $sanitized = preg_replace('/(?:\bhref|\bxlink:href)\s*=\s*["\']\s*javascript:[^"\']*["\']/i', '', (string) $sanitized);

        return (string) $sanitized;
    }
}
