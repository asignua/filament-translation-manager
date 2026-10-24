<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Feature;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Support\Synchronizer;
use Asignua\FilamentTranslationManager\Tests\Concerns\MakesProject;
use Asignua\FilamentTranslationManager\Tests\TestCase;

class SynchronizerTest extends TestCase
{
    use MakesProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeProject();
        $this->scanProject();

        $this->writeFile('views/shop.blade.php', "{{ __('Add to basket') }} @lang(\"It's here\") {{ trans('messages.title') }}");
        $this->writeFile('app/Thing.php', "<?php __('From code'); __('pkg::group.key'); __('a' . \$b);");
        $this->writeFile('app/tests/Hidden.php', "<?php __('Only in a tests folder');");
        $this->writeFile('lang/en/messages.php', "<?php return ['title' => 'Title', 'nested' => ['deep' => 'Deep']];");
        $this->writeFile('lang/uk/messages.php', "<?php return ['title' => 'Заголовок'];");
        $this->writeFile('lang/uk.json', '{"Add to basket": "Додати в кошик"}');
    }

    protected function tearDown(): void
    {
        $this->destroyProject();

        parent::tearDown();
    }

    private function row(string $group, string $key): ?Translation
    {
        return Translation::query()->where('group', $group)->where('key', $key)->first();
    }

    public function test_a_scan_creates_rows_from_code_and_lang_files(): void
    {
        $result = Synchronizer::make()->sync();

        $this->assertNotNull($this->row('*', 'Add to basket'));
        $this->assertNotNull($this->row('*', "It's here"));
        $this->assertNotNull($this->row('*', 'From code'));
        $this->assertNotNull($this->row('messages', 'title'));
        $this->assertNotNull($this->row('messages', 'nested.deep'));
        $this->assertSame(5, $result->created);
        // The namespaced literal; the dynamic `__('a' . $b)` is not even a literal.
        $this->assertSame(1, $result->skipped);
        $this->assertTrue($this->row('*', 'From code')->created_by_scan);
    }

    public function test_tests_folders_are_not_scanned(): void
    {
        Synchronizer::make()->sync();

        $this->assertNull($this->row('*', 'Only in a tests folder'));
    }

    public function test_file_texts_are_imported_per_locale_and_the_default_locale_gets_the_key(): void
    {
        Synchronizer::make()->sync();

        $this->assertSame(['en' => 'Add to basket', 'uk' => 'Додати в кошик'], $this->row('*', 'Add to basket')->text);
        $this->assertSame(['en' => 'Title', 'uk' => 'Заголовок'], $this->row('messages', 'title')->text);
        // No German anywhere: stays missing, which is what the "missing" filter is for.
        $this->assertTrue($this->row('messages', 'title')->isMissingIn('de'));
        // A group key is not filled with its own name.
        $this->assertSame(['en' => 'Deep'], $this->row('messages', 'nested.deep')->text);
        $this->assertSame(['en' => 'From code'], $this->row('*', 'From code')->text);
    }

    public function test_the_key_fill_can_be_turned_off(): void
    {
        config(['filament-translation-manager.scan.fill_default_locale_with_key' => false]);

        Synchronizer::make()->sync();

        $this->assertSame([], $this->row('*', 'From code')->text);
    }

    public function test_a_second_scan_changes_nothing(): void
    {
        Synchronizer::make()->sync();
        $result = Synchronizer::make()->sync();

        $this->assertSame(0, $result->created);
        $this->assertSame(0, $result->filled);
        $this->assertSame(5, Translation::query()->count());
    }

    public function test_an_edited_text_is_never_overwritten_but_gaps_are_filled(): void
    {
        Synchronizer::make()->sync();

        $row = $this->row('messages', 'title');
        $row->text = ['en' => 'My own title'];
        $row->save();

        $this->writeFile('lang/de/messages.php', "<?php return ['title' => 'Titel'];");
        config(['filament-translation-manager.locales' => ['en', 'uk', 'de']]);
        \Asignua\FilamentTranslationManager\Support\Locales::override(null);

        $result = Synchronizer::make()->sync();

        $this->assertSame(
            ['en' => 'My own title', 'uk' => 'Заголовок', 'de' => 'Titel'],
            $this->row('messages', 'title')->fresh()->text,
        );
        $this->assertGreaterThanOrEqual(1, $result->filled);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $result = Synchronizer::make()->sync(dryRun: true);

        $this->assertSame(5, $result->created);
        $this->assertSame(0, Translation::query()->count());
    }

    public function test_prune_deletes_only_scanner_created_rows_that_are_gone(): void
    {
        config(['filament-translation-manager.prune.mode' => 'delete']);

        Synchronizer::make()->sync();

        $manual = new Translation;
        $manual->group = '*';
        $manual->key = 'Typed by hand';
        $manual->text = ['en' => 'Typed by hand'];
        $manual->save();

        unlink($this->project.'/app/Thing.php');

        $result = Synchronizer::make()->sync(prune: true);

        $this->assertSame(1, $result->pruned);
        $this->assertNull($this->row('*', 'From code'));
        $this->assertNotNull($this->row('*', 'Typed by hand'));
        $this->assertNotNull($this->row('*', 'Add to basket'));
    }

    public function test_prune_without_the_scanned_only_guard_takes_manual_rows_too(): void
    {
        config(['filament-translation-manager.prune.mode' => 'delete', 'filament-translation-manager.prune.only_scanned' => false]);

        $manual = new Translation;
        $manual->group = '*';
        $manual->key = 'Typed by hand';
        $manual->text = ['en' => 'x'];
        $manual->save();

        $result = Synchronizer::make()->sync(prune: true);

        $this->assertSame(1, $result->pruned);
        $this->assertNull($this->row('*', 'Typed by hand'));
    }

    public function test_a_key_still_present_in_a_lang_file_survives_prune(): void
    {
        config(['filament-translation-manager.prune.mode' => 'delete']);

        Synchronizer::make()->sync();

        unlink($this->project.'/views/shop.blade.php');

        Synchronizer::make()->sync(prune: true);

        // Gone from the code, still in lang/uk.json.
        $this->assertNotNull($this->row('*', 'Add to basket'));
        $this->assertNull($this->row('*', "It's here"));
    }

    public function test_keys_longer_than_the_column_are_skipped(): void
    {
        $this->writeFile('app/Long.php', "<?php __('".str_repeat('x', 300)."');");

        $result = Synchronizer::make()->sync();

        $this->assertSame(2, $result->skipped);
        $this->assertSame(5, $result->created);
    }

    public function test_the_scan_command_reports_and_supports_dry_run(): void
    {
        config(['filament-translation-manager.prune.mode' => 'delete']);

        $this->artisan('translation-manager:scan', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, Translation::query()->count());

        $this->artisan('translation-manager:scan')->assertSuccessful();
        $this->assertSame(5, Translation::query()->count());

        unlink($this->project.'/app/Thing.php');
        $this->artisan('translation-manager:scan', ['--prune' => true])->assertSuccessful();
        $this->assertNull($this->row('*', 'From code'));
    }

    public function test_prune_marks_rows_that_are_gone_by_default_and_deletes_nothing(): void
    {
        Synchronizer::make()->sync();

        $manual = new Translation;
        $manual->group = '*';
        $manual->key = 'Typed by hand';
        $manual->text = ['en' => 'Typed by hand'];
        $manual->save();

        unlink($this->project.'/app/Thing.php');

        $result = Synchronizer::make()->sync(prune: true);

        $this->assertSame(0, $result->pruned);
        // Marking is not limited to rows the scanner created.
        $this->assertSame(2, $result->marked);
        $this->assertTrue($this->row('*', 'From code')->isOrphaned());
        $this->assertTrue($this->row('*', 'Typed by hand')->isOrphaned());
        $this->assertFalse($this->row('*', 'Add to basket')->isOrphaned());
        $this->assertStringContainsString('2', $result->summary());
    }

    public function test_a_marked_row_is_restored_when_the_key_comes_back_even_without_prune(): void
    {
        Synchronizer::make()->sync();
        $this->writeFile('app/Thing.php', "<?php __('pkg::group.key');");
        Synchronizer::make()->sync(prune: true);
        $this->assertTrue($this->row('*', 'From code')->isOrphaned());

        $this->writeFile('app/Thing.php', "<?php __('From code');");
        $result = Synchronizer::make()->sync();

        $this->assertSame(1, $result->restored);
        $this->assertFalse($this->row('*', 'From code')->isOrphaned());
        $this->assertNull($this->row('*', 'From code')->orphaned_at);
    }

    public function test_marking_is_skipped_in_a_dry_run(): void
    {
        Synchronizer::make()->sync();
        unlink($this->project.'/app/Thing.php');

        $result = Synchronizer::make()->sync(prune: true, dryRun: true);

        $this->assertSame(1, $result->marked);
        $this->assertFalse($this->row('*', 'From code')->isOrphaned());
    }

    public function test_without_status_columns_the_mark_mode_falls_back_to_delete(): void
    {
        config(['filament-translation-manager.status.enabled' => false]);
        Synchronizer::make()->sync();
        unlink($this->project.'/app/Thing.php');

        $result = Synchronizer::make()->sync(prune: true);

        $this->assertSame(1, $result->pruned);
        $this->assertNull($this->row('*', 'From code'));
    }

    public function test_the_scan_is_limited_to_the_configured_groups(): void
    {
        config(['filament-translation-manager.groups' => ['*']]);

        $result = Synchronizer::make()->sync();

        $this->assertNull($this->row('messages', 'title'));
        $this->assertNotNull($this->row('*', 'From code'));
        $this->assertSame(3, $result->created);
    }

    public function test_every_locale_can_get_the_key_as_a_placeholder(): void
    {
        config(['filament-translation-manager.scan.fill_all_locales_with_key' => true]);

        Synchronizer::make()->sync();

        $this->assertSame(['en' => 'From code', 'uk' => 'From code', 'de' => 'From code'], $this->row('*', 'From code')->text);
        // A real file text still wins over the placeholder.
        $this->assertSame('Додати в кошик', $this->row('*', 'Add to basket')->text['uk']);
        // A group key is not a sentence: no placeholder.
        $this->assertSame(['en' => 'Deep'], $this->row('messages', 'nested.deep')->text);
    }

    public function test_group_like_keys_can_be_skipped(): void
    {
        config(['filament-translation-manager.scan.skip_group_like_keys' => true]);
        $this->writeFile('app/Ee.php', "<?php __('ee.Save'); __('ee.Publish in this language'); __('e.g. this');");

        Synchronizer::make()->sync();

        $this->assertNull($this->row('*', 'ee.Save'));
        $this->assertNull($this->row('*', 'ee.Publish in this language'));
        $this->assertNotNull($this->row('*', 'e.g. this'));
    }
}
