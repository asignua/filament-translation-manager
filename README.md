# Filament Translation Manager

[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/badges/StandWithUkraine.svg)](https://stand-with-ukraine.pp.ua)

<img class="filament-hidden" src="https://raw.githubusercontent.com/asignua/filament-translation-manager/main/art/cover.jpg" alt="Filament Translation Manager">

[![Tests](https://img.shields.io/github/actions/workflow/status/asignua/filament-translation-manager/tests.yml?branch=main&label=tests)](https://github.com/asignua/filament-translation-manager/actions/workflows/tests.yml)

Edit every `__('...')` string of your Laravel app from the Filament panel, per language, without a deploy. Texts live in the
database ([spatie/laravel-translation-loader](https://github.com/spatie/laravel-translation-loader)) and **override** the lang
files, so the files stay your defaults. A scan finds the strings in your code for you, a filter shows what is still missing in a
language, and a quick-translate page walks you through the gaps one by one.

It is the successor of `kenepa/translation-manager`, which never shipped Filament 5, on the same storage (`language_lines`), the
same gate and the same config keys, so an existing project moves over without touching its data (see
[Migrating from kenepa/translation-manager](#migrating-from-kenepatranslation-manager)).

- Searchable table: group, key and one column per language. One search box looks in the key **and** in every text.
- "Missing in" filter (pick one or several languages) and "Missing in any language".
- Edit form with a field per language; the lang-file value is shown under it as a hint.
- **Scan**: finds `__()`, `trans()`, `trans_choice()`, `@lang` and `Lang::get()` in your code, plus your lang files (PHP groups and
  JSON), and adds the missing rows. A text you edited is never overwritten. Also as `php artisan translation-manager:scan`.
- **Quick translate** page: the next missing text for a language, the other languages shown as the source, "Save & next".
- JSON export / import per language (the format of `lang/xx.json`).
- Optional language switcher in the top bar (session, and optionally a user column). Off by default.
- Closed by default: nobody opens it until you define a gate. Deleting has its own, stricter gate.
- Cyrillic and other non-ASCII text is stored **readable** in the database, not as `До...`.
- English, Ukrainian, Polish, German, French, Spanish, Italian, Dutch, Brazilian Portuguese and Turkish.

## Screenshots

![The translations table](https://raw.githubusercontent.com/asignua/filament-translation-manager/main/art/table.jpg)
![The language switcher in the top bar](https://raw.githubusercontent.com/asignua/filament-translation-manager/main/art/switcher.jpg)
![Quick translate](https://raw.githubusercontent.com/asignua/filament-translation-manager/main/art/quick-translate.jpg)

![Quick translate in dark mode](https://raw.githubusercontent.com/asignua/filament-translation-manager/main/art/quick-translate-dark.jpg)
![The edit form](https://raw.githubusercontent.com/asignua/filament-translation-manager/main/art/edit.jpg)

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Filament 5

## Installation

```bash
composer require asignua/filament-translation-manager
php artisan vendor:publish --tag=filament-translation-manager-migrations
php artisan migrate
```

The migrations are publishable stubs. The first creates the `language_lines` table the way spatie's own migration does, and
**does nothing when the table already exists**, so do not also publish spatie's. The second adds the column `--prune` uses to
tell scanner-created rows from yours, the third the nullable `confirmed_at` / `orphaned_at` status columns (skip it and set
`status.enabled` to `false` if you do not want the status). Optionally publish the config (`--tag=filament-translation-manager-config`).

Register the plugin on your panel and tell it the languages:

```php
use Asignua\FilamentTranslationManager\TranslationManagerPlugin;

$panel->plugin(
    TranslationManagerPlugin::make()
        ->locales(['en' => 'English', 'uk' => 'Українська', 'de' => 'Deutsch'])
);
```

Then open the door. The plugin is closed until a gate exists (an undefined ability means "no"):

```php
use Illuminate\Support\Facades\Gate;

Gate::define('manageTranslations', fn (User $user): bool => $user->isAdmin());
Gate::define('deleteTranslations', fn (User $user): bool => $user->isAdmin()); // single and bulk delete
```

Open **Translations** in the navigation and press **Scan code** once.

## Usage

**Scan.** The header action (or `php artisan translation-manager:scan`) reads the folders in `scan.paths` (default `app`,
`resources/views`, `routes`; `tests`, `vendor`, `node_modules` are skipped) and your lang files, and creates a row for every
string it has not seen. For a PHP group (`messages.checkout.title`) the file text lands in the row; for a JSON-style key
(`__('Add to basket')`) the key itself becomes the text of the default language. Languages without a text stay **missing**.

- It is additive: an existing text is never overwritten, a gap in an existing row is filled from the lang files.
- `--dry-run` only reports. `--prune` acts on rows whose key is gone from the code **and** the lang files. By default
  (`prune.mode` = `mark`) it only **marks** them (`orphaned_at`, shown as "Not in code" and filterable); the mark is cleared
  by the next scan that finds the key again, and nothing is deleted. With `prune.mode` = `delete` (the "Also delete rows no
  longer found" toggle needs the delete gate) it deletes them, by default only rows the scanner created itself
  (`prune.only_scanned`), never ones you typed. Strings built at run time (`__($key)`, `__('a'.$b)`) cannot be found: review before pruning.
- The scan skips what the database loader cannot serve: namespaced keys (`pkg::group.key`) and keys over 255 characters.
  They are counted as "skipped".

**Status.** Every row has a badge: **OK**, **Missing** (a text is absent in a configured language) or **Not in code** (the last
scan did not find the key). A text that equals the key counts as missing too (`missing.value_equal_to_key`, on by default):
that is the placeholder a scan leaves for a string nobody translated yet. When such a text is right on purpose (a brand
name, a word that is the same in every language) press **Confirm** in the row menu; a confirmed row is never missing.
Filters: Missing in…, Missing in any locale, Not found in code, Confirmed.

**Find the gaps.** Filter **Missing in** and pick a language, or use **Missing in any language**. The navigation badge
(`->navigationBadge()`) shows how many rows are incomplete.

**Quick translate.** The page walks the rows that miss a text in the chosen language, oldest first. The other languages are shown
as the source; type and press **Save & next** (Ctrl/Cmd+Enter works too), or **Skip**. `?locale=de` opens it on a language.

**Export / import.** Export one language as `xx.json`: JSON-style rows by their key, group rows as `group.key`. Import takes the
same shape; it fills gaps and never overwrites unless you switch **Overwrite existing texts** on.

**Language switcher.** `->languageSwitcher()` adds a language menu to the top bar. The choice is kept in the session and
applied when the panel boots; set `language_switcher_options.user_column` to a string column of your user model to remember it there as well.
To apply it on routes outside the panel too, add `Asignua\FilamentTranslationManager\Http\Middleware\SetLocale` after `StartSession`.

## Configuration

Everything is fluent on the plugin and falls back to `config/filament-translation-manager.php`:

```php
TranslationManagerPlugin::make()
    ->locales([                                  // or ->availableLocales([...]) as in kenepa
        ['code' => 'en', 'name' => 'English', 'flag' => 'gb'],   // the flag is a country code, shown as an emoji
        ['code' => 'uk', 'name' => 'Українська', 'flag' => 'ua'],
    ])
    ->gate('manageTranslations')                 // kenepa's ability was 'use-translation-manager'
    ->deleteGate('deleteTranslations')
    ->authorize(fn (User $user) => $user->isAdmin())  // replaces both gates (also accepts a bool)
    ->languageSwitcher()
    ->quickTranslate()                           // on by default; ->quickTranslate(false) hides the page
    ->navigationGroup('Settings')->navigationSort(90)->navigationIcon('heroicon-o-language')
    ->navigationBadge();
```

`locales` also accepts a list of codes (`['en', 'uk']`; the name comes from `intl`) or a `code => name` map. Without it the
plugin uses `app.locale` and `app.fallback_locale`.

Config keys: `table`, `groups`, `status.enabled`, `missing.value_equal_to_key`, `locales` (alias `available_locales`), `default_locale`, `model`, `gate`, `delete_gate`, `language_switcher`,
`language_switcher_options.{user_column,session_key}`, `quick_translate.enabled`, `scan.{paths,extensions,exclude,include_lang_files,fill_default_locale_with_key,fill_all_locales_with_key,skip_group_like_keys}`,
`prune.{mode,only_scanned}`, `navigation.{group,sort,icon,badge}`.

### Wrapping the plugin in a host

A host with its own table prefix, its own role system or only some of the groups configures, not forks:

```php
config([
    'filament-translation-manager.table' => 'ee_translations',            // default model reads it
    'filament-translation-manager.model' => App\Models\Translation::class, // may extend the plugin model
    'filament-translation-manager.groups' => ['*'],                       // only JSON-style keys: list, scan, quick translate
    'filament-translation-manager.scan.paths' => [base_path('app'), base_path('resources/views')],
    'filament-translation-manager.scan.include_lang_files' => false,
]);

Locales::resolveUsing(fn () => MyLanguages::all());  // read on every call; same shapes as ->locales()

$panel->plugin(TranslationManagerPlugin::make()
    ->resource(MyTranslationResource::class)         // or ->resource(false) when the panel discovers it
    ->authorize(fn () => $user->isAdmin())
    ->navigationGroup(fn () => __('System'))->navigationLabel(fn () => __('Translations')));
```

`MyTranslationResource` extends `TranslationResource` and may override `canCreate()`, `scopeQuery(Builder)`, `extraColumns()` and
`extraRecordActions()`; give it its own pages (small subclasses with `$resource` set) when you add header actions. The model may
override `getTable()`; the status and "missing" logic live in scopes (`missingIn`, `missingInAny`, `orphaned`, `confirmed`,
`inManagedGroups`) so a host query can reuse them. JSON paths are written as `text->uk` and compiled by the query builder per
driver, so the same code runs on SQLite, MySQL/MariaDB and PostgreSQL (`SqlGrammarTest` checks the generated SQL).

### The model

Rows are served through `Asignua\FilamentTranslationManager\Models\Translation`, a subclass of spatie's `LanguageLine`. The plugin
sets spatie's `translation-loader.model` to it when you have not chosen another. Two reasons it is not the stock model: the stock
`array` cast escapes Cyrillic into `\uXXXX` in the database, and spatie flushes the cache only for the languages left in a row after
an edit, so clearing a language would keep serving the old text. If you use your own model, extend `LanguageLine` and set `model`.

## Migrating from kenepa/translation-manager

The data does not move: both use spatie's `language_lines` table. Your existing rows keep working, and rows with escaped Cyrillic
are read as before and become readable the next time they are saved.

| kenepa/translation-manager | this plugin |
| --- | --- |
| `Kenepa\TranslationManager\TranslationManagerPlugin::make()` | `Asignua\FilamentTranslationManager\TranslationManagerPlugin::make()` |
| config `available_locales` (`code`, `name`, `flag`) | same shape, also `->locales()` / `->availableLocales()`; the flag is a country code |
| config `language_switcher` | same key (default `false`), or `->languageSwitcher()` |
| gate `use-translation-manager` | `manageTranslations`; to keep the old one: `->gate('use-translation-manager')` |
| "Synchronize" / publish translations | **Scan code** action and `translation-manager:scan` (plus `--prune`, `--dry-run`) |
| spatie migration (`vendor:publish --provider=Spatie\TranslationLoader...`) | not needed; ours skips an existing table |

1. `composer remove kenepa/translation-manager && composer require asignua/filament-translation-manager`
2. Swap the plugin class in your panel provider; keep your locales.
3. Gate: rename the ability or call `->gate('use-translation-manager')`. Add `deleteTranslations` (or delete stays disabled).
4. `php artisan vendor:publish --tag=filament-translation-manager-migrations && php artisan migrate` (adds the `created_by_scan` column).
5. Remove the old config file if you published it. This plugin reads `config/filament-translation-manager.php`.

This list follows kenepa's README; check it against the version you run. Anything kenepa had that is not here (for example its
own languages seeding) is intentionally left out.

## Gotchas

- **Closed by default.** No gate, no access, and no error: the navigation entry just is not there. Define `manageTranslations`.
- **Always write through the model.** Spatie caches every group and language forever; only the model's `saved`/`deleted` events
  flush it. A raw `DB::table('language_lines')->update(...)` leaves stale text on the site until the cache is cleared.
- **Dots are fine, `::` is not.** `__('Mr. Smith')` is a JSON-style key and works. Namespaced keys (`pkg::group.key`) are
  served by the package that owns them and cannot be overridden from the database loader.
- **A key that looks like `group.key`** is treated as a group row only when a lang file `group.php` exists (or the group already
  has rows); otherwise it is a JSON-style key with the whole string.
- **Dynamic keys are invisible to the scan.** Add them by hand or keep them in lang files, and review before `--prune`.
- **Stored text is limited to 255-character keys** (the unique index is `group` + `key`).
- **Two scanners must never disagree.** The plugin has one literal parser for everything (`Support\TranslationLiteral`), tested with
  escaped quotes and apostrophes inside double-quoted strings; do not add a second regex.
- **Already published spatie's migration?** Fine. Ours skips an existing table. If you publish spatie's *after* ours it fails
  with "table exists": delete that file.

## Translations

The interface ships in English, Ukrainian, German, Spanish, French, Italian, Dutch, Polish, Brazilian Portuguese and Turkish
under the `filament-translation-manager::filament-translation-manager` namespace. A test keeps every language in step with the English keys. Override a
string by publishing the translations (`--tag=filament-translation-manager-translations`) and editing the copy in
`lang/vendor/filament-translation-manager`.

## AI agents

The package ships [Laravel Boost](https://laravel.com/docs/boost) guidelines (`resources/boost/guidelines/core.blade.php`) so a
coding agent wires it up correctly.

## Testing

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/pint --test
```

Try it by hand with the workbench:

```bash
vendor/bin/testbench workbench:build
vendor/bin/testbench filament:assets
vendor/bin/testbench db:seed --class="Workbench\Database\Seeders\DemoSeeder"
vendor/bin/testbench serve --host=0.0.0.0
```

Log in at `/admin` as `emma@example.com` / `password`.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
