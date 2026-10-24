@php
    use Asignua\FilamentTranslationManager\Support\Locales;

    $locales = Locales::all();
    $current = app()->getLocale();
@endphp

@if (count($locales) > 1)
    <x-filament::dropdown placement="bottom-end" teleport>
        <x-slot name="trigger">
            <button
                type="button"
                class="fi-icon-btn flex items-center gap-1 px-2 text-sm font-medium"
                aria-label="{{ __('filament-translation-manager::filament-translation-manager.switcher.label') }}"
            >
                <span>{{ Locales::label($current) }}</span>
            </button>
        </x-slot>

        <x-filament::dropdown.list>
            @foreach ($locales as $code => $locale)
                <form method="post" action="{{ route('filament-translation-manager.locale', $code) }}">
                    @csrf
                    <x-filament::dropdown.list.item
                        tag="button"
                        type="submit"
                        :color="$code === $current ? 'primary' : 'gray'"
                    >
                        {{ Locales::label($code) }}
                    </x-filament::dropdown.list.item>
                </form>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
@endif
