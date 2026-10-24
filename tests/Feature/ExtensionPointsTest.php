<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Tests\Feature;

use Asignua\FilamentTranslationManager\Models\Translation;
use Asignua\FilamentTranslationManager\Pages\QuickTranslate;
use Asignua\FilamentTranslationManager\Resources\TranslationResource;
use Asignua\FilamentTranslationManager\Resources\TranslationResource\Pages\ListTranslations;
use Asignua\FilamentTranslationManager\Support\Locales;
use Asignua\FilamentTranslationManager\Tests\TestCase;
use Asignua\FilamentTranslationManager\TranslationManagerPlugin;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

/**
 * What a host (eEgnith is the first) relies on to wrap the plugin without copying it.
 */
class ExtensionPointsTest extends TestCase
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

    public function test_the_table_name_comes_from_the_config(): void
    {
        $this->assertSame('language_lines', (new Translation)->getTable());

        config(['filament-translation-manager.table' => 'ee_translations']);

        $this->assertSame('ee_translations', (new Translation)->getTable());
    }

    public function test_the_groups_config_limits_the_list_the_count_and_quick_translate(): void
    {
        config(['filament-translation-manager.groups' => ['*']]);
        $front = $this->make('*', 'Front', ['en' => 'Front']);
        $other = $this->make('messages', 'title', ['en' => 'Title']);

        $this->assertSame([$front->id], TranslationResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame(1, TranslationResource::missingCount());
        $this->assertFalse(TranslationResource::showsGroup());

        Livewire::test(ListTranslations::class)
            ->assertCanSeeTableRecords([$front])
            ->assertCanNotSeeTableRecords([$other])
            ->assertTableColumnExists('group', fn ($column) => $column->isHidden());

        Livewire::test(QuickTranslate::class)
            ->assertSet('recordId', $front->id)
            ->assertSee('1 left');
    }

    public function test_every_group_is_shown_by_default(): void
    {
        $this->make('*', 'Front', ['en' => 'Front']);
        $this->make('messages', 'title', ['en' => 'Title']);

        $this->assertSame(2, TranslationResource::getEloquentQuery()->count());
        $this->assertTrue(TranslationResource::showsGroup());
    }

    public function test_a_subclass_can_close_create_and_narrow_the_query_and_add_columns_and_actions(): void
    {
        $subclass = new class extends TranslationResource
        {
            public static function canCreate(): bool
            {
                return false;
            }

            protected static function scopeQuery(Builder $query): Builder
            {
                return $query->where('key', 'like', 'Keep%');
            }
        };

        $this->make('*', 'Keep me', ['en' => 'a']);
        $this->make('*', 'Drop me', ['en' => 'b']);

        $this->assertFalse($subclass::canCreate());
        $this->assertTrue(TranslationResource::canCreate());
        $this->assertSame(['Keep me'], $subclass::getEloquentQuery()->pluck('key')->all());
        $this->assertSame(1, $subclass::missingCount());
    }

    public function test_the_registered_resource_can_be_replaced_or_dropped(): void
    {
        $plugin = TranslationManagerPlugin::make();

        $this->assertSame(TranslationResource::class, $plugin->getResourceClass());
        $this->assertFalse($plugin->resource(false)->getResourceClass());
        $this->assertSame('App\\MyResource', $plugin->resource('App\\MyResource')->getResourceClass());
    }

    public function test_navigation_group_and_label_may_be_closures_resolved_late(): void
    {
        $plugin = TranslationManagerPlugin::make()
            ->navigationGroup(static fn (): string => 'Lazy group')
            ->navigationLabel(static fn (): string => 'Lazy label');

        $this->assertSame('Lazy group', $plugin->getNavigationGroup());
        $this->assertSame('Lazy label', $plugin->getNavigationLabel());
    }

    public function test_locales_can_come_from_a_closure_read_on_every_call(): void
    {
        Locales::override(null);
        $codes = ['uk', 'en'];
        Locales::resolveUsing(static function () use (&$codes): array {
            return array_map(static fn (string $code): array => ['code' => $code, 'name' => strtoupper($code), 'flag' => null], $codes);
        });

        try {
            $this->assertSame(['uk', 'en'], Locales::codes());

            $codes = ['uk', 'en', 'de'];
            $this->assertSame(['uk', 'en', 'de'], Locales::codes());

            // The plugin's own locales() still wins.
            Locales::override(['fr']);
            $this->assertSame(['fr'], Locales::codes());
        } finally {
            Locales::resolveUsing(null);
        }
    }
}
