# Changelog

All notable changes to `asignua/filament-translation-manager` are documented here.

## Unreleased

- Translations resource on spatie/laravel-translation-loader's `language_lines`: table with a column per language, search across
  key and texts, "Missing in" filters, edit and create forms, gated delete.
- Scan (`translation-manager:scan [--prune] [--dry-run]` and a header action) for `__()`, `trans()`, `trans_choice()`, `@lang`
  and `Lang::get()` plus lang files (PHP groups and JSON); one shared literal parser; additive, never overwrites edited texts.
- Quick translate page, JSON export / import per language, opt-in top-bar language switcher.
- `Translation` model: Cyrillic stored readable, full cache flush on save and delete.
- Gates `manageTranslations` and `deleteTranslations` (closed unless defined); kenepa/translation-manager config keys accepted.
- English, Ukrainian, Polish, German, French, Spanish, Italian, Dutch, Brazilian Portuguese and Turkish.
