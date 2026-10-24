<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

use Asignua\FilamentTranslationManager\Models\Translation;
use Illuminate\Database\Eloquent\Model;

/**
 * The scan: code literals + lang files become `language_lines` rows. Additive - an existing text is never
 * overwritten - and every write goes through the model (so spatie's cache is flushed).
 */
final class Synchronizer
{
    public const int MAX_KEY_LENGTH = 255;

    public function __construct(
        private readonly CodeScanner $scanner,
        private readonly LangFiles $files,
    ) {}

    public static function make(): self
    {
        return new self(CodeScanner::fromConfig(), new LangFiles);
    }

    /**
     * @return class-string<Translation>
     */
    private function model(): string
    {
        /** @var class-string<Translation> */
        return (string) config('filament-translation-manager.model', Translation::class);
    }

    /**
     * @param bool $prune  delete rows that no longer exist in code or lang files
     * @param bool $dryRun count only, write nothing
     */
    public function sync(bool $prune = false, bool $dryRun = false): SyncResult
    {
        $model = $this->model();
        $locales = Locales::codes();
        $default = Locales::default();
        $fillDefault = (bool) config('filament-translation-manager.scan.fill_default_locale_with_key', true);
        $includeFiles = (bool) config('filament-translation-manager.scan.include_lang_files', true);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Translation> $existing */
        $existing = $model::query()->get();
        $rows = [];

        foreach ($existing as $row) {
            $rows[$this->id($row->group, $row->key)] = $row;
        }

        $groups = array_values(array_unique(array_merge(
            $this->files->groups(),
            $existing->pluck('group')->filter(static fn (string $g): bool => $g !== '*')->all(),
        )));
        $resolver = new KeyResolver($groups);

        // found[id] = [group, key, [locale => file text]]
        $found = [];
        $skipped = 0;

        foreach ($this->scanner->literals() as $literal) {
            $pair = $resolver->resolve($literal);

            if ($pair === null || strlen($pair[1]) > self::MAX_KEY_LENGTH) {
                $skipped++;

                continue;
            }

            $found[$this->id($pair[0], $pair[1])] ??= [$pair[0], $pair[1], []];
        }

        if ($includeFiles) {
            foreach ($locales as $locale) {
                foreach ($this->files->lines($locale) as $group => $lines) {
                    foreach ($lines as $key => $value) {
                        if (strlen($key) > self::MAX_KEY_LENGTH) {
                            $skipped++;

                            continue;
                        }

                        $found[$this->id((string) $group, $key)] ??= [(string) $group, $key, []];
                        $found[$this->id((string) $group, $key)][2][$locale] = $value;
                    }
                }
            }
        }

        $created = 0;
        $filled = 0;

        foreach ($found as $id => [$group, $key, $fileTexts]) {
            $row = $rows[$id] ?? null;
            $text = $row === null ? [] : ($row->text ?? []);
            $changed = false;

            foreach ($locales as $locale) {
                $current = $text[$locale] ?? null;

                if (is_string($current) && $current !== '') {
                    continue;
                }

                $value = $fileTexts[$locale] ?? ($group === '*' && $fillDefault && $locale === $default ? $key : null);

                if ($value !== null && $value !== '') {
                    $text[$locale] = $value;
                    $changed = true;
                }
            }

            if ($row === null) {
                $created++;

                if (!$dryRun) {
                    $row = new $model;
                    $row->group = $group;
                    $row->key = $key;
                    $row->text = $text;
                    $row->created_by_scan = true;
                    $row->save();
                }
            } elseif ($changed) {
                $filled++;

                if (!$dryRun) {
                    $row->text = $text;
                    $row->save();
                }
            }
        }

        $pruned = 0;

        if ($prune) {
            $onlyScanned = (bool) config('filament-translation-manager.prune.only_scanned', true);

            foreach ($rows as $id => $row) {
                if (isset($found[$id]) || ($onlyScanned && !$row->created_by_scan)) {
                    continue;
                }

                $pruned++;

                if (!$dryRun) {
                    $row->delete();
                }
            }
        }

        return new SyncResult(count($found), $created, $filled, $pruned, $skipped);
    }

    private function id(string $group, string $key): string
    {
        return $group."\0".$key;
    }
}
