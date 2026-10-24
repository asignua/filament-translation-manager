<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Feature;

use Asignua\FilamentTranslationManager\Resources\TranslationResource;
use Asignua\FilamentTranslationManager\Support\LocaleSwitcher;
use Asignua\FilamentTranslationManager\Tests\TestCase;
use Asignua\FilamentTranslationManager\TranslationManagerPlugin;
use Filament\Facades\Filament;

class LanguageSwitcherTest extends TestCase
{
    public function test_choosing_a_locale_stores_it_in_the_session(): void
    {
        $this->post(route('filament-translation-manager.locale', 'uk'))->assertRedirect();

        $this->assertSame('uk', session('translation-manager.locale'));
    }

    public function test_an_unconfigured_locale_is_refused(): void
    {
        $this->post(route('filament-translation-manager.locale', 'xx'))->assertNotFound();

        $this->assertNull(session('translation-manager.locale'));
    }

    public function test_a_guest_cannot_switch(): void
    {
        auth()->logout();

        $this->post(route('filament-translation-manager.locale', 'uk'))->assertForbidden();
        $this->assertNull(session('translation-manager.locale'));
    }

    public function test_the_choice_is_applied_when_the_panel_boots(): void
    {
        $this->withSession(['translation-manager.locale' => 'uk']);

        Filament::getPanel('admin')->getPlugin(TranslationManagerPlugin::ID)->boot(Filament::getPanel('admin'));

        $this->assertSame('uk', app()->getLocale());
    }

    public function test_a_stale_locale_in_the_session_is_ignored(): void
    {
        $this->withSession(['translation-manager.locale' => 'xx']);

        $this->assertNull(LocaleSwitcher::chosen());
    }

    public function test_the_choice_can_be_remembered_on_the_user(): void
    {
        config(['filament-translation-manager.language_switcher_options.user_column' => 'locale']);

        $this->post(route('filament-translation-manager.locale', 'de'));

        $this->assertSame('de', auth()->user()->fresh()->locale);

        $this->flushSession();
        $this->startSession();
        $this->assertSame('de', LocaleSwitcher::chosen());
    }

    public function test_the_switcher_is_rendered_in_the_topbar_only_when_enabled(): void
    {
        // The workbench panel enables it.
        $this->get(TranslationResource::getUrl())->assertSee(route('filament-translation-manager.locale', 'uk'), false);
    }

    public function test_it_is_off_by_default(): void
    {
        $this->assertFalse(config('filament-translation-manager.language_switcher'));
        $this->assertFalse(TranslationManagerPlugin::make()->isLanguageSwitcherEnabled());
    }
}
