@php
    $locale = app()->getLocale();
@endphp

<section class="py-14 border-b border-ocean-100 bg-gradient-to-r from-subtle via-white to-breeze/40" id="about">
    <div class="max-w-6xl mx-auto px-4 sm:px-8">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
            <div class="md:col-span-5 space-y-2">
                <span class="text-xs font-mono uppercase tracking-widest text-ocean-700 font-semibold">
                    {{ $setting?->getTranslation('about_badge', $locale) ?? '[ ABOUT OUR SERVICES ]' }}
                </span>
                <h2 class="text-xl sm:text-2xl font-serif text-ink-900 font-normal">
                    {{ $setting?->getTranslation('about_title', $locale) }}
                </h2>
            </div>
            <div class="md:col-span-7 text-xs sm:text-sm text-ink-700 font-light leading-relaxed space-y-3">
                {!! nl2br(e($setting?->getTranslation('about_body', $locale))) !!}
            </div>
        </div>
    </div>
</section>
