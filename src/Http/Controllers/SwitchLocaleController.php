<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Http\Controllers;

use Asignua\FilamentTranslationManager\Support\Locales;
use Asignua\FilamentTranslationManager\Support\LocaleSwitcher;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchLocaleController
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        // The panel's guard, not necessarily the default one.
        $user = Filament::auth()->user() ?? $request->user();

        abort_if($user === null, 403);
        abort_unless(in_array($locale, Locales::codes(), true), 404);

        LocaleSwitcher::remember($locale, $user);

        return back();
    }
}
