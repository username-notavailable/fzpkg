<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Utils\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Fuzzy\Fzpkg\FzpkgServiceProvider;

class SetTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('fz.load.cookies.theme')) {
            $useTheme = FzpkgServiceProvider::elaborateSetThemeFromCookie();
    
            $response = $next($request);
    
            $response->headers->setCookie(cookie()->forever(name: config('fz.default.themeCookieName'), value: $useTheme, httpOnly: false));
            
            return $response;
        }
        else {
            return $next($request);
        }
    }

    public static function elaborateHttpExceptions(HttpException $e, Request $request) : ?Response
    {
        if (config('fz.load.cookies.locale')) {
            FzpkgServiceProvider::elaborateSetLocaleFromCookie();
        }

        if (config('fz.load.cookies.theme')) {
            FzpkgServiceProvider::elaborateSetThemeFromCookie();
        }

        if (view()->exists('errors.' . $e->getStatusCode())) {
            $view = 'errors.' . $e->getStatusCode();
        }
        else if ($e->getStatusCode() >= 400 && $e->getStatusCode() <= 499 && view()->exists('errors.4XX')) {
            $view = 'errors.4XX';
        }
        else if ($e->getStatusCode() >= 500 && $e->getStatusCode() <= 599 && view()->exists('errors.5XX')) {
            $view = 'errors.5XX';
        }

        if (isset($view)) {
            return response()->view($view, [], $e->getStatusCode());
        }
        else {
            if (view()->exists('errors.generic')) {
                $lead = __('Errore :code', ['code' => $e->getStatusCode()]);
                
                return response()->view('errors.generic', ['lead' => $lead, 'exception' => $e], $e->getStatusCode());
            }

            return null;
        }
    }

    public static function elaborateAllExceptions(\Throwable $e, Request $request) : ?Response
    {
        if (config('fz.load.cookies.locale')) {
            FzpkgServiceProvider::elaborateSetLocaleFromCookie();
        }

        if (config('fz.load.cookies.theme')) {
            FzpkgServiceProvider::elaborateSetThemeFromCookie();
        }

        if (view()->exists('errors.generic')) {
            $lead = __('Errore :code', ['code' => 500]);
            
            return response()->view('errors.generic', ['lead' => $lead, 'exception' => $e], 500);
        }

        return null;
    }
}
