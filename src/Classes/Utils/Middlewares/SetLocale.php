<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Utils\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Fuzzy\Fzpkg\FzpkgServiceProvider;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('fz.load.cookies.locale')) {
            $useLocale = FzpkgServiceProvider::elaborateSetLocaleFromCookie();
     
            $response = $next($request);
    
            $response->headers->setCookie(cookie()->forever(name: config('fz.default.localeCookieName'), value: $useLocale, httpOnly: false));
            
            return $response;
        }
        else {
            return $next($request);
        }
    }
}
