<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Feature;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Pages\QuickTranslate;
use Asignua\FilamentTranslationManager\Resources\TranslationResource;
use Asignua\FilamentTranslationManager\Resources\TranslationResource\Pages\EditTranslation;
use Asignua\FilamentTranslationManager\Resources\TranslationResource\Pages\ListTranslations;
use Asignua\FilamentTranslationManager\Support\Synchronizer;
use Asignua\FilamentTranslationManager\Support\TranslationTransfer;
use Asignua\FilamentTranslationManager\Tests\Concerns\MakesProject;
use Asignua\FilamentTranslationManager\Tests\TestCase;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

/**
 * Regressions found by an independent review of the plugin.
 */
class ReviewFixesTest extends TestCase
{
    use MakesProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeProject();
        $this->scanProject();
        $this->writeFile('lang/en/messages.php', '<?php return [];');
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

    private function row(string $group, string $key): ?Translation
    {
        return Translation::query()->where('group', $group)->where('key', $key)->first();
    }

    /**
     * MySQL/MariaDB with a *_ci collation treat `Email`/`email` as one unique key. SQLite is byte-exact, so the
     * collision is reproduced by making the unique index case-insensitive.
     */
    private function makeKeysCaseInsensitive(): void
    {
        Schema::table('language_lines', fn ($table) => $table->dropUnique(['group', 'key']));
        DB::statement('create unique index tm_ci on language_lines ("group", "key" collate nocase)');
    }

    // ---- 1. unique collisions ---------------------------------------------------------------------------------------------

    public function test_a_unique_collision_in_the_scan_is_skipped_and_the_rest_is_written(): void
    {
        $this->makeKeysCaseInsensitive();
        $this->make('*', 'email', ['en' => 'email']);
        $this->writeFile('app/A.php', "<?php __('Email'); __('Zeta');");

        $result = Synchronizer::make()->sync();

        $this->assertNotNull($this->row('*', 'Zeta'));
        $this->assertNull($this->row('*', 'Email'));
        $this->assertSame(1, $result->created);
        $this->assertSame(1, $result->skipped);
    }

    public function test_a_unique_collision_in_the_import_is_skipped_and_the_rest_is_written(): void
    {
        $this->makeKeysCaseInsensitive();
        $this->make('*', 'email', ['en' => 'email']);

        $result = (new TranslationTransfer)->import('uk', ['Email' => 'Пошта', 'Zeta' => 'Зета']);

        $this->assertSame('Зета', $this->row('*', 'Zeta')?->text['uk']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['skipped']);
    }

    public function test_the_stub_makes_the_key_columns_binary_on_mysql_and_mariadb(): void
    {
        $stub = (string) file_get_contents(__DIR__.'/../../database/migrations/create_language_lines_table.php.stub');

        $this->assertStringContainsString('utf8mb4_bin', $stub);
        $this->assertFileExists(__DIR__.'/../../database/migrations/make_language_lines_keys_binary.php.stub');
    }

    // ---- 2. language switcher on a real request ----------------------------------------------------------------------------

    public function test_a_full_page_load_gets_the_chosen_locale_even_when_the_session_was_not_started_early(): void
    {
        $store = app('session')->driver();
        $store->setId($id = Str::random(40));
        $store->put('translation-manager.locale', 'uk');
        $store->save();

        $name = config('session.cookie');
        $value = CookieValuePrefix::create($name, app(Encrypter::class)->getKey()).$id;

        $this->withCookie($name, app(Encrypter::class)->encrypt($value, false));

        $this->get(TranslationResource::getUrl())->assertOk()->assertSee('lang="uk"', false);
    }

    // ---- 3. key length ----------------------------------------------------------------------------------------------------

    public function test_the_key_limit_counts_characters_and_can_be_lifted(): void
    {
        $long = str_repeat('Ж', 200); // 200 characters, 400 bytes
        $this->writeFile('app/A.php', "<?php __('{$long}');");

        $this->assertSame(1, Synchronizer::make()->sync()->created);

        config(['filament-translation-manager.scan.max_key_length' => 100]);
        $this->assertSame(1, Synchronizer::make()->sync(dryRun: true)->skipped);

        config(['filament-translation-manager.scan.max_key_length' => null]);
        $this->assertSame(0, Synchronizer::make()->sync(dryRun: true)->skipped);
    }

    public function test_a_skipped_long_key_does_not_orphan_its_existing_row(): void
    {
        config(['filament-translation-manager.scan.max_key_length' => 100]);
        $long = str_repeat('Ж', 150);
        $this->make('*', $long, ['en' => $long]);
        $this->writeFile('app/A.php', "<?php __('{$long}');");

        $result = Synchronizer::make()->sync(prune: true);

        $this->assertSame(0, $result->marked);
        $this->assertNull($this->row('*', $long)->orphaned_at);
    }

    // ---- 4. groups scoping in transfer / quick translate ---------------------------------------------------------------

    public function test_import_and_export_respect_the_managed_groups(): void
    {
        config(['filament-translation-manager.groups' => ['*'], 'filament-translation-manager.scan.skip_group_like_keys' => true]);
        $this->make('messages', 'title', ['uk' => 'Заголовок']);
        $this->make('*', 'Hello', ['uk' => 'Привіт']);

        $this->assertSame(['Hello' => 'Привіт'], (new TranslationTransfer)->export('uk'));

        $result = (new TranslationTransfer)->import('uk', ['messages.title' => 'Інший', 'ee.Save' => 'Зберегти', 'Plain' => 'Звичайний']);

        $this->assertSame(1, $result['created']);
        $this->assertSame(2, $result['skipped']);
        $this->assertSame('Заголовок', $this->row('messages', 'title')->text['uk']);
        $this->assertNull($this->row('*', 'ee.Save'));
    }

    public function test_quick_translate_cannot_be_pointed_at_a_row_outside_the_groups(): void
    {
        config(['filament-translation-manager.groups' => ['*']]);
        $other = $this->make('messages', 'title', ['en' => 'Title']);
        $this->make('*', 'Front', ['en' => 'Front']);

        $page = new QuickTranslate;
        $page->recordId = $other->id;
        $this->assertNull($page->record());

        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(QuickTranslate::class)->set('recordId', $other->id);
    }

    // ---- 5. import limits --------------------------------------------------------------------------------------------------

    public function test_the_import_flushes_the_cache_once_per_group_not_per_row(): void
    {
        $flushes = 0;
        Cache::shouldReceive('forget')->andReturnUsing(function () use (&$flushes): bool {
            $flushes++;

            return true;
        });

        $map = [];

        for ($i = 0; $i < 20; $i++) {
            $map["Key {$i}"] = "Text {$i}";
        }

        (new TranslationTransfer)->import('uk', $map);

        // one flush per locale of the group (en, uk, de + app locales), not 20 x that
        $this->assertLessThanOrEqual(8, $flushes);
        $this->assertGreaterThan(0, $flushes);
    }

    public function test_the_import_upload_has_a_size_limit_from_the_config(): void
    {
        $this->assertSame(2048, (int) config('filament-translation-manager.import.max_size'));
    }

    // ---- 6. behaviour -------------------------------------------------------------------------------------------------------

    public function test_a_locale_an_editor_cleared_is_not_refilled_by_the_next_scan(): void
    {
        config(['filament-translation-manager.scan.fill_all_locales_with_key' => true]);
        $this->writeFile('app/A.php', "<?php __('Hello');");
        Synchronizer::make()->sync();
        $row = $this->row('*', 'Hello');
        $this->assertSame('Hello', $row->text['uk']);

        Livewire::test(EditTranslation::class, ['record' => $row->getKey()])
            ->fillForm(['text.uk' => ''])
            ->call('save');

        Synchronizer::make()->sync();

        $this->assertNull($row->refresh()->textFor('uk'));
        $this->assertArrayHasKey('uk', $row->text);
    }

    public function test_a_locale_added_later_is_still_backfilled_on_existing_rows(): void
    {
        config(['filament-translation-manager.scan.fill_all_locales_with_key' => true]);
        $this->make('*', 'Hello', ['en' => 'Hello', 'uk' => 'Привіт']);
        $this->writeFile('app/A.php', "<?php __('Hello');");

        Synchronizer::make()->sync();

        $this->assertSame('Hello', $this->row('*', 'Hello')->text['de']);
        $this->assertSame('Привіт', $this->row('*', 'Hello')->text['uk']);
    }

    public function test_a_cleared_locale_counts_as_missing(): void
    {
        $row = $this->make('*', 'Hello', ['en' => 'Hello', 'uk' => null]);

        $this->assertTrue($row->isMissingIn('uk'));
        $this->assertContains($row->id, Translation::query()->missingIn('uk')->pluck('id')->all());
    }

    public function test_the_record_title_of_a_json_row_is_just_the_key(): void
    {
        $front = $this->make('*', 'Some key', ['en' => 'x']);
        $group = $this->make('messages', 'title', ['en' => 'x']);

        $this->assertSame('Some key', TranslationResource::getRecordTitle($front));
        $this->assertSame('messages.title', TranslationResource::getRecordTitle($group));
    }

    public function test_search_looks_at_the_texts_not_at_the_json_keys(): void
    {
        $row = $this->make('*', 'Hello', ['en' => 'Hello there', 'uk' => 'Привіт']);
        DB::table('language_lines')->insert([
            'group' => '*', 'key' => 'Legacy', 'text' => '{"uk":"Привіт світ"}',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $legacy = $this->row('*', 'Legacy');

        Livewire::test(ListTranslations::class)
            ->searchTable('uk')
            ->assertCanNotSeeTableRecords([$row, $legacy])
            ->searchTable('світ')
            ->assertCanSeeTableRecords([$legacy])
            ->assertCanNotSeeTableRecords([$row]);
    }
}
