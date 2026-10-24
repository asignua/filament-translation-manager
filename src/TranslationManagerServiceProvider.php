<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager;

use Asignua\FilamentTranslationManager\Commands\ScanCommand;
use Asignua\FilamentTranslationManager\Models\Translation;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TranslationManagerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-translation-manager';

    public function configurePackage(Package $package): void
    {
        // Translations: `__('filament-translation-manager::filament-translation-manager.<key>')`, publish tag
        // `filament-translation-manager-translations`. Migrations are publishable stubs (they skip a table that exists).
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasRoute('web')
            ->hasMigrations(['create_language_lines_table', 'add_scan_columns_to_language_lines_table'])
            ->hasCommand(ScanCommand::class);
    }

    public function packageRegistered(): void
    {
        // spatie's provider is deferred and merges its config later, with OUR value winning when it is already set.
        // So: serve rows through our model (unescaped JSON, full cache flush) unless the host chose its own.
        if (config('translation-loader.model') === null) {
            config(['translation-loader.model' => config('filament-translation-manager.model', Translation::class)]);
        }
    }
}
