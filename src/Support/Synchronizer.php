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
     * Whether `--prune` marks rows (`orphaned_at`) instead of deleting them: config `prune.mode`, and only when the
     * status columns are in use.
     */
    public static function marksOrphans(): bool
    {
        return config('filament-translation-manager.prune.mode', 'mark') === 'mark' && Translation::statusEnabled();
    }

    /**
     * @param bool $prune  handle rows that no longer exist in code or lang files: mark them `orphaned_at` (and clear the
     *                     mark of the ones found again) or delete them, per config `prune.mode`
     * @param bool $dryRun count only, write nothing
     */
    public function sync(bool $prune = false, bool $dryRun = false): SyncResult
    {
        $model = $this->model();
        $locales = Locales::codes();
        $default = Locales::default();
        $fillDefault = (bool) config('filament-translation-manager.scan.fill_default_locale_with_key', true);
        $fillAll = (bool) config('filament-translation-manager.scan.fill_all_locales_with_key', false);
        $skipGroupLike = (bool) config('filament-translation-manager.scan.skip_group_like_keys', false);
        $includeFiles = (bool) config('filament-translation-manager.scan.include_lang_files', true);
        $managed = $model::managedGroups();

        /** @var \Illuminate\Database\Eloquent\Collection<int, Translation> $existing */
        $existing = $model::query()->inManagedGroups()->get();
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

            if ($pair === null || strlen($pair[1]) > self::MAX_KEY_LENGTH || ($skipGroupLike && $pair[0] === '*' && KeyResolver::looksLikeGroupKey($pair[1]))) {
                $skipped++;

                continue;
            }

            if ($managed !== null && !in_array($pair[0], $managed, true)) {
                continue;
            }

            $found[$this->id($pair[0], $pair[1])] ??= [$pair[0], $pair[1], []];
        }

        if ($includeFiles) {
            foreach ($locales as $locale) {
                foreach ($this->files->lines($locale) as $group => $lines) {
                    if ($managed !== null && !in_array((string) $group, $managed, true)) {
                        continue;
                    }

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

                $placeholder = $group === '*' && (($fillDefault && $locale === $default) || $fillAll);
                $value = $fileTexts[$locale] ?? ($placeholder ? $key : null);

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
        $marked = 0;
        $restored = 0;

        if (Translation::statusEnabled()) {
            // A key that is found again is no longer an orphan, with or without --prune.
            foreach ($rows as $id => $row) {
                if (isset($found[$id]) && $row->orphaned_at !== null) {
                    $restored++;

                    if (!$dryRun) {
                        $row->orphaned_at = null;
                        $row->save();
                    }
                }
            }
        }

        if ($prune && self::marksOrphans()) {
            // Non-destructive: every scanned row takes part (not only the ones the scanner created) and nothing is deleted.
            foreach ($rows as $id => $row) {
                if (!isset($found[$id]) && $row->orphaned_at === null) {
                    $marked++;

                    if (!$dryRun) {
                        $row->orphaned_at = now();
                        $row->save();
                    }
                }
            }
        } elseif ($prune) {
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

        return new SyncResult(count($found), $created, $filled, $pruned, $skipped, $marked, $restored);
    }

    private function id(string $group, string $key): string
    {
        return $group."\0".$key;
    }
}
