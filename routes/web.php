<?php

declare(strict_types=1);

use Asignua\FilamentTranslationManager\Http\Controllers\SwitchLocaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->post('translation-manager/locale/{locale}', SwitchLocaleController::class)
    ->name('filament-translation-manager.locale');
