<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Standard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;

class LandingPageService
{
    /**
     * @return array{
     *     setting: ?SiteSetting,
     *     slides: Collection<int, HeroSlide>,
     *     services: Collection<int, Service>,
     *     destinations: Collection<int, Destination>,
     *     standards: Collection<int, Standard>,
     *     faqs: Collection<int, Faq>
     * }
     */
    public function getLandingPageData(): array
    {
        return [
            'setting' => SiteSetting::first(),
            'slides' => HeroSlide::where('is_active', true)->orderBy('sort_order')->get(),
            'services' => Service::where('is_active', true)->orderBy('sort_order')->get(),
            'destinations' => Destination::where('is_active', true)->orderBy('sort_order')->get(),
            'standards' => Standard::where('is_active', true)->orderBy('sort_order')->get(),
            'faqs' => Faq::where('is_active', true)->orderBy('sort_order')->get(),
        ];
    }

    public function switchLocale(string $locale): RedirectResponse
    {
        $normalizedLocale = in_array($locale, ['en', 'id'], true) ? $locale : 'en';
        $redirectTarget = $normalizedLocale === 'id' ? '/id' : '/';

        return redirect($redirectTarget)->withCookie(
            Cookie::make('olt_locale', $normalizedLocale, 60 * 24 * 30)
        );
    }
}

