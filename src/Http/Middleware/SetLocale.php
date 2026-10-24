<?php

declare(strict_types=1);

namespace Asignua\FilamentTranslationManager\Http\Middleware;

use Asignua\FilamentTranslationManager\Support\LocaleSwitcher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the locale picked in the language switcher. The plugin applies it itself when the panel boots; add this
 * middleware (after StartSession) to ->middleware([...], isPersistent: true) for routes outside the panel.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        LocaleSwitcher::apply();

        return $next($request);
    }
}
