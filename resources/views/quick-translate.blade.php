@php
    use Asignua\FilamentTranslationManager\Support\Locales;

    $record = $this->record();
    $remaining = $this->remaining();
@endphp

<x-filament-panels::page>
    <div class="grid max-w-3xl gap-6">
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="locale" aria-label="{{ __('filament-translation-manager::filament-translation-manager.fields.locale') }}">
                @foreach (Locales::options() as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>

        @if ($record === null)
            <x-filament::section>
                <p class="text-sm" data-quick-done>
                    {{ __('filament-translation-manager::filament-translation-manager.quick.all_done') }}
                </p>
            </x-filament::section>
        @else
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('filament-translation-manager::filament-translation-manager.quick.remaining', ['count' => $remaining]) }}
                </x-slot>

                <div class="grid gap-4">
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ $record->group }}
                        </div>
                        <div class="text-sm font-semibold break-words">{{ $record->key }}</div>
                    </div>

                    @foreach ($this->sources() as $label => $text)
                        <div class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-white/5">
                            <div class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</div>
                            <div class="break-words whitespace-pre-line">{{ $text }}</div>
                        </div>
                    @endforeach

                    <x-filament::input.wrapper>
                        <textarea
                            wire:model="value"
                            rows="3"
                            class="fi-input block w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 outline-none dark:text-white sm:text-sm"
                            aria-label="{{ Locales::label((string) $locale) }}"
                            placeholder="{{ Locales::label((string) $locale) }}"
                            wire:keydown.ctrl.enter="save"
                            wire:keydown.meta.enter="save"
                        ></textarea>
                    </x-filament::input.wrapper>

                    <div class="flex flex-wrap gap-3">
                        <x-filament::button wire:click="save" color="primary">
                            {{ __('filament-translation-manager::filament-translation-manager.quick.save_next') }}
                        </x-filament::button>
                        <x-filament::button wire:click="skip" color="gray">
                            {{ __('filament-translation-manager::filament-translation-manager.quick.skip') }}
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
