<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

final readonly class SyncResult
{
    /**
     * @param int $found    distinct (group, key) pairs seen in code and lang files
     * @param int $created  rows created
     * @param int $filled   existing rows that got a text for a locale they did not have (never an overwrite)
     * @param int $pruned   rows deleted by --prune
     * @param int $skipped  literals that cannot be a row (namespaced `pkg::x`, longer than `scan.max_key_length` characters, a key the database refuses as a duplicate)
     * @param int $marked   rows newly marked as no longer found (`prune.mode` = mark)
     * @param int $restored rows whose "no longer found" mark was cleared because the key is found again
     */
    public function __construct(
        public int $found = 0,
        public int $created = 0,
        public int $filled = 0,
        public int $pruned = 0,
        public int $skipped = 0,
        public int $marked = 0,
        public int $restored = 0,
    ) {}

    /**
     * The report line of the command and of the panel notification.
     */
    public function summary(): string
    {
        $line = (string) __('filament-translation-manager::filament-translation-manager.scan.summary', [
            'found' => $this->found,
            'created' => $this->created,
            'filled' => $this->filled,
            'pruned' => $this->pruned,
            'skipped' => $this->skipped,
        ]);

        if ($this->marked > 0 || $this->restored > 0) {
            $line .= ' '.__('filament-translation-manager::filament-translation-manager.scan.summary_marks', [
                'marked' => $this->marked,
                'restored' => $this->restored,
            ]);
        }

        return $line;
    }
}
