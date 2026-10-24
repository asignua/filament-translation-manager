<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * The language switcher's state: the chosen locale lives in the session (and optionally on a user column) and is
 * applied to the app on every panel request.
 */
final class LocaleSwitcher
{
    public static function sessionKey(): string
    {
        return (string) config('filament-translation-manager.language_switcher_options.session_key', 'translation-manager.locale');
    }

    public static function userColumn(): ?string
    {
        $column = config('filament-translation-manager.language_switcher_options.user_column');

        return is_string($column) && $column !== '' ? $column : null;
    }

    /**
     * The locale chosen for this request, or null. Only a configured locale is accepted.
     */
    public static function chosen(): ?string
    {
        $locale = null;

        if (app()->bound('session') && session()->isStarted()) {
            $stored = session()->get(self::sessionKey());
            $locale = is_string($stored) ? $stored : null;
        }

        if ($locale === null && ($column = self::userColumn()) !== null) {
            $user = auth()->user();
            $value = $user instanceof Model ? $user->getAttribute($column) : null;
            $locale = is_string($value) ? $value : null;
        }

        return $locale !== null && in_array($locale, Locales::codes(), true) ? $locale : null;
    }

    public static function apply(): void
    {
        $locale = self::chosen();

        if ($locale !== null) {
            app()->setLocale($locale);
        }
    }

    public static function remember(string $locale, ?Authenticatable $user): void
    {
        session()->put(self::sessionKey(), $locale);

        $column = self::userColumn();

        if ($column !== null && $user instanceof Model) {
            $user->forceFill([$column => $locale])->save();
        }
    }
}
