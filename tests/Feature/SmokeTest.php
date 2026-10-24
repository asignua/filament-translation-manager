<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Feature;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Resources\TranslationResource;
use Asignua\FilamentTranslationManager\Tests\TestCase;
use Asignua\FilamentTranslationManager\TranslationManagerPlugin;
use Filament\Facades\Filament;

class SmokeTest extends TestCase
{
    public function test_the_panel_boots(): void
    {
        $this->assertSame('admin', Filament::getCurrentPanel()?->getId());
    }

    public function test_the_plugin_is_registered_on_the_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->hasPlugin(TranslationManagerPlugin::ID));
        $this->assertInstanceOf(TranslationManagerPlugin::class, $panel->getPlugin(TranslationManagerPlugin::ID));
        $this->assertContains(TranslationResource::class, $panel->getResources());
    }

    public function test_the_translations_are_loaded(): void
    {
        $this->assertSame('Translations', __('filament-translation-manager::filament-translation-manager.resource.plural'));
    }

    public function test_the_model_is_served_to_spatie_and_the_table_is_there(): void
    {
        $this->assertSame(Translation::class, config('translation-loader.model'));
        $this->assertSame(0, Translation::query()->count());
    }
}
