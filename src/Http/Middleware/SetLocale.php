<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Http\Middleware;

use Asignua\FilamentTranslationManager\Support\LocaleSwitcher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the locale picked in the language switcher. The plugin adds it to the panel's auth middleware (persistent, so
 * Livewire updates get it too); add it after StartSession to routes outside the panel.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        LocaleSwitcher::apply();

        return $next($request);
    }
}
