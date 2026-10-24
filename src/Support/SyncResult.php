<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

final readonly class SyncResult
{
    /**
     * @param int $found   distinct (group, key) pairs seen in code and lang files
     * @param int $created rows created
     * @param int $filled  existing rows that got a text for a locale they did not have (never an overwrite)
     * @param int $pruned  rows deleted by --prune
     * @param int $skipped literals that cannot be a row (namespaced `pkg::x`, longer than 255 characters)
     */
    public function __construct(
        public int $found = 0,
        public int $created = 0,
        public int $filled = 0,
        public int $pruned = 0,
        public int $skipped = 0,
    ) {}
}
