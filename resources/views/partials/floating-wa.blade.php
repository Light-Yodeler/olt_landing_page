@php
    $locale = app()->getLocale();
    $waNumber = $setting?->whatsapp_number ?? '6287865432190';
    $defaultWaMsg = urlencode($setting?->getTranslation('whatsapp_default_message', $locale) ?? 'Hello Ocean Lombok Tour');
@endphp

<div class="fixed bottom-6 right-6 z-50">
    <a href="https://wa.me/{{ $waNumber }}?text={{ $defaultWaMsg }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2.5 h-12 px-5 rounded-full bg-gradient-to-r from-amberGlow-500 to-coralSunset-500 text-white shadow-sunset hover:from-amberGlow-600 hover:to-coralSunset-600 hover:scale-105 active:scale-95 transition-all text-xs font-bold tracking-wide border border-amberGlow-400/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amberGlow-400">
        <x-lucide-message-circle class="w-4 h-4 text-white" />
        <span class="hidden sm:inline">{{ __('floating_wa.label') }}</span>
    </a>
</div>
