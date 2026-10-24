<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

use Closure;
use Locale;

/**
 * The locales the manager works with, normalised to `code => ['code', 'name', 'flag']`.
 */
final class Locales
{
    /** @var array<string, array{code: string, name: string, flag: string|null}>|null */
    private static ?array $override = null;

    private static ?Closure $resolver = null;

    /**
     * The locales from a closure, read on every call (a host whose language list can change after boot passes it
     * here instead of a config value). Same shapes as `locales()`. The plugin's `locales()` still wins.
     *
     * @param (Closure(): array<array-key, mixed>)|null $resolver
     */
    public static function resolveUsing(?Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    /**
     * @param array<array-key, mixed>|null $locales
     */
    public static function override(?array $locales): void
    {
        self::$override = $locales === null ? null : self::normalise($locales);
    }

    /**
     * @return array<string, array{code: string, name: string, flag: string|null}>
     */
    public static function all(): array
    {
        if (self::$override !== null) {
            return self::$override;
        }

        if (self::$resolver !== null) {
            return self::normalise((self::$resolver)());
        }

        $configured = config('filament-translation-manager.locales') ?? config('filament-translation-manager.available_locales');

        if (!is_array($configured) || $configured === []) {
            $configured = array_values(array_unique(array_filter([
                (string) config('app.locale'),
                (string) config('app.fallback_locale'),
            ])));
        }

        return self::normalise($configured);
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<string, string> code => "flag Name"
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::all() as $code => $locale) {
            $options[$code] = self::label($code);
        }

        return $options;
    }

    public static function label(string $code): string
    {
        $locale = self::all()[$code] ?? null;

        if ($locale === null) {
            return $code;
        }

        return trim(($locale['flag'] !== null ? self::flagEmoji($locale['flag']).' ' : '').$locale['name']);
    }

    public static function default(): string
    {
        $default = config('filament-translation-manager.default_locale');

        return is_string($default) && $default !== '' ? $default : (string) config('app.locale');
    }

    /**
     * Country code ("gb") to the flag emoji (regional indicator letters). Anything else gives no flag.
     */
    public static function flagEmoji(string $flag): string
    {
        $flag = strtoupper($flag);

        if (preg_match('/^[A-Z]{2}$/', $flag) !== 1) {
            return '';
        }

        $emoji = '';

        foreach (str_split($flag) as $letter) {
            $emoji .= mb_chr(0x1F1E6 + ord($letter) - ord('A'), 'UTF-8');
        }

        return $emoji;
    }

    /**
     * @param array<array-key, mixed> $locales
     *
     * @return array<string, array{code: string, name: string, flag: string|null}>
     */
    private static function normalise(array $locales): array
    {
        $normalised = [];

        foreach ($locales as $key => $value) {
            if (is_array($value)) {
                $code = (string) ($value['code'] ?? (is_string($key) ? $key : ''));
                $name = isset($value['name']) ? (string) $value['name'] : self::nameFor($code);
                $flag = isset($value['flag']) && is_string($value['flag']) ? $value['flag'] : null;
            } elseif (is_string($key)) {
                [$code, $name, $flag] = [$key, (string) $value, null];
            } else {
                [$code, $name, $flag] = [(string) $value, self::nameFor((string) $value), null];
            }

            if ($code === '') {
                continue;
            }

            $normalised[$code] = ['code' => $code, 'name' => $name, 'flag' => $flag];
        }

        return $normalised;
    }

    private static function nameFor(string $code): string
    {
        if (class_exists(Locale::class)) {
            $name = (string) Locale::getDisplayName($code, $code);

            if ($name !== '' && $name !== $code) {
                return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
            }
        }

        return strtoupper($code);
    }
}
