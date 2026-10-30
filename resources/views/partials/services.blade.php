@php
    $locale = app()->getLocale();
    $waNumber = $setting?->whatsapp_number ?? '6287865432190';
@endphp

<section class="py-16 border-b border-ocean-100 bg-surface" id="services">
    <div class="max-w-6xl mx-auto px-4 sm:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-10 pb-4 border-b border-borderSubtle">
            <div>
                <span class="text-xs font-mono uppercase tracking-widest text-ocean-700 font-semibold">{{ __('services.section_badge') }}</span>
                <h2 class="text-2xl sm:text-3xl font-serif text-ink-900 mt-1 font-normal">{{ __('services.section_title') }}</h2>
            </div>
            <p class="text-xs text-ink-500 mt-2 sm:mt-0 font-mono">{{ __('services.section_subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($services as $service)
                @php
                    $features = $service->getTranslation('features', $locale) ?? [];
                    if (!is_array($features)) {
                        $features = [];
                    }
                    $waMsg = $service->getTranslation('whatsapp_message', $locale) ?? "Hello OLT, I would like to inquire about {$service->getTranslation('title', $locale)}.";
                    $btnText = $service->getTranslation('button_text', $locale) ?? 'Inquire Now';
                @endphp
                <div class="bg-base p-5 rounded-xl border border-border flex flex-col justify-between hover:border-ocean-500 hover:shadow-ocean transition-all duration-300 group">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-mono font-bold text-ocean-700">{{ $service->getTranslation('category_code', $locale) }}</span>
                            <div class="w-8 h-8 rounded-lg bg-subtle flex items-center justify-center border border-border group-hover:bg-amberGlow-500 group-hover:text-white transition-colors">
                                @if($service->icon === 'bike')
                                    <x-lucide-bike class="w-4 h-4 text-ocean-700 group-hover:text-white transition-colors" />
                                @elseif($service->icon === 'car')
                                    <x-lucide-car class="w-4 h-4 text-ocean-700 group-hover:text-white transition-colors" />
                                @elseif($service->icon === 'mountain')
                                    <x-lucide-mountain class="w-4 h-4 text-ocean-700 group-hover:text-white transition-colors" />
                                @elseif($service->icon === 'waves')
                                    <x-lucide-waves class="w-4 h-4 text-ocean-700 group-hover:text-white transition-colors" />
                                @elseif($service->icon === 'compass')
                                    <x-lucide-compass class="w-4 h-4 text-ocean-700 group-hover:text-white transition-colors" />
                                @else
                                    <x-lucide-map-pin class="w-4 h-4 text-ocean-700 group-hover:text-white transition-colors" />
                                @endif
                            </div>
                        </div>

                        <h3 class="text-base font-bold text-ink-900 font-sans group-hover:text-ocean-700 transition-colors">
                            {{ $service->getTranslation('title', $locale) }}
                        </h3>

                        <p class="text-xs text-ink-700 leading-relaxed font-light">
                            {{ $service->getTranslation('description', $locale) }}
                        </p>

                        @if(count($features) > 0)
                            <ul class="pt-2 border-t border-borderSubtle space-y-1 text-[11px] text-ink-500">
                                @foreach($features as $feature)
                                    <li>• {{ is_array($feature) ? ($feature['feature'] ?? '') : $feature }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="mt-5 pt-3 border-t border-borderSubtle">
                        <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMsg) }}" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-ocean-700 hover:text-coralSunset-600 flex items-center gap-1 transition-colors focus:outline-none focus-visible:ring-1 focus-visible:ring-ocean-500 rounded">
                            <span>{{ $btnText }}</span>
                            <x-lucide-arrow-right class="w-3 h-3" />
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
