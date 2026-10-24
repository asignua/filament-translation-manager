<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Feature;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Pages\QuickTranslate;
use Asignua\FilamentTranslationManager\Tests\TestCase;
use Asignua\FilamentTranslationManager\TranslationManagerPlugin;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Livewire\Livewire;

class QuickTranslateTest extends TestCase
{
    private function make(string $key, array $text): Translation
    {
        $row = new Translation;
        $row->group = '*';
        $row->key = $key;
        $row->text = $text;
        $row->save();

        return $row;
    }

    public function test_it_starts_on_the_first_non_default_locale_with_the_first_missing_row(): void
    {
        $this->make('Done', ['en' => 'Done', 'uk' => 'Готово']);
        $first = $this->make('First', ['en' => 'First']);
        $this->make('Second', ['en' => 'Second']);

        Livewire::test(QuickTranslate::class)
            ->assertSet('locale', 'uk')
            ->assertSet('recordId', $first->getKey())
            ->assertSee('First')
            ->assertSee('2 left');
    }

    public function test_save_and_next_walks_through_every_missing_row(): void
    {
        $a = $this->make('A', ['en' => 'A']);
        $b = $this->make('B', ['en' => 'B', 'uk' => 'Б']);
        $c = $this->make('C', ['en' => 'C']);

        Livewire::test(QuickTranslate::class)
            ->assertSet('recordId', $a->getKey())
            ->set('value', 'Ей')
            ->call('save')
            ->assertSet('recordId', $c->getKey())
            ->assertSet('value', '')
            ->set('value', 'Сі')
            ->call('save')
            ->assertSet('recordId', null)
            ->assertSee(__('filament-translation-manager::filament-translation-manager.quick.all_done'));

        Notification::assertNotified(__('filament-translation-manager::filament-translation-manager.quick.saved'));
        $this->assertSame('Ей', $a->refresh()->textFor('uk'));
        $this->assertSame('Сі', $c->refresh()->textFor('uk'));
        $this->assertSame('Б', $b->refresh()->textFor('uk'));
        // The English source is untouched.
        $this->assertSame('A', $a->textFor('en'));
    }

    public function test_an_empty_value_is_not_saved(): void
    {
        $a = $this->make('A', ['en' => 'A']);

        Livewire::test(QuickTranslate::class)
            ->set('value', '   ')
            ->call('save')
            ->assertSet('recordId', $a->getKey());

        Notification::assertNotified(__('filament-translation-manager::filament-translation-manager.quick.empty'));
        $this->assertTrue($a->refresh()->isMissingIn('uk'));
    }

    public function test_skip_moves_on_without_writing(): void
    {
        $a = $this->make('A', ['en' => 'A']);
        $b = $this->make('B', ['en' => 'B']);

        Livewire::test(QuickTranslate::class)
            ->call('skip')
            ->assertSet('recordId', $b->getKey())
            ->call('skip')
            ->assertSet('recordId', null);

        $this->assertTrue($a->refresh()->isMissingIn('uk'));
        $this->assertTrue($b->refresh()->isMissingIn('uk'));
    }

    public function test_changing_the_locale_restarts_the_walk(): void
    {
        $a = $this->make('A', ['en' => 'A', 'uk' => 'А']);
        $b = $this->make('B', ['en' => 'B', 'uk' => 'Б', 'de' => 'B']);

        Livewire::test(QuickTranslate::class)
            ->assertSet('recordId', null)
            ->set('locale', 'de')
            ->assertSet('recordId', $a->getKey())
            ->assertSee('А');
    }

    public function test_the_other_locales_are_shown_as_the_source(): void
    {
        $this->make('Greeting', ['en' => 'Hello there', 'de' => 'Hallo']);

        Livewire::test(QuickTranslate::class)
            ->assertSee('Hello there')
            ->assertSee('Hallo');
    }

    public function test_an_unknown_locale_is_rejected(): void
    {
        Livewire::test(QuickTranslate::class)
            ->set('locale', 'xx')
            ->assertStatus(422);
    }

    public function test_the_locale_can_come_from_the_query_string(): void
    {
        $a = $this->make('A', ['en' => 'A', 'uk' => 'А']);

        $this->get(QuickTranslate::getUrl().'?locale=de')->assertSuccessful()->assertSee('A');
    }

    public function test_the_page_is_gated_and_can_be_switched_off(): void
    {
        $plugin = Filament::getPanel('admin')->getPlugin(TranslationManagerPlugin::ID);

        $this->assertTrue(QuickTranslate::canAccess());

        $plugin->quickTranslate(false);
        $this->assertFalse(QuickTranslate::canAccess());

        $plugin->quickTranslate()->gate('an-ability-nobody-defined');
        $this->assertFalse(QuickTranslate::canAccess());
    }

    public function test_a_forbidden_user_cannot_save_even_with_a_stale_page(): void
    {
        $a = $this->make('A', ['en' => 'A']);

        $component = Livewire::test(QuickTranslate::class)->set('value', 'x');

        Filament::getPanel('admin')->getPlugin(TranslationManagerPlugin::ID)->gate('an-ability-nobody-defined');

        $component->call('save')->assertForbidden();
        $this->assertTrue($a->refresh()->isMissingIn('uk'));
    }
}
