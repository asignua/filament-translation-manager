<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Feature;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Resources\TranslationResource;
use Asignua\FilamentTranslationManager\Resources\TranslationResource\Pages\ListTranslations;
use Asignua\FilamentTranslationManager\Tests\TestCase;
use Livewire\Livewire;

class StatusTest extends TestCase
{
    private function make(string $group, string $key, array $text, array $status = []): Translation
    {
        $row = new Translation;
        $row->group = $group;
        $row->key = $key;
        $row->text = $text;

        foreach ($status as $column => $value) {
            $row->{$column} = $value;
        }

        $row->save();

        return $row;
    }

    public function test_a_text_equal_to_the_key_is_missing(): void
    {
        $placeholder = $this->make('*', 'Hello', ['en' => 'Hello', 'uk' => 'Hello', 'de' => 'Hallo']);
        $done = $this->make('*', 'Bye', ['en' => 'Goodbye', 'uk' => 'Бувай', 'de' => 'Tschüss']);

        $this->assertTrue($placeholder->isMissingIn('uk'));
        $this->assertTrue($placeholder->isMissing());
        $this->assertFalse($done->isMissing());

        $this->assertSame([$placeholder->id], Translation::query()->missingIn('uk')->pluck('id')->all());
        $this->assertSame([$placeholder->id], Translation::query()->missingInAny()->pluck('id')->all());
        // In the language the keys are written in (app.fallback_locale = en here) the key IS the text.
        $this->assertFalse($placeholder->isMissingIn('en'));
        $this->assertSame([], Translation::query()->missingIn('en')->pluck('id')->all());
    }

    public function test_the_source_locale_is_configurable(): void
    {
        config(['filament-translation-manager.missing.source_locale' => 'uk']);
        $row = $this->make('*', 'Додати', ['en' => 'Додати', 'uk' => 'Додати', 'de' => 'Hinzufügen']);

        $this->assertFalse($row->isMissingIn('uk'));
        $this->assertTrue($row->isMissingIn('en'));
        $this->assertSame([$row->id], Translation::query()->missingIn('en')->pluck('id')->all());
        $this->assertSame([], Translation::query()->missingIn('uk')->pluck('id')->all());
    }

    public function test_a_key_without_letters_has_nothing_to_translate(): void
    {
        $this->assertTrue(Translation::hasNothingToTranslate('+38 067 660 1330'));
        $this->assertTrue(Translation::hasNothingToTranslate('→ 100%'));
        $this->assertFalse(Translation::hasNothingToTranslate('+ Додати ще'));
        $this->assertFalse(Translation::hasNothingToTranslate('Collection'));
    }

    public function test_the_key_comparison_can_be_turned_off(): void
    {
        config(['filament-translation-manager.missing.value_equal_to_key' => false]);
        $row = $this->make('*', 'Hello', ['en' => 'Hello', 'uk' => 'Hello', 'de' => 'Hallo']);

        $this->assertFalse($row->isMissing());
        $this->assertSame([], Translation::query()->missingInAny()->pluck('id')->all());
    }

    public function test_a_confirmed_row_is_never_missing(): void
    {
        $confirmed = $this->make('*', 'Hello', ['en' => 'Hello'], ['confirmed_at' => now()]);
        $open = $this->make('*', 'World', ['en' => 'World']);

        $this->assertFalse($confirmed->isMissing());
        $this->assertSame([$open->id], Translation::query()->missingInAny()->pluck('id')->all());
        $this->assertSame([$open->id], Translation::query()->missingIn('de')->pluck('id')->all());
        $this->assertSame([$confirmed->id], Translation::query()->confirmed()->pluck('id')->all());
    }

    public function test_without_status_columns_a_confirmed_value_does_not_count(): void
    {
        config(['filament-translation-manager.status.enabled' => false]);
        $row = $this->make('*', 'Hello', ['en' => 'Hello'], ['confirmed_at' => now()]);

        $this->assertTrue($row->isMissingIn('de'));
        $this->assertFalse($row->isConfirmed());
    }

    public function test_the_status_is_orphaned_then_missing_then_ok(): void
    {
        $orphan = $this->make('*', 'A', ['en' => 'A'], ['orphaned_at' => now()]);
        $missing = $this->make('*', 'B', ['en' => 'B']);
        $ok = $this->make('*', 'C', ['en' => 'Ц', 'uk' => 'Ц', 'de' => 'Z']);

        $this->assertSame(Translation::STATUS_ORPHANED, $orphan->status());
        $this->assertSame(Translation::STATUS_MISSING, $missing->status());
        $this->assertSame(Translation::STATUS_OK, $ok->status());
        $this->assertSame([$orphan->id], Translation::query()->orphaned()->pluck('id')->all());
    }

    public function test_the_list_has_the_status_column_and_the_status_filters(): void
    {
        $orphan = $this->make('*', 'A', ['en' => 'A'], ['orphaned_at' => now()]);
        $confirmed = $this->make('*', 'B', ['en' => 'B'], ['confirmed_at' => now()]);
        $plain = $this->make('*', 'C', ['en' => 'C']);

        Livewire::test(ListTranslations::class)
            ->assertTableColumnExists('status')
            ->assertTableColumnStateSet('status', 'orphaned', $orphan)
            ->assertTableColumnStateSet('status', 'ok', $confirmed)
            ->assertTableColumnStateSet('status', 'missing', $plain)
            ->filterTable('orphaned', true)
            ->assertCanSeeTableRecords([$orphan])
            ->assertCanNotSeeTableRecords([$confirmed, $plain])
            ->resetTableFilters()
            ->filterTable('confirmed', true)
            ->assertCanSeeTableRecords([$confirmed])
            ->assertCanNotSeeTableRecords([$orphan, $plain])
            ->resetTableFilters()
            ->filterTable('missing', true)
            ->assertCanSeeTableRecords([$orphan, $plain])
            ->assertCanNotSeeTableRecords([$confirmed]);
    }

    public function test_confirm_and_unconfirm_toggle_the_row_and_flush_the_cache(): void
    {
        $row = $this->make('*', 'Hello', ['en' => 'Hello']);

        Livewire::test(ListTranslations::class)
            ->callTableAction('confirm', $row)
            ->assertHasNoTableActionErrors();

        $this->assertNotNull($row->refresh()->confirmed_at);
        $this->assertFalse($row->isMissing());

        Livewire::test(ListTranslations::class)->callTableAction('confirm', $row);

        $this->assertNull($row->refresh()->confirmed_at);
    }

    public function test_the_status_ui_is_hidden_when_the_columns_are_off(): void
    {
        config(['filament-translation-manager.status.enabled' => false]);

        $test = Livewire::test(ListTranslations::class)->assertTableColumnDoesNotExist('status');

        $filters = $test->instance()->getTable()->getFilters();
        $this->assertArrayNotHasKey('orphaned', $filters);
        $this->assertArrayNotHasKey('confirmed', $filters);
        $this->assertArrayHasKey('missing', $filters);
    }

    public function test_the_missing_count_ignores_confirmed_rows(): void
    {
        $this->make('*', 'Hello', ['en' => 'Hello'], ['confirmed_at' => now()]);
        $this->make('*', 'World', ['en' => 'World']);

        $this->assertSame(1, TranslationResource::missingCount());
    }
}
