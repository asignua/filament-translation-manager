<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Feature;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Tests\Concerns\MakesProject;
use Asignua\FilamentTranslationManager\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class DatabaseOverrideTest extends TestCase
{
    use MakesProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeProject();
        $this->writeFile('lang/en/messages.php', "<?php return ['hello' => 'Hello from file'];");
        $this->writeFile('lang/en.json', '{"Add to basket": "Basket (file)"}');
        app('translator')->setLoaded([]);
    }

    protected function tearDown(): void
    {
        $this->destroyProject();

        parent::tearDown();
    }

    private function make(string $group, string $key, array $text): Translation
    {
        $row = new Translation;
        $row->group = $group;
        $row->key = $key;
        $row->text = $text;
        $row->save();

        return $row;
    }

    public function test_the_file_value_is_served_when_there_is_no_row(): void
    {
        $this->assertSame('Hello from file', __('messages.hello'));
        $this->assertSame('Basket (file)', __('Add to basket'));
    }

    public function test_a_database_row_overrides_the_file(): void
    {
        $this->make('messages', 'hello', ['en' => 'Hello from the database']);
        $this->make('*', 'Add to basket', ['en' => 'Basket (db)']);

        $this->assertSame('Hello from the database', __('messages.hello'));
        $this->assertSame('Basket (db)', __('Add to basket'));
    }

    public function test_the_cache_is_flushed_when_a_row_is_edited(): void
    {
        $row = $this->make('messages', 'hello', ['en' => 'First']);
        $this->assertSame('First', __('messages.hello'));

        // The translator memoises per request; the persistent cache is what survives it.
        app('translator')->setLoaded([]);
        $row->text = ['en' => 'Second'];
        $row->save();
        app('translator')->setLoaded([]);

        $this->assertSame('Second', __('messages.hello'));
    }

    public function test_the_cache_is_flushed_when_a_locale_is_cleared(): void
    {
        $row = $this->make('messages', 'hello', ['en' => 'From db', 'uk' => 'З бази']);
        $this->assertSame('З бази', __('messages.hello', [], 'uk'));

        app('translator')->setLoaded([]);
        // Spatie alone would only flush the locales left in the new text; `uk` would keep serving the stale string.
        $row->text = ['en' => 'From db'];
        $row->save();
        app('translator')->setLoaded([]);

        // `uk` is gone from the row: the file has no uk either, so the key is returned.
        $this->assertSame('From db', __('messages.hello', [], 'uk'));
    }

    public function test_the_cache_is_flushed_when_a_row_is_deleted(): void
    {
        $row = $this->make('messages', 'hello', ['en' => 'Deleted soon']);
        $this->assertSame('Deleted soon', __('messages.hello'));

        app('translator')->setLoaded([]);
        $row->delete();
        app('translator')->setLoaded([]);

        $this->assertSame('Hello from file', __('messages.hello'));
    }

    public function test_cyrillic_is_stored_readable_in_the_database(): void
    {
        $this->make('*', 'Додати', ['uk' => 'Додати в кошик', 'en' => 'Add']);

        $raw = (string) DB::table('language_lines')->where('key', 'Додати')->value('text');

        $this->assertStringContainsString('Додати в кошик', $raw);
        $this->assertStringNotContainsString('\\u04', $raw);
        $this->assertSame('Додати в кошик', Translation::query()->first()->text['uk']);
    }

    public function test_legacy_escaped_rows_are_still_read(): void
    {
        DB::table('language_lines')->insert([
            'group' => '*',
            'key' => 'Old',
            'text' => '{"uk":"Старий"}',
        ]);

        $this->assertSame('Старий', Translation::query()->first()->text['uk']);
    }

    public function test_the_text_of_one_locale_does_not_fall_back(): void
    {
        $row = $this->make('*', 'Only english', ['en' => 'English only']);

        $this->assertNull($row->textFor('de'));
        $this->assertTrue($row->isMissingIn('de'));
        $this->assertFalse($row->isMissingIn('en'));
    }
}
