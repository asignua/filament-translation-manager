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
     * The table behind the model. `language_lines` is spatie's own; a host with a table prefix (`ee_translations`)
     * changes it here. Used by the default model and by the published migration stubs.
     */
    'table' => 'language_lines',

    /*
     * Limit everything the plugin shows and scans to these groups (`['*']` = only the JSON-style keys of `__('Hello')`).
     * `null` = every group.
     */
    'groups' => null,

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

    /*
     * Row status. `confirmed_at` ("an editor checked this row, it is right as it is") takes a row out of "missing";
     * `orphaned_at` is set by the scan when it no longer finds the key. Both are nullable columns added by the
     * `add_status_columns_to_language_lines_table` migration stub. `false` = no status column, no status filters.
     */
    'status' => [
        'enabled' => true,
    ],

    'missing' => [
        // A text equal to the key counts as missing too (a scan seeds the key as a placeholder text). A confirmed
        // row is never missing.
        'value_equal_to_key' => true,
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
        // Also read the project's lang files (php groups and JSON) into the table. `true`: every key of the files becomes a row;
        // `'known'`: the file texts only fill the rows of keys the CODE uses (a key only in a file gets no row and is not "found",
        // so `--prune` still marks it); `false`: do not read the files.
        'include_lang_files' => true,
        // The longest key a row may have, in CHARACTERS. 255 = the default `key` column (varchar(255)); `null` = unlimited
        // (a host whose key column is TEXT). Longer keys are skipped and counted, and their existing rows are not orphaned.
        'max_key_length' => 255,
        // JSON-style rows get the key itself as the text of the default locale, so they are not "missing" there.
        'fill_default_locale_with_key' => true,
        // ... or in EVERY locale (the placeholder is then the key everywhere; pair it with `missing.value_equal_to_key`).
        // Useful when a missing locale must not fall back to another language's text at run time.
        'fill_all_locales_with_key' => false,
        // Skip JSON-style keys that look like an unknown `group.item` identifier (`auth.failed`, `ee.Save`, `ee.Publish in this
        // language`): a lang key whose file does not exist is not a sentence worth a row.
        'skip_group_like_keys' => false,
    ],

    'prune' => [
        // What `--prune` does with a row whose key is gone from the code and the lang files:
        //  - `mark`   sets `orphaned_at` (and clears it when the key is found again). Nothing is deleted - strings built
        //             at run time (`__($key)`) cannot be found. Needs `status.enabled`; without it the mode is `delete`.
        //  - `delete` deletes the row (see `only_scanned`).
        'mode' => 'mark',
        // `delete` mode: delete only rows the scanner created itself; false = any row of a scanned group.
        'only_scanned' => true,
    ],

    'import' => [
        // The largest import file, in kilobytes.
        'max_size' => 2048,
    ],

    'navigation' => [
        'group' => null,
        'sort' => null,
        'icon' => 'heroicon-o-language',
        'badge' => false,
    ],
];
