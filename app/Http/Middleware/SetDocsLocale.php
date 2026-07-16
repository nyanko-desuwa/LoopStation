<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve and persist the locale for the API docs pages (/docs/api).
 *
 * Priority: ?lang query param > docs_locale cookie > default 'en'.
 * The chosen locale is remembered via a long-lived cookie and the
 * generated OpenAPI spec is cached per locale so switching languages
 * never serves a stale, single-language document.
 */
class SetDocsLocale
{
    /** Cookie that remembers the docs language choice. */
    private const COOKIE = 'docs_locale';

    /** Default language for the docs pages. */
    private const DEFAULT_LOCALE = 'en';

    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['en']);

        $requested = $request->query('lang');
        $cookie = $request->cookie(self::COOKIE);

        $locale = collect([$requested, $cookie, self::DEFAULT_LOCALE])
            ->first(fn ($value) => in_array($value, $supported, true));

        app()->setLocale($locale);

        // Cache the generated spec per locale so VI and EN documents
        // do not overwrite each other under a single cache key.
        config(['scramble.cache.key' => config('scramble.cache.key').'.'.$locale]);

        $response = $next($request);

        // Remember the choice for a year (only when explicitly switched or
        // not yet stored) so the page reopens in the same language.
        if ($requested !== null || $cookie === null) {
            $response->headers->setCookie(
                Cookie::create(self::COOKIE, $locale, now()->addYear(), '/')
            );
        }

        return $response;
    }
}
