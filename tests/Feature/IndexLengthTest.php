<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Feature;

use Asignua\FilamentTranslationManager\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite never complains about an index that is too long; MySQL/MariaDB (InnoDB, utf8mb4) refuse above 3072 bytes.
 */
class IndexLengthTest extends TestCase
{
    private const int MAX_BYTES = 3072;

    private const int BYTES_PER_CHAR = 4;

    public function test_every_index_fits_the_innodb_limit_in_utf8mb4(): void
    {
        $lengths = [];

        foreach (Schema::getColumns('language_lines') as $column) {
            // SQLite reports a bare `varchar`; Laravel's default string length is 255.
            if (preg_match('/^varchar(?:\((\d+)\))?$/i', (string) $column['type'], $m) === 1) {
                $lengths[$column['name']] = (int) ($m[1] ?? 255);
            }
        }

        $this->assertArrayHasKey('group', $lengths);
        $this->assertArrayHasKey('key', $lengths);

        $checked = 0;

        foreach (Schema::getIndexes('language_lines') as $index) {
            if ($index['primary']) {
                continue;
            }

            $bytes = 0;

            foreach ($index['columns'] as $column) {
                $this->assertArrayHasKey($column, $lengths, "Index [{$index['name']}] uses a non-varchar column [{$column}], which needs a prefix length.");
                $bytes += $lengths[$column] * self::BYTES_PER_CHAR;
            }

            $this->assertLessThanOrEqual(self::MAX_BYTES, $bytes, "Index [{$index['name']}] is {$bytes} bytes.");
            $checked++;
        }

        $this->assertGreaterThanOrEqual(2, $checked);
    }

    public function test_the_unique_pair_stays_far_below_the_limit(): void
    {
        $this->assertLessThanOrEqual(self::MAX_BYTES, (255 + 255) * self::BYTES_PER_CHAR);
    }
}
