<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Feature;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Resources\TranslationResource;
use Asignua\FilamentTranslationManager\Resources\TranslationResource\Pages\CreateTranslation;
use Asignua\FilamentTranslationManager\Resources\TranslationResource\Pages\EditTranslation;
use Asignua\FilamentTranslationManager\Resources\TranslationResource\Pages\ListTranslations;
use Asignua\FilamentTranslationManager\Support\TranslationTransfer;
use Asignua\FilamentTranslationManager\Tests\TestCase;
use Asignua\FilamentTranslationManager\TranslationManagerPlugin;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class ResourceTest extends TestCase
{
    private function make(string $group, string $key, array $text): Translation
    {
        $row = new Translation;
        $row->group = $group;
        $row->key = $key;
        $row->text = $text;
        $row->save();

        return $row;
    }

    private function plugin(): TranslationManagerPlugin
    {
        /** @var TranslationManagerPlugin */
        return Filament::getPanel('admin')->getPlugin(TranslationManagerPlugin::ID);
    }

    public function test_the_list_shows_a_column_per_configured_locale(): void
    {
        $row = $this->make('*', 'Hello', ['en' => 'Hello', 'uk' => 'Привіт']);

        Livewire::test(ListTranslations::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$row])
            ->assertTableColumnExists('text.en')
            ->assertTableColumnExists('text.uk')
            ->assertTableColumnExists('text.de')
            ->assertTableColumnStateSet('text.uk', 'Привіт', $row);
    }

    public function test_search_looks_in_the_key_and_in_every_text(): void
    {
        $byKey = $this->make('*', 'Checkout button', ['en' => 'Pay']);
        $byText = $this->make('*', 'Other', ['en' => 'x', 'uk' => 'Оформлення замовлення']);
        $none = $this->make('*', 'Unrelated', ['en' => 'nothing']);

        Livewire::test(ListTranslations::class)
            ->searchTable('Checkout')
            ->assertCanSeeTableRecords([$byKey])
            ->assertCanNotSeeTableRecords([$byText, $none])
            ->searchTable('замовлення')
            ->assertCanSeeTableRecords([$byText])
            ->assertCanNotSeeTableRecords([$byKey, $none]);
    }

    public function test_search_treats_wildcards_literally(): void
    {
        $percent = $this->make('*', '100% sure', ['en' => 'a']);
        $other = $this->make('*', 'Plain', ['en' => 'b']);

        Livewire::test(ListTranslations::class)
            ->searchTable('%')
            ->assertCanSeeTableRecords([$percent])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_the_missing_in_filter_lists_rows_without_a_text_in_that_locale(): void
    {
        $complete = $this->make('*', 'Complete', ['en' => 'a', 'uk' => 'б', 'de' => 'c']);
        $noGerman = $this->make('*', 'No German', ['en' => 'a', 'uk' => 'б']);
        $emptyGerman = $this->make('*', 'Empty German', ['en' => 'a', 'uk' => 'б', 'de' => '']);
        $noUkrainian = $this->make('*', 'No Ukrainian', ['en' => 'a', 'de' => 'c']);

        Livewire::test(ListTranslations::class)
            ->filterTable('missing_in', ['de'])
            ->assertCanSeeTableRecords([$noGerman, $emptyGerman])
            ->assertCanNotSeeTableRecords([$complete, $noUkrainian])
            ->resetTableFilters()
            ->filterTable('missing_in', ['uk', 'de'])
            ->assertCanSeeTableRecords([$noGerman, $emptyGerman, $noUkrainian])
            ->assertCanNotSeeTableRecords([$complete]);
    }

    public function test_the_missing_in_any_locale_filter(): void
    {
        $complete = $this->make('*', 'Complete', ['en' => 'a', 'uk' => 'б', 'de' => 'c']);
        $partial = $this->make('*', 'Partial', ['en' => 'a']);

        Livewire::test(ListTranslations::class)
            ->filterTable('missing', true)
            ->assertCanSeeTableRecords([$partial])
            ->assertCanNotSeeTableRecords([$complete]);
    }

    public function test_the_group_filter(): void
    {
        $json = $this->make('*', 'Hello', ['en' => 'a']);
        $auth = $this->make('auth', 'failed', ['en' => 'b']);

        Livewire::test(ListTranslations::class)
            ->filterTable('group', 'auth')
            ->assertCanSeeTableRecords([$auth])
            ->assertCanNotSeeTableRecords([$json]);
    }

    public function test_editing_saves_texts_and_flushes_the_served_translation(): void
    {
        $row = $this->make('*', 'Hello', ['en' => 'Hello', 'uk' => 'Привіт']);
        $this->assertSame('Привіт', __('Hello', [], 'uk'));

        Livewire::test(EditTranslation::class, ['record' => $row->getKey()])
            ->assertFormSet(['text.uk' => 'Привіт'])
            ->fillForm(['text.uk' => 'Вітаю', 'text.de' => 'Hallo', 'text.en' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $row->refresh();
        // A cleared field is null (it is "missing", not an empty string, and a scan will not refill it).
        $this->assertSame(['en' => null, 'uk' => 'Вітаю', 'de' => 'Hallo'], $row->text);
        // A new request starts with an empty in-memory translator; only the persistent cache could still be stale.
        app('translator')->setLoaded([]);
        $this->assertSame('Вітаю', __('Hello', [], 'uk'));
        $this->assertStringContainsString('Вітаю', (string) DB::table('language_lines')->value('text'));
    }

    public function test_editing_keeps_texts_of_locales_that_are_not_configured(): void
    {
        $row = $this->make('*', 'Hello', ['en' => 'Hello', 'fr' => 'Bonjour']);

        Livewire::test(EditTranslation::class, ['record' => $row->getKey()])
            ->fillForm(['text.en' => 'Hi'])
            ->call('save');

        $this->assertSame(['en' => 'Hi', 'fr' => 'Bonjour'], $row->refresh()->text);
    }

    public function test_the_group_and_key_cannot_be_edited(): void
    {
        $row = $this->make('*', 'Hello', ['en' => 'Hello']);

        Livewire::test(EditTranslation::class, ['record' => $row->getKey()])
            ->assertFormFieldIsDisabled('key')
            ->assertFormFieldIsDisabled('group');
    }

    public function test_the_edit_form_hints_the_lang_file_value(): void
    {
        $dir = sys_get_temp_dir().'/tm-hint-'.bin2hex(random_bytes(4));
        mkdir($dir.'/en', 0777, true);
        file_put_contents($dir.'/en/messages.php', "<?php return ['hello' => 'From the file'];");
        app()->useLangPath($dir);

        $row = $this->make('messages', 'hello', ['de' => 'x']);

        Livewire::test(EditTranslation::class, ['record' => $row->getKey()])
            ->assertSee(__('filament-translation-manager::filament-translation-manager.form.file_default', ['text' => 'From the file']));

        unlink($dir.'/en/messages.php');
        rmdir($dir.'/en');
        rmdir($dir);
    }

    public function test_creating_a_row(): void
    {
        Livewire::test(CreateTranslation::class)
            ->fillForm(['group' => '*', 'key' => 'New key', 'text.en' => 'New', 'text.uk' => 'Новий'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(['en' => 'New', 'uk' => 'Новий'], Translation::query()->where('key', 'New key')->firstOrFail()->text);
    }

    public function test_group_and_key_must_be_unique_together(): void
    {
        $this->make('*', 'Taken', ['en' => 'a']);

        Livewire::test(CreateTranslation::class)
            ->fillForm(['group' => '*', 'key' => 'Taken'])
            ->call('create')
            ->assertHasFormErrors(['key']);

        // The same key in another group is a different row.
        Livewire::test(CreateTranslation::class)
            ->fillForm(['group' => 'messages', 'key' => 'Taken'])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_bulk_delete_removes_rows_and_flushes_the_cache(): void
    {
        $a = $this->make('*', 'A', ['en' => 'a']);
        $b = $this->make('*', 'B', ['en' => 'b']);

        Livewire::test(ListTranslations::class)
            ->callTableBulkAction('delete', [$a, $b]);

        $this->assertSame(0, Translation::query()->count());
    }

    public function test_the_resource_is_closed_when_the_gate_is_not_defined(): void
    {
        $this->plugin()->gate('an-ability-nobody-defined');

        $this->assertFalse(TranslationResource::canViewAny());

        Livewire::test(ListTranslations::class)->assertForbidden();
    }

    public function test_the_default_gate_names_are_the_documented_ones(): void
    {
        $this->assertSame('manageTranslations', $this->plugin()->getGate());
        $this->assertSame('deleteTranslations', $this->plugin()->getDeleteGate());
    }

    public function test_deleting_needs_its_own_gate(): void
    {
        $row = $this->make('*', 'A', ['en' => 'a']);
        $this->plugin()->deleteGate('an-ability-nobody-defined');

        $this->assertTrue(TranslationResource::canViewAny());
        $this->assertFalse(TranslationResource::canDeleteAny());
        $this->assertFalse(TranslationResource::canDelete($row));

        Livewire::test(ListTranslations::class)
            ->assertSuccessful()
            ->assertTableBulkActionHidden('delete')
            ->assertTableActionHidden('delete', $row);
    }

    public function test_authorize_closure_replaces_the_gate(): void
    {
        $this->plugin()->gate('an-ability-nobody-defined')->authorize(fn ($user): bool => $user->email === 'emma@example.com');

        $this->assertFalse(TranslationResource::canViewAny());

        $this->actingAs(\Workbench\App\Models\User::factory()->create(['email' => 'emma@example.com']));

        $this->assertTrue(TranslationResource::canViewAny());
    }

    public function test_a_guest_is_denied(): void
    {
        auth()->logout();

        $this->assertFalse(TranslationResource::canViewAny());
    }

    public function test_the_list_actions_exist(): void
    {
        Livewire::test(ListTranslations::class)
            ->assertActionExists('scan')
            ->assertActionExists('export')
            ->assertActionExists('import')
            ->assertActionExists('quickTranslate');
    }

    public function test_the_scan_action_notifies(): void
    {
        Livewire::test(ListTranslations::class)
            ->callAction('scan', ['prune' => false])
            ->assertHasNoActionErrors();

        Notification::assertNotified(__('filament-translation-manager::filament-translation-manager.scan.done'));
    }

    public function test_the_export_action_downloads_the_locale(): void
    {
        $this->make('*', 'Hello', ['en' => 'Hello', 'uk' => 'Привіт']);

        Livewire::test(ListTranslations::class)
            ->callAction('export', ['locale' => 'uk'])
            ->assertFileDownloaded('uk.json', (new TranslationTransfer)->exportJson('uk'), 'application/json');

        $this->assertStringContainsString('"Hello": "Привіт"', (new TranslationTransfer)->exportJson('uk'));
    }

    public function test_the_navigation_badge_counts_incomplete_rows(): void
    {
        $this->make('*', 'Complete', ['en' => 'a', 'uk' => 'б', 'de' => 'c']);
        $this->make('*', 'Partial', ['en' => 'a']);

        $this->assertNull(TranslationResource::getNavigationBadge());

        $this->plugin()->navigationBadge();

        $this->assertSame('1', TranslationResource::getNavigationBadge());
    }
}
