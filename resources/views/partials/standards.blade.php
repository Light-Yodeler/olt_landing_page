@php
    $locale = app()->getLocale();
@endphp

<section class="py-16 border-b border-ocean-100 bg-surface" id="standards">
    <div class="max-w-6xl mx-auto px-4 sm:px-8">
        <div class="max-w-2xl mb-12">
            <span class="text-xs font-mono uppercase tracking-widest text-ocean-700 font-semibold">{{ __('standards.section_badge') }}</span>
            <h2 class="text-2xl sm:text-3xl font-serif text-ink-900 mt-1 font-normal">{{ __('standards.section_title') }}</h2>
            <p class="text-xs sm:text-sm text-ink-700 mt-2 font-light">
                {{ __('standards.section_subtitle') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-xs text-ink-700">
            @foreach($standards as $index => $standard)
                @php
                    $borderColors = ['border-amberGlow-500', 'border-ocean-600', 'border-coralSunset-500'];
                    $borderClass = $borderColors[$index % 3];
                @endphp
                <div class="space-y-3 border-l-2 {{ $borderClass }} pl-4 bg-gradient-to-r from-subtle/60 to-transparent py-2 rounded-r-lg">
                    <h3 class="font-bold text-ink-900 text-sm">
                        {{ $standard->getTranslation('title', $locale) }}
                    </h3>
                    <p class="leading-relaxed font-light">
                        {{ $standard->getTranslation('description', $locale) }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
