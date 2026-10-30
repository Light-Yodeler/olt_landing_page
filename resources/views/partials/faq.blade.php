@php
    $locale = app()->getLocale();
@endphp

<section class="py-16 border-b border-ocean-100 bg-subtle/40" id="faq">
    <div class="max-w-4xl mx-auto px-4 sm:px-8">
        <div class="text-center mb-10">
            <span class="text-xs font-mono uppercase tracking-widest text-ocean-700 font-semibold">{{ __('faq.section_badge') }}</span>
            <h2 class="text-2xl sm:text-3xl font-serif text-ink-900 mt-1 font-normal">{{ __('faq.section_title') }}</h2>
        </div>

        <div class="space-y-3 text-xs">
            @foreach($faqs as $faq)
                <details class="bg-surface border border-border rounded-lg p-4 group shadow-sm transition-all hover:border-ocean-400 focus-within:ring-2 focus-within:ring-ocean-500">
                    <summary class="font-bold text-ink-900 cursor-pointer list-none flex justify-between items-center focus:outline-none">
                        <span>{{ $faq->getTranslation('question', $locale) }}</span>
                        <x-lucide-plus class="w-4 h-4 text-ocean-600 group-open:rotate-45 group-open:text-coralSunset-500 transition-transform" />
                    </summary>
                    <p class="mt-3 text-ink-700 leading-relaxed font-light border-t border-borderSubtle pt-3">
                        {{ $faq->getTranslation('answer', $locale) }}
                    </p>
                </details>
            @endforeach
        </div>
    </div>
</section>
