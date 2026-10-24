<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

/**
 * A cleared field removes the locale from the row (so it counts as "missing" again) instead of storing "".
 * Texts of locales that are not configured (no field in the form) survive an edit.
 */
final class TextFilter
{
    /**
     * @param array<string, mixed>       $data
     * @param array<string, string>|null $existing the stored text of the row being edited
     *
     * @return array<string, mixed>
     */
    public static function clean(array $data, ?array $existing = null): array
    {
        $text = is_array($data['text'] ?? null) ? $data['text'] : [];

        $text = array_filter(
            $text,
            static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
        );

        foreach ($existing ?? [] as $locale => $value) {
            if (!array_key_exists($locale, Locales::all())) {
                $text[$locale] = $value;
            }
        }

        $data['text'] = $text;

        return $data;
    }
}
