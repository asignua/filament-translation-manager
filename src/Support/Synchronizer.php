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
        // true: every key of the lang files becomes a row; 'known': the file texts only fill rows whose key the CODE uses
        // (a key that lives in a file but nowhere in the code stays out, and is not "found").
        $filesMode = config('filament-translation-manager.scan.include_lang_files', true);
        $includeFiles = $filesMode !== false;
        $codeOnly = $filesMode === 'known';
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
        // Keys the scan SAW but cannot write (too long): their rows exist and must not be taken for orphans.
        $seen = [];
        $skipped = 0;

        foreach ($this->scanner->literals() as $literal) {
            $pair = $resolver->resolve($literal);

            if ($pair !== null && $managed !== null && !in_array($pair[0], $managed, true)) {
                continue;
            }

            if ($pair !== null && Writes::keyTooLong($pair[1])) {
                $seen[$this->id($pair[0], $pair[1])] = true;
                $skipped++;

                continue;
            }

            if ($pair === null || ($skipGroupLike && $pair[0] === '*' && KeyResolver::looksLikeGroupKey($pair[1]))) {
                $skipped++;

                continue;
            }

            $found[$this->id($pair[0], $pair[1])] ??= [$pair[0], $pair[1], []];
        }

        $fileLines = [];

        if ($includeFiles) {
            foreach ($locales as $locale) {
                foreach ($this->files->lines($locale) as $group => $lines) {
                    if ($managed !== null && !in_array((string) $group, $managed, true)) {
                        continue;
                    }

                    foreach ($lines as $key => $value) {
                        if (Writes::keyTooLong((string) $key)) {
                            $seen[$this->id((string) $group, (string) $key)] = true;
                            $skipped++;

                            continue;
                        }

                        $id = $this->id((string) $group, $key);

                        if ($codeOnly) {
                            $fileLines[$id] ??= [(string) $group, $key, []];
                            $fileLines[$id][2][$locale] = $value;
                        } else {
                            $found[$id] ??= [(string) $group, $key, []];
                            $found[$id][2][$locale] = $value;
                        }
                    }
                }
            }
        }

        if ($codeOnly) {
            foreach ($found as $id => $entry) {
                $found[$id][2] = $fileLines[$id][2] ?? [];
            }
        }

        $created = 0;
        $filled = 0;

        $write = static function (array $entry) use (&$created, &$filled, &$skipped, $rows, $locales, $default, $fillDefault, $fillAll, $model, $dryRun): void {
            [$group, $key, $fileTexts] = $entry;
            $row = $rows[$group."\0".$key] ?? null;
            $text = $row === null ? [] : ($row->text ?? []);
            $changed = false;

            foreach ($locales as $locale) {
                // A locale the row HAS - even as null, which is how an editor's "cleared" is stored - is a decision, not a gap.
                if (array_key_exists($locale, $text)) {
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
                if ($dryRun) {
                    $created++;

                    return;
                }

                $row = new $model;
                $row->group = $group;
                $row->key = $key;
                $row->text = $text;
                $row->created_by_scan = true;

                // Nothing to translate (no letters: a phone number, a price) - confirmed right away, never "missing".
                if (Translation::statusEnabled() && Translation::hasNothingToTranslate($key)) {
                    $row->confirmed_at = now();
                }

                Writes::insert($row) ? $created++ : $skipped++;
            } elseif ($changed) {
                $filled++;

                if (!$dryRun) {
                    $row->text = $text;
                    $row->save();
                }
            }
        };

        if ($dryRun) {
            foreach ($found as $entry) {
                $write($entry);
            }
        } else {
            Writes::chunked($model, $found, $write);
        }

        $pruned = 0;
        $marked = 0;
        $restored = 0;
        $present = static fn (string $id): bool => isset($found[$id]) || isset($seen[$id]);

        // Rows to touch, then written in chunks (each in a transaction, one cache flush at the end).
        /** @var array<string, array{0: Translation, 1: string}> $changes row + what to do: restore / mark / delete */
        $changes = [];

        if (Translation::statusEnabled()) {
            // A key that is found again is no longer an orphan, with or without --prune.
            foreach ($rows as $id => $row) {
                if ($present($id) && $row->orphaned_at !== null) {
                    $restored++;
                    $changes[$id] = [$row, 'restore'];
                }
            }

            // Rows created before this rule (or by hand) whose key has nothing to translate get confirmed too.
            foreach ($rows as $id => $row) {
                if (!isset($changes[$id]) && $row->confirmed_at === null && Translation::hasNothingToTranslate((string) $row->key)) {
                    $changes[$id] = [$row, 'confirm'];
                }
            }
        }

        if ($prune && self::marksOrphans()) {
            // Non-destructive: every scanned row takes part (not only the ones the scanner created) and nothing is deleted.
            foreach ($rows as $id => $row) {
                if (!$present($id) && $row->orphaned_at === null) {
                    $marked++;
                    $changes[$id] = [$row, 'mark'];
                }
            }
        } elseif ($prune) {
            $onlyScanned = (bool) config('filament-translation-manager.prune.only_scanned', true);

            foreach ($rows as $id => $row) {
                if ($present($id) || ($onlyScanned && !$row->created_by_scan)) {
                    continue;
                }

                $pruned++;
                $changes[$id] = [$row, 'delete'];
            }
        }

        if (!$dryRun) {
            Writes::chunked($model, $changes, static function (array $change): void {
                [$row, $action] = $change;

                match ($action) {
                    'restore' => $row->forceFill(['orphaned_at' => null])->save(),
                    'mark' => $row->forceFill(['orphaned_at' => now()])->save(),
                    'confirm' => $row->forceFill(['confirmed_at' => now()])->save(),
                    default => $row->delete(),
                };
            });
        }

        return new SyncResult(count($found), $created, $filled, $pruned, $skipped, $marked, $restored);
    }

    private function id(string $group, string $key): string
    {
        return $group."\0".$key;
    }
}
