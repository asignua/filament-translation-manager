@php
    use Asignua\FilamentTranslationManager\Support\Locales;

    $locales = Locales::all();
    $current = app()->getLocale();
@endphp

@if (count($locales) > 1)
    {{-- Namespaced CSS only (`fi-tm-*`): the panel's compiled theme does not contain a plugin's Tailwind utilities. --}}
    <style>
        .fi-tm-switcher { display: flex; align-items: center; margin-inline-end: 0.75rem; }
        .fi-tm-switcher-trigger { display: inline-flex; flex-direction: row; align-items: center; gap: 0.375rem; white-space: nowrap; padding: 0.375rem 0.625rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; line-height: 1.25rem; background: transparent; border: 0; cursor: pointer; color: inherit; }
        .fi-tm-switcher-trigger:hover, .fi-tm-switcher-trigger:focus-visible { background: color-mix(in srgb, currentColor 8%, transparent); }
        .fi-tm-switcher-form { margin: 0; }
        .fi-tm-switcher-item { display: flex; width: 100%; align-items: center; gap: 0.5rem; white-space: nowrap; padding: 0.375rem 0.75rem; border: 0; background: transparent; color: inherit; font-size: 0.875rem; line-height: 1.25rem; text-align: start; cursor: pointer; border-radius: 0.375rem; }
        .fi-tm-switcher-item:hover, .fi-tm-switcher-item:focus-visible { background: color-mix(in srgb, currentColor 8%, transparent); }
        .fi-tm-switcher-item[aria-current="true"] { font-weight: 600; }
        .fi-tm-switcher-flag { font-size: 1.125rem; line-height: 1; }
    </style>

    <div class="fi-tm-switcher">
        <x-filament::dropdown placement="bottom-end" teleport>
            <x-slot name="trigger">
                <button
                    type="button"
                    class="fi-tm-switcher-trigger"
                    aria-label="{{ __('filament-translation-manager::filament-translation-manager.switcher.label') }}"
                >
                    @if (filled($locales[$current]['flag'] ?? null))
                        <span class="fi-tm-switcher-flag" aria-hidden="true">{{ Locales::flagEmoji($locales[$current]['flag']) }}</span>
                    @endif
                    <span>{{ $locales[$current]['name'] ?? strtoupper($current) }}</span>
                </button>
            </x-slot>

            <x-filament::dropdown.list>
                @foreach ($locales as $code => $locale)
                    <form method="post" action="{{ route('filament-translation-manager.locale', $code) }}" class="fi-tm-switcher-form">
                        @csrf
                        <button type="submit" class="fi-tm-switcher-item" @if ($code === $current) aria-current="true" @endif>
                            @if (filled($locale['flag']))
                                <span class="fi-tm-switcher-flag" aria-hidden="true">{{ Locales::flagEmoji($locale['flag']) }}</span>
                            @endif
                            <span>{{ $locale['name'] }}</span>
                        </button>
                    </form>
                @endforeach
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    </div>
@endif
