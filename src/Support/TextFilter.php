<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

/**
 * A cleared field does not store "": the locale becomes `null` when the row had it (so it counts as "missing" and a later
 * scan sees an EXPLICIT choice and does not refill it with the key), and is simply left out when the row never had it.
 * Texts of locales that are not configured (no field in the form) survive an edit.
 */
final class TextFilter
{
    /**
     * @param array<string, mixed>            $data
     * @param array<string, string|null>|null $existing the stored text of the row being edited
     *
     * @return array<string, mixed>
     */
    public static function clean(array $data, ?array $existing = null): array
    {
        $text = is_array($data['text'] ?? null) ? $data['text'] : [];

        $kept = [];

        foreach ($text as $locale => $value) {
            if (is_string($value) && trim($value) !== '') {
                $kept[$locale] = $value;
            } elseif (array_key_exists($locale, $existing ?? [])) {
                $kept[$locale] = null;
            }
        }

        $text = $kept;

        foreach ($existing ?? [] as $locale => $value) {
            if (!array_key_exists($locale, Locales::all())) {
                $text[$locale] = $value;
            }
        }

        $data['text'] = $text;

        return $data;
    }
}
