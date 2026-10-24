<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Models;

use Asignua\FilamentTranslationManager\Casts\UnescapedJson;
use Asignua\FilamentTranslationManager\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Spatie\TranslationLoader\LanguageLine;

/**
 * A row of `language_lines`: group + key + one text per locale. Everything the plugin writes goes through this
 * model (never a raw query), because spatie caches every group/locale forever and only the model events flush it.
 *
 * @property int $id
 * @property string $group
 * @property string $key
 * @property array<string, string>|null $text
 * @property bool $created_by_scan
 */
class Translation extends LanguageLine
{
    // spatie's model has no explicit table (it is named LanguageLine); ours must not guess `translations`.
    protected $table = 'language_lines';

    /** @var array<string, string> */
    protected $casts = [
        // Not the stock `array`: that one escapes Cyrillic into \uXXXX.
        'text' => UnescapedJson::class,
        'created_by_scan' => 'boolean',
    ];

    /**
     * The text of ONE locale, without spatie's fallback to app.fallback_locale
     * (getTranslation() would make a German row look translated when only English exists).
     */
    public function textFor(string $locale): ?string
    {
        $value = $this->text[$locale] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function isMissingIn(string $locale): bool
    {
        return $this->textFor($locale) === null;
    }

    /**
     * Rows with no text in the locale (no entry, null or empty string).
     *
     * @param Builder<static> $query
     */
    public function scopeMissingIn(Builder $query, string $locale): void
    {
        $query->where(function (Builder $query) use ($locale): void {
            $query->whereNull('text->'.$locale)->orWhere('text->'.$locale, '');
        });
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
