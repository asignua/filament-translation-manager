<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

use Asignua\FilamentTranslationManager\Models\Translation;

/**
 * JSON export / import of ONE locale. The file is a flat `{"address": "text"}` map shaped like Laravel's own
 * `lang/<locale>.json`: JSON rows by their key, group rows as `group.key`.
 */
final class TranslationTransfer
{
    /**
     * @return class-string<Translation>
     */
    private function model(): string
    {
        /** @var class-string<Translation> */
        return (string) config('filament-translation-manager.model', Translation::class);
    }

    /**
     * @return array<string, string>
     */
    public function export(string $locale): array
    {
        $model = $this->model();
        $map = [];

        /** @var \Illuminate\Database\Eloquent\Collection<int, Translation> $rows */
        $rows = $model::query()->orderBy('group')->orderBy('key')->get();

        foreach ($rows as $row) {
            $text = $row->textFor($locale);

            if ($text !== null) {
                $map[KeyResolver::address($row->group, $row->key)] = $text;
            }
        }

        return $map;
    }

    public function exportJson(string $locale): string
    {
        return (string) json_encode(
            $this->export($locale),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * @param array<array-key, mixed> $map
     * @param bool                    $overwrite replace texts that already exist (default: fill the gaps only)
     *
     * @return array{created: int, updated: int, skipped: int}
     */
    public function import(string $locale, array $map, bool $overwrite = false): array
    {
        $model = $this->model();
        $existing = [];
        $groups = [];

        /** @var \Illuminate\Database\Eloquent\Collection<int, Translation> $rows */
        $rows = $model::query()->get();

        foreach ($rows as $row) {
            $existing[$row->group."\0".$row->key] = $row;

            if ($row->group !== '*') {
                $groups[$row->group] = true;
            }
        }

        $resolver = new KeyResolver(array_values(array_unique(array_merge(array_map('strval', array_keys($groups)), (new LangFiles)->groups()))));
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($map as $address => $value) {
            $pair = $resolver->resolve((string) $address);

            if ($pair === null || !is_string($value) || $value === '' || strlen($pair[1]) > Synchronizer::MAX_KEY_LENGTH) {
                $result['skipped']++;

                continue;
            }

            $row = $existing[$pair[0]."\0".$pair[1]] ?? null;

            if ($row === null) {
                $row = new $model;
                $row->group = $pair[0];
                $row->key = $pair[1];
                $row->text = [$locale => $value];
                $row->save();
                $existing[$pair[0]."\0".$pair[1]] = $row;
                $result['created']++;

                continue;
            }

            if (!$overwrite && $row->textFor($locale) !== null) {
                $result['skipped']++;

                continue;
            }

            if ($row->textFor($locale) === $value) {
                continue;
            }

            $row->text = array_merge($row->text ?? [], [$locale => $value]);
            $row->save();
            $result['updated']++;
        }

        return $result;
    }
}
