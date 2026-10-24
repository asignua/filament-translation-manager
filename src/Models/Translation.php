<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Models;

use Asignua\FilamentTranslationManager\Casts\UnescapedJson;
use Asignua\FilamentTranslationManager\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\TranslationLoader\LanguageLine;

/**
 * A row of `language_lines`: group + key + one text per locale. Everything the plugin writes goes through this
 * model (never a raw query), because spatie caches every group/locale forever and only the model events flush it.
 *
 * Status (columns added by the `add_status_columns_to_language_lines_table` migration, see config `status.enabled`):
 * `confirmed_at` - an editor checked the row, it is right as it is (a row is then never "missing");
 * `orphaned_at` - the last scan did not find the key in the code and the lang files any more.
 *
 * @property int $id
 * @property string $group
 * @property string $key
 * @property array<string, string>|null $text
 * @property bool $created_by_scan
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $orphaned_at
 */
class Translation extends LanguageLine
{
    public const string STATUS_OK = 'ok';

    public const string STATUS_MISSING = 'missing';

    public const string STATUS_ORPHANED = 'orphaned';

    /** @var array<string, string> */
    protected $casts = [
        // Not the stock `array`: that one escapes Cyrillic into \uXXXX.
        'text' => UnescapedJson::class,
        'created_by_scan' => 'boolean',
        'confirmed_at' => 'datetime',
        'orphaned_at' => 'datetime',
    ];

    /**
     * spatie's model has no explicit table (it is named LanguageLine); ours must not guess `translations`.
     * The name is the plugin's `table` config, so a host with a prefix changes one value (or overrides this method).
     */
    public function getTable(): string
    {
        return (string) config('filament-translation-manager.table', 'language_lines');
    }

    public static function statusEnabled(): bool
    {
        return (bool) config('filament-translation-manager.status.enabled', true);
    }

    public static function valueEqualToKeyIsMissing(): bool
    {
        return (bool) config('filament-translation-manager.missing.value_equal_to_key', true);
    }

    /**
     * The groups the plugin works with (config `groups`), or null for all of them.
     *
     * @return list<string>|null
     */
    public static function managedGroups(): ?array
    {
        $groups = config('filament-translation-manager.groups');

        return is_array($groups) && $groups !== [] ? array_values(array_map('strval', $groups)) : null;
    }

    /**
     * The text of ONE locale, without spatie's fallback to app.fallback_locale
     * (getTranslation() would make a German row look translated when only English exists).
     */
    public function textFor(string $locale): ?string
    {
        $value = $this->text[$locale] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * No text in the locale: no entry, an empty string, and (config `missing.value_equal_to_key`) the key itself,
     * which is what a scan leaves as a placeholder. A confirmed row is never missing.
     */
    public function isMissingIn(string $locale): bool
    {
        if ($this->isConfirmed()) {
            return false;
        }

        $text = $this->textFor($locale);

        return $text === null || (static::valueEqualToKeyIsMissing() && $text === $this->key);
    }

    /**
     * Missing in at least one of the configured locales (or of the given ones), and not confirmed.
     *
     * @param list<string>|null $locales
     */
    public function isMissing(?array $locales = null): bool
    {
        foreach ($locales ?? Locales::codes() as $locale) {
            if ($this->isMissingIn($locale)) {
                return true;
            }
        }

        return false;
    }

    public function isConfirmed(): bool
    {
        return static::statusEnabled() && $this->confirmed_at !== null;
    }

    public function isOrphaned(): bool
    {
        return static::statusEnabled() && $this->orphaned_at !== null;
    }

    /**
     * The badge of the table: `orphaned` (not in the code any more) beats `missing` beats `ok`.
     */
    public function status(): string
    {
        return match (true) {
            $this->isOrphaned() => self::STATUS_ORPHANED,
            $this->isMissing() => self::STATUS_MISSING,
            default => self::STATUS_OK,
        };
    }

    /**
     * Rows with no text in the locale (no entry, null or empty string; also a text equal to the key when
     * `missing.value_equal_to_key` is on), except confirmed rows.
     *
     * The JSON path is written as `text->locale`: the query builder compiles it per driver
     * (`json_unquote(json_extract(...))` on MySQL/MariaDB, `json_extract` on SQLite, `->>` on PostgreSQL),
     * so no raw JSON function is needed.
     *
     * @param Builder<static> $query
     */
    public function scopeMissingIn(Builder $query, string $locale): void
    {
        $path = 'text->'.$locale;

        $query->where(function (Builder $query) use ($path): void {
            $query->whereNull($path)->orWhere($path, '');

            if (static::valueEqualToKeyIsMissing()) {
                $query->orWhereColumn($path, 'key');
            }
        });

        if (static::statusEnabled()) {
            $query->whereNull('confirmed_at');
        }
    }

    /**
     * Rows missing a text in at least one of the locales (default: the configured ones).
     *
     * @param Builder<static>   $query
     * @param list<string>|null $locales
     */
    public function scopeMissingInAny(Builder $query, ?array $locales = null): void
    {
        $locales ??= Locales::codes();

        $query->where(function (Builder $query) use ($locales): void {
            foreach ($locales as $locale) {
                $query->orWhere(fn (Builder $q) => $q->missingIn($locale));
            }
        });
    }

    /**
     * @param Builder<static> $query
     */
    public function scopeOrphaned(Builder $query): void
    {
        $query->whereNotNull('orphaned_at');
    }

    /**
     * @param Builder<static> $query
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->whereNotNull('confirmed_at');
    }

    /**
     * Limits the query to the configured groups (`groups`); no-op when every group is managed.
     *
     * @param Builder<static> $query
     */
    public function scopeInManagedGroups(Builder $query): void
    {
        $groups = static::managedGroups();

        if ($groups !== null) {
            $query->whereIn('group', $groups);
        }
    }

    /**
     * Spatie flushes only the locales present in the CURRENT text. A locale that was just cleared (or never
     * configured) would keep serving the old string from the cache, so every plausible locale is flushed.
     */
    public function flushGroupCache(): void
    {
        $locales = array_merge(
            array_keys($this->text ?? []),
            Locales::codes(),
            [(string) config('app.locale'), (string) config('app.fallback_locale')],
        );

        foreach (array_unique($locales) as $locale) {
            Cache::forget(static::getCacheKey($this->group, (string) $locale));
        }
    }
}
