<?php

declare(strict_types=1);

use Asignua\FilamentTranslationManager\Models\Translation;

return [
    /*
     * The locales you translate into. Either a list of codes, a `code => name` map, or kenepa-style rows
     * (`['code' => 'en', 'name' => 'English', 'flag' => 'gb']`). `null` = app.locale + app.fallback_locale.
     * `available_locales` is accepted as an alias (kenepa/translation-manager).
     */
    'locales' => null,

    /*
     * The locale the quick-translate page offers as the source text and the scanner fills with the key itself
     * for JSON-style keys. `null` = app.locale.
     */
    'default_locale' => null,

    /*
     * The model behind the `language_lines` table. It must extend Spatie\TranslationLoader\LanguageLine; the
     * default one stores Cyrillic and other non-ASCII text readable and flushes the cache of every locale.
     * When spatie's `translation-loader.model` is still the stock LanguageLine, the plugin points it here.
     */
    'model' => Translation::class,

    /*
     * Gate abilities. A gate that is NOT defined denies everybody. Set `gate` to `use-translation-manager`
     * to keep the kenepa/translation-manager ability.
     */
    'gate' => 'manageTranslations',

    /* Deleting rows (single and bulk) is a separate, stricter ability. */
    'delete_gate' => 'deleteTranslations',

    /* kenepa/translation-manager's switch for the topbar language switcher. Off by default. */
    'language_switcher' => false,

    'language_switcher_options' => [
        // Remember the choice on the user as well (a string column on the user model, e.g. `locale`); `null` = session only.
        'user_column' => null,
        // The session key the middleware reads.
        'session_key' => 'translation-manager.locale',
    ],

    'quick_translate' => [
        'enabled' => true,
    ],

    'scan' => [
        // Where `__()`, `trans()`, `trans_choice()`, `@lang` and `Lang::get()` are looked for (relative to base_path()).
        'paths' => ['app', 'resources/views', 'routes'],
        'extensions' => ['php'],
        // Folders skipped anywhere below the scan paths.
        'exclude' => ['vendor', 'node_modules', 'tests', 'storage', 'cache'],
        // Also read the project's lang files (php groups and JSON) into the table.
        'include_lang_files' => true,
        // JSON-style rows get the key itself as the text of the default locale, so they are not "missing" there.
        'fill_default_locale_with_key' => true,
    ],

    'prune' => [
        // `--prune` deletes only rows the scanner created itself; false = any row of a scanned group.
        'only_scanned' => true,
    ],

    'navigation' => [
        'group' => null,
        'sort' => null,
        'icon' => 'heroicon-o-language',
        'badge' => false,
    ],
];
