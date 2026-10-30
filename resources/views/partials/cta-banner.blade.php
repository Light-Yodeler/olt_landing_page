@php
    $locale = app()->getLocale();
    $waNumber = $setting?->whatsapp_number ?? '6287865432190';
    $ctaMsg = $setting?->getTranslation('cta_whatsapp_message', $locale) ?? 'Hello Ocean Lombok Tour';
    $btnText = $setting?->getTranslation('cta_button_text', $locale) ?? 'Start Chat on WhatsApp';
@endphp

<section class="py-16 bg-gradient-to-b from-surface via-subtle to-breeze/30 border-b border-ocean-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-8 text-center space-y-5">
        <div class="w-16 h-16 mx-auto relative flex items-center justify-center">
            @if($setting?->logo_path)
                <img src="{{ asset('storage/' . $setting->logo_path) }}" alt="OLT Emblem" class="w-16 h-16 rounded-full object-cover shadow-sm">
            @else
                <svg class="w-16 h-16 rounded-full drop-shadow-md" viewBox="0 0 100 100">
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
        <h2 class="text-2xl sm:text-3xl font-serif text-ink-900 font-normal">
            {{ $setting?->getTranslation('cta_title', $locale) }}
        </h2>
        <p class="text-xs sm:text-sm text-ink-700 max-w-xl mx-auto font-light leading-relaxed">
            {{ $setting?->getTranslation('cta_description', $locale) }}
        </p>
        <div class="pt-2">
            <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($ctaMsg) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2.5 h-11 px-7 rounded-lg bg-gradient-to-r from-amberGlow-500 to-coralSunset-500 hover:from-amberGlow-600 hover:to-coralSunset-600 text-white text-xs font-bold tracking-wide transition-all shadow-sunset active:scale-95 border border-amberGlow-400/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-amberGlow-400">
                <x-lucide-message-circle class="w-4 h-4 text-white" />
                <span>{{ $btnText }}</span>
            </a>
        </div>
    </div>
</section>
