@php
    $locale = app()->getLocale();
    $waNumber = $setting?->whatsapp_number ?? '6287865432190';
@endphp

<section class="py-16 border-b border-ocean-100 bg-subtle/50" id="destinations">
    <div class="max-w-6xl mx-auto px-4 sm:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-10 pb-4 border-b border-border">
            <div>
                <span class="text-xs font-mono uppercase tracking-widest text-ocean-700 font-semibold">{{ __('destinations.section_badge') }}</span>
                <h2 class="text-2xl sm:text-3xl font-serif text-ink-900 mt-1 font-normal">{{ __('destinations.section_title') }}</h2>
            </div>
            <p class="text-xs text-ink-500 mt-2 sm:mt-0 font-mono">{{ __('destinations.section_subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($destinations as $dest)
                @php
                    $imageUrl = Str::startsWith($dest->image_path, 'http') ? $dest->image_path : asset('storage/' . $dest->image_path);
                    $waMsg = $dest->getTranslation('whatsapp_message', $locale) ?? "Hello OLT, I am interested in visiting {$dest->getTranslation('name', $locale)}.";
                    $btnText = $dest->getTranslation('button_text', $locale) ?? ($locale === 'id' ? 'Tanya Tur →' : 'Inquire Tour →');
                @endphp
                <div class="group bg-surface rounded-xl border border-border overflow-hidden flex flex-col shadow-sm hover:shadow-ocean hover:border-ocean-500 transition-all duration-300">
                    <div class="relative h-56 overflow-hidden bg-breeze">
                        <img alt="{{ $dest->getTranslation('name', $locale) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" src="{{ $imageUrl }}">
                        @if($dest->getTranslation('category_badge', $locale))
                            <span class="absolute top-3 left-3 px-2.5 py-1 rounded bg-ocean-900/85 backdrop-blur-sm text-[10px] font-mono text-amberGlow-300 font-semibold tracking-wider uppercase border border-ocean-700/50">
                                {{ $dest->getTranslation('category_badge', $locale) }}
                            </span>
                        @endif
                    </div>
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="text-base font-bold text-ink-900 group-hover:text-ocean-700 transition-colors">
                                {{ $dest->getTranslation('name', $locale) }}
                            </h3>
                            <p class="text-xs text-ink-700 mt-2 font-light leading-relaxed">
                                {{ $dest->getTranslation('description', $locale) }}
                            </p>
                        </div>
                        <div class="pt-4 mt-4 border-t border-borderSubtle flex items-center justify-between text-xs">
                            <span class="font-mono text-ocean-700 text-[11px] font-medium">
                                {{ $dest->getTranslation('location', $locale) }}
                            </span>
                            <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMsg) }}" target="_blank" rel="noopener noreferrer" class="font-bold text-coralSunset-600 hover:text-coralSunset-500 transition-colors focus:outline-none focus-visible:ring-1 focus-visible:ring-coralSunset-500 rounded">
                                {{ $btnText }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
