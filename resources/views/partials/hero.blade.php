@php
    $locale = app()->getLocale();
    $waNumber = $setting?->whatsapp_number ?? '6287865432190';
    $highlights = $setting?->getTranslation('hero_highlights', $locale) ?? [];
    if (!is_array($highlights)) {
        $highlights = [];
    }
@endphp

<section class="relative border-b border-ocean-100 overflow-hidden min-h-[580px] sm:min-h-[640px] flex items-center">
    <!-- Background Slider Container -->
    <div class="absolute inset-0 z-0 overflow-hidden bg-ocean-950">
        @foreach($slides as $index => $slide)
            @php
                $imageUrl = Str::startsWith($slide->image_path, 'http') ? $slide->image_path : asset('storage/' . $slide->image_path);
            @endphp
            <div class="hero-slide {{ $index === 0 ? 'active' : '' }} absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $imageUrl }}');"></div>
        @endforeach

        <!-- Soft translucent dark overlays to preserve contrast while keeping slide imagery crisp and vibrant -->
        <div class="absolute inset-0 bg-gradient-to-r from-ocean-950/80 via-ocean-900/55 to-ocean-950/30"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-ocean-950/75 via-transparent to-black/20"></div>
    </div>

    <!-- Indicators -->
    @if(count($slides) > 1)
        <div class="absolute bottom-6 left-4 sm:left-8 z-20 flex items-center gap-2">
            @foreach($slides as $index => $slide)
                <button aria-label="Slide {{ $index + 1 }}" class="slide-indicator {{ $index === 0 ? 'w-8 bg-amberGlow-400' : 'w-3 bg-white/50' }} h-1.5 rounded-full shadow-sm transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amberGlow-400" onclick="setSlide({{ $index }})"></button>
            @endforeach
        </div>
    @endif

    <!-- Content -->
    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-8 py-16 sm:py-24 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-8 space-y-6">
                <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full border border-amberGlow-400/40 bg-ocean-900/60 backdrop-blur-md text-amberGlow-300 text-xs shadow-sunset">
                    @if($setting?->logo_path)
                        <img src="{{ asset('storage/' . $setting->logo_path) }}" alt="OLT Logo" class="w-5 h-5 rounded-full object-cover shrink-0">
                    @else
                        <svg class="w-5 h-5 rounded-full shrink-0" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" fill="#0c4a60" r="48" stroke="#f59e0b" stroke-width="3"></circle>
                            <path d="M12 50 A38 38 0 0 1 88 50 Z" fill="#f59e0b"></path>
                            <polygon fill="#0284c7" points="45,50 68,32 86,50"></polygon>
                            <path d="M12 50 Q50 60 88 50 L88 78 Q50 88 12 78 Z" fill="#0ea5e9"></path>
                            <text fill="#ffffff" font-family="'Plus Jakarta Sans', sans-serif" font-size="20" font-weight="900" x="26" y="74">OLT</text>
                        </svg>
                    @endif
                    <span class="font-mono text-[11px] tracking-wide text-amberGlow-200 uppercase font-semibold">
                        {{ $setting?->getTranslation('hero_badge', $locale) ?? 'Ocean Lombok Tour' }}
                    </span>
                </div>

                <h1 class="text-3xl sm:text-5xl lg:text-5xl font-serif text-white leading-[1.2] font-normal tracking-tight drop-shadow-md">
                    {{ $setting?->getTranslation('hero_headline', $locale) }}
                </h1>

                <p class="text-sm sm:text-base text-sky-100 max-w-2xl font-light leading-relaxed drop-shadow">
                    {{ $setting?->getTranslation('hero_description', $locale) }}
                </p>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($setting?->getTranslation('whatsapp_default_message', $locale) ?? 'Hello Ocean Lombok Tour') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 h-11 px-6 rounded-lg bg-gradient-to-r from-amberGlow-500 to-coralSunset-500 hover:from-amberGlow-600 hover:to-coralSunset-600 text-white text-xs font-bold tracking-wide transition-all shadow-sunset active:scale-95 border border-amberGlow-400/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amberGlow-400">
                        <x-lucide-message-circle class="w-4 h-4 text-white" />
                        <span>{{ __('hero.btn.contact_team') }}</span>
                    </a>
                    <a class="inline-flex items-center justify-center gap-2 h-11 px-5 rounded-lg border border-sky-300/40 bg-ocean-800/40 hover:bg-ocean-700/50 text-sky-100 hover:text-white text-xs font-semibold backdrop-blur-md transition-all shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-ocean-400" href="#destinations">
                        <span>{{ __('hero.btn.explore_destinations') }}</span>
                        <x-lucide-arrow-down class="w-3.5 h-3.5 text-amberGlow-400" />
                    </a>
                </div>
            </div>

            <!-- Greeting Card -->
            <div class="lg:col-span-4 bg-surface/95 backdrop-blur-md p-6 rounded-2xl border border-border shadow-ocean space-y-5">
                <div class="flex items-center gap-4 pb-4 border-b border-borderSubtle">
                    <div class="w-16 h-16 relative flex items-center justify-center shrink-0">
                        @if($setting?->logo_path)
                            <img src="{{ asset('storage/' . $setting->logo_path) }}" alt="OLT Logo" class="w-16 h-16 rounded-full object-cover">
                        @else
                            <svg class="w-16 h-16 rounded-full drop-shadow-md" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" fill="#0c4a60" r="48" stroke="#f59e0b" stroke-width="3"></circle>
                                <circle cx="50" cy="43" fill="#f0f9ff" stroke="#0284c7" stroke-width="1"></circle>
                                <path d="M12 50 A38 38 0 0 1 88 50 Z" fill="#f59e0b"></path>
                                <circle cx="50" cy="45" fill="#fef08a" r="13"></circle>
                                <polygon fill="#0369a1" points="45,50 68,32 86,50"></polygon>
                                <polygon fill="#ffffff" opacity="0.9" points="68,32 72,37 66,38"></polygon>
                                <polygon fill="#0c4a60" points="41,47 43,32 49,47"></polygon>
                                <path d="M32 47 Q42 51 51 47 Z" fill="#ea580c"></path>
                                <path d="M12 50 Q25 45 40 50 T70 48 T88 50 L88 78 Q65 88 50 88 T12 78 Z" fill="#0284c7"></path>
                                <text fill="#ffffff" font-family="'Plus Jakarta Sans', sans-serif" font-size="22" font-weight="900" stroke="#082f49" stroke-width="1.2" x="24" y="75">O</text>
                                <text fill="#38bdf8" font-family="'Plus Jakarta Sans', sans-serif" font-size="23" font-weight="900" stroke="#082f49" stroke-width="1.2" x="43" y="77">L</text>
                                <text fill="#fbbf24" font-family="'Plus Jakarta Sans', sans-serif" font-size="22" font-weight="900" stroke="#082f49" stroke-width="1.2" x="58" y="75">T</text>
                            </svg>
                        @endif
                    </div>
                    <div>
                        <span class="text-sm font-bold text-ink-900 block leading-tight">{{ $setting?->getTranslation('company_name', $locale) ?? 'Ocean Lombok Tour' }}</span>
                        <span class="text-[11px] text-ocean-700 font-mono font-medium mt-0.5 block">Kuta Mandalika • Lombok</span>
                    </div>
                </div>

                <div class="space-y-3 text-xs text-ink-700">
                    @foreach($highlights as $point)
                        <div class="flex items-start gap-2.5">
                            <x-lucide-check-circle-2 class="w-4 h-4 text-ocean-600 shrink-0 mt-0.5" />
                            <span>{{ is_array($point) ? ($point['point'] ?? '') : $point }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="pt-3 border-t border-borderSubtle">
                    <span class="text-[11px] font-mono text-ink-500 block mb-1">{{ __('hero.quick_info.need_info') }}</span>
                    <a class="text-xs font-bold text-coralSunset-600 hover:text-coralSunset-500 flex items-center gap-1.5 transition-colors focus:outline-none focus-visible:ring-1 focus-visible:ring-coralSunset-500 rounded" href="https://wa.me/{{ $waNumber }}?text={{ urlencode($setting?->getTranslation('whatsapp_default_message', $locale) ?? 'Hello OLT') }}" target="_blank" rel="noopener noreferrer">
                        <span>{{ __('hero.quick_info.whatsapp_btn') }}</span>
                        <x-lucide-arrow-up-right class="w-3.5 h-3.5" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
