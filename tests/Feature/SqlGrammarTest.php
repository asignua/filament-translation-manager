<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Feature;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The plugin is developed on SQLite and runs on MySQL/MariaDB. A query builder never connects to compile SQL, so the
 * SQL of the "missing" scopes can be checked per driver here: the JSON path must be compiled by the grammar
 * (`text->uk`), not hand-written with a function one driver does not have.
 */
class SqlGrammarTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function drivers(): array
    {
        return ['mysql' => ['mysql'], 'mariadb' => ['mariadb'], 'pgsql' => ['pgsql'], 'sqlite' => ['sqlite']];
    }

    private function sql(string $driver, callable $build): string
    {
        config(['database.connections.dry_'.$driver => match ($driver) {
            'sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            default => ['driver' => $driver, 'host' => '127.0.0.1', 'database' => 'x', 'username' => 'x', 'password' => '', 'prefix' => ''],
        }]);

        $model = (new Translation)->setConnection('dry_'.$driver);

        return $build($model->newQuery())->toSql();
    }

    #[DataProvider('drivers')]
    public function test_the_missing_scope_compiles_json_paths_per_driver(string $driver): void
    {
        $sql = $this->sql($driver, static fn ($query) => $query->missingIn('uk'));

        $this->assertStringNotContainsStringIgnoringCase('JSON_EXTRACT(`text`, ?)', $sql, 'a hand-written JSON function');

        $expected = match ($driver) {
            'mysql' => ["json_extract(`text`, '$.\"uk\"') is null", "json_unquote(json_extract(`text`, '$.\"uk\"')) = ?", "json_unquote(json_extract(`text`, '$.\"uk\"')) = `key`"],
            'mariadb' => ["json_extract(`text`, '$.\"uk\"') is null", "json_value(`text`, '$.\"uk\"') = ?", "json_value(`text`, '$.\"uk\"') = `key`"],
            'pgsql' => ['"text"->>\'uk\' is null', '"text"->>\'uk\' = ?', '"text"->>\'uk\' = "key"'],
            'sqlite' => ["json_extract(\"text\", '$.\"uk\"') is null", "json_extract(\"text\", '$.\"uk\"') = ?", "json_extract(\"text\", '$.\"uk\"') = \"key\""],
        };

        foreach ($expected as $fragment) {
            $this->assertStringContainsString($fragment, $sql);
        }

        // The `key` column is quoted by the grammar (it is a reserved word in MySQL).
        $this->assertStringContainsString('confirmed_at', $sql);
    }

    #[DataProvider('drivers')]
    public function test_a_locale_with_odd_characters_stays_inside_the_json_path(string $driver): void
    {
        $sql = $this->sql($driver, static fn ($query) => $query->missingIn('pt_BR'));

        $this->assertStringContainsString('pt_BR', $sql);
        $this->assertStringNotContainsString("pt_BR'; ", $sql);
    }
}
