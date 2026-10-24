@php
    use Asignua\FilamentTranslationManager\Support\Locales;

    $record = $this->record();
    $remaining = $this->remaining();
    $all = Locales::all();
    $flag = static fn (string $code): string => filled($all[$code]['flag'] ?? null) ? Locales::flagEmoji($all[$code]['flag']) : '';
@endphp

<x-filament-panels::page>
    {{-- Namespaced CSS only (`fi-tm-*`): the panel's compiled theme does not contain a plugin's Tailwind utilities. --}}
    <style>
        .fi-tm-quick { display: grid; gap: 1.5rem; max-width: 48rem; }
        .fi-tm-meta { display: flex; flex-wrap: wrap; align-items: baseline; gap: 0.5rem; font-size: 0.8125rem; line-height: 1.25rem; opacity: 0.65; }
        .fi-tm-meta-group { font-weight: 500; }
        .fi-tm-meta-key { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; overflow-wrap: anywhere; }
        .fi-tm-block { display: grid; gap: 0.375rem; }
        .fi-tm-label { display: flex; align-items: center; gap: 0.375rem; font-size: 0.875rem; font-weight: 500; line-height: 1.5rem; }
        .fi-tm-source { padding: 0.625rem 0.875rem; border-radius: 0.5rem; background: color-mix(in srgb, currentColor 5%, transparent); font-size: 0.875rem; line-height: 1.5rem; white-space: pre-line; overflow-wrap: anywhere; }
        .fi-tm-target { display: block; box-sizing: border-box; width: 100%; min-height: 6rem; padding: 0.5rem 0.75rem; resize: vertical; border: 1px solid color-mix(in srgb, currentColor 20%, transparent); border-radius: 0.5rem; background: transparent; color: inherit; font: inherit; font-size: 0.875rem; line-height: 1.5rem; }
        .fi-tm-target:focus { outline: 2px solid var(--primary-500, #f59e0b); outline-offset: -1px; }
        .fi-tm-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; }
        .fi-tm-locale { max-width: 20rem; }
    </style>

    <div class="fi-tm-quick">
        <div class="fi-tm-block fi-tm-locale">
            <label class="fi-tm-label" for="fi-tm-locale">{{ __('filament-translation-manager::filament-translation-manager.fields.locale') }}</label>
            <x-filament::input.wrapper>
                <x-filament::input.select id="fi-tm-locale" wire:model.live="locale">
                    @foreach (Locales::options() as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        @if ($record === null)
            <x-filament::section>
                <p data-quick-done>{{ __('filament-translation-manager::filament-translation-manager.quick.all_done') }}</p>
            </x-filament::section>
        @else
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('filament-translation-manager::filament-translation-manager.quick.remaining', ['count' => $remaining]) }}
                </x-slot>

                <div class="fi-tm-quick">
                    <div class="fi-tm-meta">
                        <span class="fi-tm-meta-group">{{ __('filament-translation-manager::filament-translation-manager.table.group') }}: {{ $record->group }}</span>
                        <span aria-hidden="true">/</span>
                        <span class="fi-tm-meta-key">{{ $record->key }}</span>
                    </div>

                    @foreach ($this->sources() as $code => $text)
                        <div class="fi-tm-block">
                            <div class="fi-tm-label">
                                @if ($flag($code) !== '')
                                    <span aria-hidden="true">{{ $flag($code) }}</span>
                                @endif
                                <span>{{ $all[$code]['name'] ?? $code }}</span>
                            </div>
                            <div class="fi-tm-source">{{ $text }}</div>
                        </div>
                    @endforeach

                    <div class="fi-tm-block">
                        <label class="fi-tm-label" for="fi-tm-target">
                            @if ($flag((string) $locale) !== '')
                                <span aria-hidden="true">{{ $flag((string) $locale) }}</span>
                            @endif
                            <span>{{ $all[(string) $locale]['name'] ?? $locale }}</span>
                        </label>
                        <textarea
                            id="fi-tm-target"
                            class="fi-tm-target"
                            wire:model="value"
                            rows="4"
                            wire:keydown.ctrl.enter="save"
                            wire:keydown.meta.enter="save"
                        ></textarea>
                    </div>

                    <div class="fi-tm-actions">
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
