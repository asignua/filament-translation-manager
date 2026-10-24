## Filament Translation Manager (asignua/filament-translation-manager)

Edit `__()` strings per language from the panel; texts live in spatie/laravel-translation-loader's `language_lines` and override lang files. Successor of `kenepa/translation-manager`.

- Register: `->plugin(Asignua\FilamentTranslationManager\TranslationManagerPlugin::make()->locales(['en' => 'English', 'uk' => 'Українська']))`, then `php artisan vendor:publish --tag=filament-translation-manager-migrations && php artisan migrate`. The migrations are publishable stubs; the first skips an existing `language_lines` table.
- Closed by default: define `Gate::define('manageTranslations', ...)` (access) and `Gate::define('deleteTranslations', ...)` (delete). An undefined ability means "no". Alternatives: `->gate('use-translation-manager')` (kenepa's name) or `->authorize(fn ($user) => ...)`.
- Fill the table with `php artisan translation-manager:scan [--dry-run] [--prune]` or the "Scan code" action. It is additive and never overwrites an edited text; `--prune` removes only scanner-created rows whose key vanished (dynamic keys are not found - review first).
- Always write translations through `Asignua\FilamentTranslationManager\Models\Translation` (save/delete), never a raw query: spatie caches forever and only model events flush it.
- Options are fluent on the plugin (`languageSwitcher()`, `quickTranslate()`, `navigationGroup()`, `navigationBadge()`) and fall back to `config/filament-translation-manager.php`.
- One literal parser (`Support\TranslationLiteral`) serves every scan; namespaced keys (`pkg::group.key`) are skipped because the database loader does not serve them. Dots in a key are fine.
