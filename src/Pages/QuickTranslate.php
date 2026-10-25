<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Pages;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Support\LangFiles;
use Asignua\FilamentTranslationManager\Support\Locales;
use Asignua\FilamentTranslationManager\TranslationManagerPlugin;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;
use UnitEnum;

/**
 * Walks the rows that miss a text in the chosen locale, one by one: type, "Save & next".
 */
class QuickTranslate extends Page
{
    protected string $view = 'filament-translation-manager::quick-translate';

    protected static ?string $slug = 'quick-translate';

    public ?string $locale = null;

    // Locked: the id comes back from the browser, and editing an arbitrary row by tampering with it must not work.
    #[Locked]
    public ?int $recordId = null;

    public string $value = '';

    /** @var list<int> rows skipped in this session of the page */
    public array $skipped = [];

    public static function plugin(): TranslationManagerPlugin
    {
        return TranslationManagerPlugin::resolve();
    }

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->hasPlugin(TranslationManagerPlugin::ID) === true
            && static::plugin()->isQuickTranslateEnabled()
            && static::plugin()->canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-translation-manager::filament-translation-manager.quick.navigation');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return static::plugin()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        $sort = static::plugin()->getNavigationSort();

        return $sort === null ? null : $sort + 1;
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-bolt';
    }

    public function getTitle(): string
    {
        return __('filament-translation-manager::filament-translation-manager.quick.title');
    }

    public function mount(): void
    {
        $codes = Locales::codes();
        $requested = request()->query('locale');

        // The first locale that is not the source language - that is the one that usually lacks texts.
        $this->locale = is_string($requested) && in_array($requested, $codes, true)
            ? $requested
            : (collect($codes)->first(fn (string $code): bool => $code !== Locales::default()) ?? $codes[0] ?? null);

        $this->loadNext();
    }

    public function updatedLocale(): void
    {
        abort_unless(in_array($this->locale, Locales::codes(), true), 422);

        $this->skipped = [];
        $this->loadNext();
    }

    public function save(): void
    {
        $this->guard();

        $record = $this->record();

        if ($record === null || $this->locale === null) {
            return;
        }

        if (trim($this->value) === '') {
            Notification::make()
                ->warning()
                ->title(__('filament-translation-manager::filament-translation-manager.quick.empty'))
                ->send();

            return;
        }

        $record->text = array_merge($record->text ?? [], [$this->locale => $this->value]);
        $record->save();

        Notification::make()
            ->success()
            ->title(__('filament-translation-manager::filament-translation-manager.quick.saved'))
            ->send();

        $this->loadNext();
    }

    public function skip(): void
    {
        $this->guard();

        if ($this->recordId !== null) {
            $this->skipped[] = $this->recordId;
        }

        $this->loadNext();
    }

    /**
     * How many rows still miss a text in the chosen locale.
     */
    public function remaining(): int
    {
        return $this->locale === null ? 0 : $this->missing()->count();
    }

    public function record(): ?Translation
    {
        return $this->recordId === null ? null : $this->model()::query()->inManagedGroups()->find($this->recordId);
    }

    /**
     * The other locales' texts of the current row, to translate from.
     *
     * @return array<string, string>
     */
    public function sources(): array
    {
        $record = $this->record();

        if ($record === null) {
            return [];
        }

        $sources = [];
        $files = new LangFiles;

        foreach (Locales::codes() as $code) {
            if ($code === $this->locale) {
                continue;
            }

            $text = $record->textFor($code) ?? $files->value($record->group, $record->key, $code);

            if ($text !== null) {
                $sources[$code] = $text;
            }
        }

        return $sources;
    }

    protected function loadNext(): void
    {
        $next = $this->locale === null
            ? null
            : $this->missing()->whereNotIn('id', $this->skipped)->orderBy('id')->first();

        $this->recordId = $next?->getKey();
        $this->value = '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Translation>
     */
    protected function missing(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->model()::query()->inManagedGroups()->missingIn((string) $this->locale);
    }

    /**
     * @return class-string<Translation>
     */
    protected function model(): string
    {
        return static::plugin()->getModel();
    }

    protected function guard(): void
    {
        abort_unless(static::canAccess(), 403);
    }
}
