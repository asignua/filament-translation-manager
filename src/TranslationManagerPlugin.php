<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Pages\QuickTranslate;
use Asignua\FilamentTranslationManager\Resources\TranslationResource;
use Asignua\FilamentTranslationManager\Support\Locales;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/**
 * Database-backed translations for a Filament panel.
 *
 *     ->plugin(TranslationManagerPlugin::make()
 *         ->locales(['en' => 'English', 'uk' => 'Українська', 'de'])
 *         ->languageSwitcher()
 *         ->gate('manageTranslations'))
 *
 * Every option falls back to config/filament-translation-manager.php. The names follow kenepa/translation-manager
 * (`availableLocales()`, `languageSwitcher()`, `navigationGroup()` …) where that package had one.
 */
class TranslationManagerPlugin implements Plugin
{
    public const string ID = 'asignua-filament-translation-manager';

    /** @var array<array-key, mixed>|null */
    protected ?array $locales = null;

    protected ?string $gate = null;

    protected ?string $deleteGate = null;

    protected bool|Closure|null $authorizeUsing = null;

    protected ?bool $languageSwitcher = null;

    protected ?bool $quickTranslate = null;

    protected string|Closure|null $navigationGroup = null;

    protected ?int $navigationSort = null;

    protected ?string $navigationIcon = null;

    protected string|Closure|null $navigationLabel = null;

    /** @var class-string<TranslationResource>|false|null */
    protected string|false|null $resource = null;

    protected ?bool $navigationBadge = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static */
        return filament(static::ID);
    }

    /**
     * The plugin of the current panel when it has one, otherwise a bare instance that reads the config only
     * (the console, a queue job, a test that never touched a panel).
     */
    public static function resolve(): static
    {
        $panel = Filament::getCurrentPanel();

        if ($panel !== null && $panel->hasPlugin(static::ID)) {
            /** @var static */
            return $panel->getPlugin(static::ID);
        }

        return static::make();
    }

    public function getId(): string
    {
        return static::ID;
    }

    public function register(Panel $panel): void
    {
        if ($this->locales !== null) {
            Locales::override($this->locales);
        }

        $resource = $this->getResourceClass();

        if ($resource !== false) {
            $panel->resources([$resource]);
        }

        if ($this->isQuickTranslateEnabled()) {
            $panel->pages([QuickTranslate::class]);
        }

        if ($this->isLanguageSwitcherEnabled()) {
            $panel->renderHook(PanelsRenderHook::USER_MENU_BEFORE, static fn (): string => view('filament-translation-manager::language-switcher')->render());
        }
    }

    public function boot(Panel $panel): void
    {
        if ($this->isLanguageSwitcherEnabled()) {
            Support\LocaleSwitcher::apply();
        }
    }

    /**
     * The resource registered on the panel. A host that subclasses {@see TranslationResource} (to add a column, hide
     * "create", restrict the query) passes its class here, or `false` when its own resource is discovered by the panel.
     *
     * @param class-string<TranslationResource>|false|null $resource
     */
    public function resource(string|false|null $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return class-string<TranslationResource>|false
     */
    public function getResourceClass(): string|false
    {
        return $this->resource ?? TranslationResource::class;
    }

    // ---- locales -----------------------------------------------------------------------------------------

    /**
     * Code list, `code => name` map or kenepa-style rows (`['code' => 'en', 'name' => 'English', 'flag' => 'gb']`).
     *
     * @param array<array-key, mixed>|null $locales
     */
    public function locales(?array $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    /**
     * @param array<array-key, mixed>|null $locales
     */
    public function availableLocales(?array $locales): static
    {
        return $this->locales($locales);
    }

    // ---- access ------------------------------------------------------------------------------------------

    public function gate(?string $gate): static
    {
        $this->gate = $gate;

        return $this;
    }

    public function getGate(): string
    {
        return $this->gate ?? (string) config('filament-translation-manager.gate', 'manageTranslations');
    }

    public function deleteGate(?string $gate): static
    {
        $this->deleteGate = $gate;

        return $this;
    }

    public function getDeleteGate(): string
    {
        return $this->deleteGate ?? (string) config('filament-translation-manager.delete_gate', 'deleteTranslations');
    }

    /**
     * Replaces the gate: a bool, or a closure receiving the user.
     */
    public function authorize(bool|Closure|null $authorize): static
    {
        $this->authorizeUsing = $authorize;

        return $this;
    }

    public function canAccess(): bool
    {
        return $this->check($this->getGate());
    }

    public function canDelete(): bool
    {
        return $this->check($this->getDeleteGate());
    }

    private function check(string $ability): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($this->authorizeUsing !== null) {
            return $this->authorizeUsing instanceof Closure
                ? (bool) app()->call($this->authorizeUsing, ['user' => $user])
                : $this->authorizeUsing;
        }

        // Closed by default: an undefined gate denies everybody.
        return Gate::has($ability) && Gate::forUser($user)->allows($ability);
    }

    private function user(): ?Authenticatable
    {
        return Filament::auth()->user() ?? auth()->user();
    }

    // ---- features ----------------------------------------------------------------------------------------

    public function languageSwitcher(bool $enabled = true): static
    {
        $this->languageSwitcher = $enabled;

        return $this;
    }

    public function isLanguageSwitcherEnabled(): bool
    {
        return $this->languageSwitcher ?? (bool) config('filament-translation-manager.language_switcher', false);
    }

    public function quickTranslate(bool $enabled = true): static
    {
        $this->quickTranslate = $enabled;

        return $this;
    }

    public function isQuickTranslateEnabled(): bool
    {
        return $this->quickTranslate ?? (bool) config('filament-translation-manager.quick_translate.enabled', true);
    }

    // ---- navigation --------------------------------------------------------------------------------------

    /**
     * A string, or a closure for a translated group label (resolved when the navigation is built, not at boot).
     */
    public function navigationGroup(string|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        $group = $this->navigationGroup ?? config('filament-translation-manager.navigation.group');
        $group = $group instanceof Closure ? $group() : $group;

        return is_string($group) ? $group : null;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        $sort = $this->navigationSort ?? config('filament-translation-manager.navigation.sort');

        return is_numeric($sort) ? (int) $sort : null;
    }

    public function navigationIcon(?string $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): string
    {
        return $this->navigationIcon ?? (string) config('filament-translation-manager.navigation.icon', 'heroicon-o-language');
    }

    public function navigationLabel(string|Closure|null $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function getNavigationLabel(): ?string
    {
        $label = $this->navigationLabel instanceof Closure ? ($this->navigationLabel)() : $this->navigationLabel;

        return is_string($label) ? $label : null;
    }

    /**
     * A badge with the number of rows that miss a text in at least one locale.
     */
    public function navigationBadge(bool $enabled = true): static
    {
        $this->navigationBadge = $enabled;

        return $this;
    }

    public function shouldShowNavigationBadge(): bool
    {
        return $this->navigationBadge ?? (bool) config('filament-translation-manager.navigation.badge', false);
    }

    /**
     * @return class-string<Translation>
     */
    public function getModel(): string
    {
        /** @var class-string<Translation> */
        return (string) config('filament-translation-manager.model', Translation::class);
    }
}
