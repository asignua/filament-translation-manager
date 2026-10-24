# Changelog

All notable changes to `asignua/filament-translation-manager` are documented here.

## Unreleased

- Row status: `confirmed_at` (an editor vouches for the row; never "missing") and `orphaned_at` (the scan no longer finds the key)
  via the `add_status_columns_to_language_lines_table` migration stub; status badge, "Not found in code" / "Confirmed" filters,
  Confirm / Remove confirmation action. `status.enabled` switches it all off.
- `--prune` is non-destructive by default (`prune.mode` = `mark`: sets / clears `orphaned_at`); `delete` keeps the old behaviour.
  A found key clears its mark on any scan.
- "Missing" also counts a text equal to the key (`missing.value_equal_to_key`) and skips confirmed rows; scan options
  `fill_all_locales_with_key` and `skip_group_like_keys`.
- Host extension points: config `table` (the model and the migration stubs read it), config `groups` (limit list, counts, quick
  translate and scan to some groups), `Locales::resolveUsing()`, `TranslationManagerPlugin::resource()`, closures for
  `navigationGroup()` / `navigationLabel()`, resource hooks `scopeQuery()`, `extraColumns()`, `extraRecordActions()`.
- The missing scopes use the query builder's JSON paths (`text->uk`), checked against the MySQL, MariaDB, PostgreSQL and SQLite grammars.

- Translations resource on spatie/laravel-translation-loader's `language_lines`: table with a column per language, search across
  key and texts, "Missing in" filters, edit and create forms, gated delete.
- Scan (`translation-manager:scan [--prune] [--dry-run]` and a header action) for `__()`, `trans()`, `trans_choice()`, `@lang`
  and `Lang::get()` plus lang files (PHP groups and JSON); one shared literal parser; additive, never overwrites edited texts.
- Quick translate page, JSON export / import per language, opt-in top-bar language switcher.
- `Translation` model: Cyrillic stored readable, full cache flush on save and delete.
- Gates `manageTranslations` and `deleteTranslations` (closed unless defined); kenepa/translation-manager config keys accepted.
- English, Ukrainian, Polish, German, French, Spanish, Italian, Dutch, Brazilian Portuguese and Turkish.
