<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $workbench = dirname(__DIR__, 2);

        // The scan reads the workbench's own views and lang files.
        $this->app->useLangPath($workbench.'/lang');
        config([
            'filament-translation-manager.scan.paths' => [$workbench.'/app', $workbench.'/resources/views'],
            'filament-translation-manager.scan.exclude' => ['vendor', 'tests'],
        ]);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(dirname(__DIR__, 2).'/resources/views', 'workbench');

        // The plugin is closed unless these are defined; in the workbench every user may do everything.
        Gate::define('manageTranslations', static fn (): bool => true);
        Gate::define('deleteTranslations', static fn (): bool => true);
    }
}
