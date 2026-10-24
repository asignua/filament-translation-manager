<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TranslationManagerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-translation-manager';

    public function configurePackage(Package $package): void
    {
        // Translations live in resources/lang/<locale>/filament-translation-manager.php and are read as
        // `__('filament-translation-manager::filament-translation-manager.<key>')`. Publish tag: `filament-translation-manager-translations`.
        $package->name(static::$name)
            ->hasTranslations()
            ->hasViews();

        // Add a config file only when the plugin really has options: create config/filament-translation-manager.php and
        // chain `->hasConfigFile()` here (publish tag `filament-translation-manager-config`). Prefer fluent setters on the Plugin.
    }
}
