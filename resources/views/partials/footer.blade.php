@php
    $locale = app()->getLocale();
    $waNumber = $setting?->whatsapp_number ?? '6287865432190';
@endphp

<footer class="py-12 bg-base">
    <div class="max-w-6xl mx-auto px-4 sm:px-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 pb-8 border-b border-borderSubtle">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 relative flex items-center justify-center shrink-0">
                    @if($setting?->logo_path)
                        <img src="{{ asset('storage/' . $setting->logo_path) }}" alt="OLT Logo" class="w-12 h-12 rounded-full object-cover">
                    @else
                        <svg class="w-12 h-12 rounded-full drop-shadow" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" fill="#0c4a60" r="48" stroke="#f59e0b" stroke-width="3"></circle>
                            <path d="M12 50 A38 38 0 0 1 88 50 Z" fill="#f59e0b"></path>
                            <circle cx="50" cy="45" fill="#fef08a" r="13"></circle>
                            <polygon fill="#0369a1" points="45,50 68,32 86,50"></polygon>
                            <polygon fill="#0c4a60" points="41,47 43,32 49,47"></polygon>
                            <path d="M12 50 Q50 60 88 50 L88 78 Q50 88 12 78 Z" fill="#0284c7"></path>
                            <text fill="#ffffff" font-family="'Plus Jakarta Sans', sans-serif" font-size="22" font-weight="900" stroke="#082f49" stroke-width="1.2" x="24" y="75">O</text>
                            <text fill="#38bdf8" font-family="'Plus Jakarta Sans', sans-serif" font-size="23" font-weight="900" stroke="#082f49" stroke-width="1.2" x="43" y="77">L</text>
                            <text fill="#fbbf24" font-family="'Plus Jakarta Sans', sans-serif" font-size="22" font-weight="900" stroke="#082f49" stroke-width="1.2" x="58" y="75">T</text>
                        </svg>
                    @endif
                </div>
                <div>
                    <span class="text-base font-bold text-ink-900">{{ $setting?->getTranslation('company_name', $locale) ?? 'Ocean Lombok Tour and Transport' }}</span>
                    <p class="text-xs text-ocean-700 mt-0.5">{{ $setting?->getTranslation('tagline', $locale) }}</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 text-xs font-mono text-ink-700">
                <div>
                    <a class="hover:text-coralSunset-600 transition-colors font-semibold focus:outline-none focus-visible:ring-1 focus-visible:ring-coralSunset-500 rounded" href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener noreferrer">
                        WhatsApp: +{{ $waNumber }}
                    </a>
                    <span class="mx-2">•</span>
                    <span>{{ $setting?->address ?? 'Kuta Mandalika, Lombok Tengah' }}</span>
                </div>

                <!-- Social Links -->
                <div class="flex items-center gap-2 pt-2 sm:pt-0">
                    @if($setting?->instagram_url)
                        <a aria-label="Instagram" class="w-7 h-7 rounded-full bg-surface border border-borderSubtle flex items-center justify-center text-ink-500 hover:text-coralSunset-600 hover:border-coralSunset-400 transition-colors focus:outline-none focus-visible:ring-1 focus-visible:ring-coralSunset-500" href="{{ $setting->instagram_url }}" target="_blank" rel="noopener noreferrer">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        </a>
                    @endif
                    @if($setting?->facebook_url)
                        <a aria-label="Facebook" class="w-7 h-7 rounded-full bg-surface border border-borderSubtle flex items-center justify-center text-ink-500 hover:text-ocean-600 hover:border-ocean-400 transition-colors focus:outline-none focus-visible:ring-1 focus-visible:ring-ocean-500" href="{{ $setting->facebook_url }}" target="_blank" rel="noopener noreferrer">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        </a>
                    @endif
                    @if($setting?->google_maps_url)
                        <a aria-label="Google Maps" class="w-7 h-7 rounded-full bg-surface border border-borderSubtle flex items-center justify-center text-ink-500 hover:text-amberGlow-600 hover:border-amberGlow-400 transition-colors focus:outline-none focus-visible:ring-1 focus-visible:ring-amberGlow-500" href="{{ $setting->google_maps_url }}" target="_blank" rel="noopener noreferrer">
                            <x-lucide-map-pin class="w-3.5 h-3.5" />
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="pt-6 flex flex-col sm:flex-row justify-between text-[11px] font-mono text-ink-500">
            <p>{{ $setting?->getTranslation('footer_copyright', $locale) ?? '© 2026 Ocean Lombok Tour and Transport (OLT).' }}</p>
            <p>{{ __('footer.motto') }}</p>
        </div>
    </div>
</footer>
