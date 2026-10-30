@php
    $locale = app()->getLocale();
    $waNumber = $setting?->whatsapp_number ?? '6287865432190';
    $defaultWaMsg = urlencode($setting?->getTranslation('whatsapp_default_message', $locale) ?? 'Hello Ocean Lombok Tour');
@endphp

<header class="sticky top-0 z-40 bg-surface/90 backdrop-blur-md border-b border-ocean-100 shadow-sm shadow-ocean-500/5">
    <div class="max-w-6xl mx-auto px-4 sm:px-8 h-20 flex items-center justify-between">
        <a class="flex items-center gap-3.5 group focus:outline-none focus-visible:ring-2 focus-visible:ring-ocean-600 rounded-lg" href="{{ $locale === 'id' ? '/id' : '/' }}">
            <div class="w-11 h-11 relative flex items-center justify-center shrink-0">
                @if($setting?->logo_path)
                    <img src="{{ asset('storage/' . $setting->logo_path) }}" alt="OLT Logo" class="w-11 h-11 rounded-full object-cover">
                @else
                    <svg class="w-11 h-11 rounded-full group-hover:scale-105 transition-transform drop-shadow" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" fill="#0c4a60" r="48" stroke="#f59e0b" stroke-width="2.5"></circle>
                        <circle cx="50" cy="50" fill="#f0f9ff" r="44" stroke="#0284c7" stroke-width="1"></circle>
                        <path d="M12 50 A38 38 0 0 1 88 50 Z" fill="#f59e0b" opacity="0.95"></path>
                        <circle cx="50" cy="46" fill="#fef08a" r="14"></circle>
                        <polygon fill="#0369a1" opacity="0.9" points="45,50 68,32 86,50"></polygon>
                        <polygon fill="#f8fafc" opacity="0.9" points="68,32 72,37 66,38"></polygon>
                        <polygon fill="#0c4a60" points="41,47 43,33 49,47"></polygon>
                        <polygon fill="#0284c7" points="40,47 34,38 39,47"></polygon>
                        <path d="M32 47 Q42 51 51 47 Z" fill="#ea580c"></path>
                        <path d="M12 50 Q25 45 40 50 T70 48 T88 50 L88 78 Q65 88 50 88 T12 78 Z" fill="#0284c7"></path>
                        <path d="M14 55 Q28 50 44 54 T76 52 T86 55 L86 80 Q65 88 50 88 T14 80 Z" fill="#0369a1"></path>
                        <path d="M16 63 Q32 58 52 62 T84 63 L84 82 Q65 88 50 88 T16 82 Z" fill="#0c4a60"></path>
                        <text fill="#ffffff" font-family="'Plus Jakarta Sans', sans-serif" font-size="23" font-weight="900" stroke="#082f49" stroke-width="1.2" x="25" y="76">O</text>
                        <text fill="#38bdf8" font-family="'Plus Jakarta Sans', sans-serif" font-size="24" font-weight="900" stroke="#082f49" stroke-width="1.2" x="44" y="78">L</text>
                        <text fill="#fbbf24" font-family="'Plus Jakarta Sans', sans-serif" font-size="23" font-weight="900" stroke="#082f49" stroke-width="1.2" x="59" y="76">T</text>
                    </svg>
                @endif
            </div>
            <div class="flex flex-col">
                <span class="text-base font-bold tracking-tight text-ink-900 leading-none">{{ $setting?->getTranslation('company_name', $locale) ?? 'Ocean Lombok Tour' }}</span>
                <span class="text-[10px] font-mono tracking-wider text-ocean-700 font-semibold uppercase mt-1">OLT • Kuta Mandalika</span>
            </div>
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center gap-8 text-xs font-semibold text-ink-700">
            <a class="hover:text-ocean-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ocean-600 rounded px-1" href="#services">{{ __('nav.services') }}</a>
            <a class="hover:text-ocean-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ocean-600 rounded px-1" href="#destinations">{{ __('nav.destinations') }}</a>
            <a class="hover:text-ocean-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ocean-600 rounded px-1" href="#about">{{ __('nav.about') }}</a>
            <a class="hover:text-ocean-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ocean-600 rounded px-1" href="#faq">{{ __('nav.faq') }}</a>
        </nav>

        <div class="flex items-center gap-3">
            <!-- Language Switcher -->
            <div class="hidden sm:flex items-center bg-subtle border border-borderSubtle rounded-lg p-0.5 text-[11px] font-mono font-semibold">
                <a href="{{ route('locale.switch', 'id') }}" class="px-2 py-1 rounded {{ $locale === 'id' ? 'bg-ocean-800 text-white shadow-sm' : 'text-ink-500 hover:text-ocean-700' }} transition-colors">ID</a>
                <a href="{{ route('locale.switch', 'en') }}" class="px-2 py-1 rounded {{ $locale === 'en' ? 'bg-ocean-800 text-white shadow-sm' : 'text-ink-500 hover:text-ocean-700' }} transition-colors">EN</a>
            </div>

            <!-- Header Action CTA -->
            <a href="https://wa.me/{{ $waNumber }}?text={{ $defaultWaMsg }}" target="_blank" rel="noopener noreferrer" class="hidden sm:inline-flex items-center justify-center gap-2 h-10 px-4 sm:px-5 rounded-lg bg-gradient-to-r from-ocean-800 to-ocean-700 hover:from-ocean-900 hover:to-ocean-800 text-white text-xs font-bold tracking-wide transition-all shadow-md shadow-ocean-700/20 active:scale-95 border border-ocean-600/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-amberGlow-500">
                <x-lucide-message-square class="w-4 h-4 shrink-0 text-amberGlow-400" />
                <span>{{ __('nav.check_availability') }}</span>
            </a>

            <!-- Mobile Hamburger Button -->
            <button id="mobileMenuBtn" aria-label="Buka Menu" aria-controls="mobileMenu" aria-expanded="false" class="md:hidden p-2 rounded-lg text-ink-700 hover:bg-subtle transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ocean-600" onclick="toggleMobileMenu()">
                <x-lucide-menu class="w-6 h-6" />
            </button>
        </div>
    </div>

    <!-- Mobile Drawer -->
    <div id="mobileMenu" class="hidden md:hidden border-t border-ocean-100 bg-surface/95 backdrop-blur-md px-4 py-4 space-y-3">
        <nav class="flex flex-col space-y-2 text-xs font-semibold text-ink-700">
            <a class="p-2 rounded hover:bg-subtle hover:text-ocean-600 transition-colors" href="#services" onclick="toggleMobileMenu()">{{ __('nav.services') }}</a>
            <a class="p-2 rounded hover:bg-subtle hover:text-ocean-600 transition-colors" href="#destinations" onclick="toggleMobileMenu()">{{ __('nav.destinations') }}</a>
            <a class="p-2 rounded hover:bg-subtle hover:text-ocean-600 transition-colors" href="#about" onclick="toggleMobileMenu()">{{ __('nav.about') }}</a>
            <a class="p-2 rounded hover:bg-subtle hover:text-ocean-600 transition-colors" href="#faq" onclick="toggleMobileMenu()">{{ __('nav.faq') }}</a>
        </nav>
        <div class="pt-2 border-t border-borderSubtle flex items-center justify-between">
            <div class="flex items-center gap-1 bg-subtle border border-borderSubtle rounded-lg p-0.5 text-[11px] font-mono font-semibold">
                <a href="{{ route('locale.switch', 'id') }}" class="px-2 py-1 rounded {{ $locale === 'id' ? 'bg-ocean-800 text-white' : 'text-ink-500' }}">ID</a>
                <a href="{{ route('locale.switch', 'en') }}" class="px-2 py-1 rounded {{ $locale === 'en' ? 'bg-ocean-800 text-white' : 'text-ink-500' }}">EN</a>
            </div>
            <a href="https://wa.me/{{ $waNumber }}?text={{ $defaultWaMsg }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-ocean-800 text-white text-xs font-bold" onclick="toggleMobileMenu()">
                <x-lucide-message-square class="w-3.5 h-3.5 text-amberGlow-400" />
                <span>{{ __('nav.check_availability') }}</span>
            </a>
        </div>
    </div>
</header>
