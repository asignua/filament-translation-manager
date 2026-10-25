<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Support;

use Asignua\FilamentTranslationManager\Models\Translation;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * How the scan and the import write: in chunks, each in its own transaction (a failure rolls back one chunk, not
 * the whole run, and a long scan does not hold one huge transaction), with spatie's cache flushed once at the end.
 * A key that collides with an existing one in the database (a `*_ci` collation that the PHP side did not see) is
 * skipped instead of aborting the run.
 */
final class Writes
{
    public const int CHUNK = 200;

    /**
     * The longest key a row may have, in CHARACTERS (config `scan.max_key_length`); null = unlimited (a TEXT column).
     */
    public static function maxKeyLength(): ?int
    {
        $limit = config('filament-translation-manager.scan.max_key_length', 255);

        return is_numeric($limit) && (int) $limit > 0 ? (int) $limit : null;
    }

    public static function keyTooLong(string $key): bool
    {
        $limit = self::maxKeyLength();

        return $limit !== null && mb_strlen($key) > $limit;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param class-string<Translation>   $model
     * @param array<TKey, TValue>         $items
     * @param Closure(TValue, TKey): void $each
     */
    public static function chunked(string $model, array $items, Closure $each): void
    {
        $connection = (new $model)->getConnection();

        $model::deferringCacheFlush(function () use ($items, $each, $connection): void {
            foreach (array_chunk($items, self::CHUNK, true) as $chunk) {
                $connection->transaction(function () use ($chunk, $each): void {
                    foreach ($chunk as $key => $item) {
                        $each($item, $key);
                    }
                });
            }
        });
    }

    /**
     * Saves a NEW row; false when the database refuses it as a duplicate.
     */
    public static function insert(Translation $row): bool
    {
        try {
            // A nested transaction is a savepoint: a refused insert does not poison the chunk (PostgreSQL).
            $row->getConnection()->transaction(static fn (): bool => $row->save());

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
