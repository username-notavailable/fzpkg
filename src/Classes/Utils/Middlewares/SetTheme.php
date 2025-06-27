<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Utils\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Fuzzy\Fzpkg\FzpkgServiceProvider;
use Illuminate\Support\Facades\Log;

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

        $responseType = $request->prefers(['text/html', 'application/json']);

        if ($responseType === 'text/html') {
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
                else {
                    Log::debug(__METHOD__ . ': view "errors.generic" not found', ['exception' => $e]);
                    return response('', $e->getStatusCode());
                }
            }
        }
        else if ($responseType === 'application/json') {
            $responseBody = ['status' => 'error', 'message' => 'Exception error'];

            if (config('app.debug')) {
                $responseBody['message'] = $e->getMessage();
            }

            return response()->json(
                data: $responseBody,
                status: 500,
                headers: [],
                options: JSON_UNESCAPED_UNICODE
            );
        }
        else {
            return response(config('app.debug') ? $e->getMessage() : '', $e->getStatusCode());
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

        $responseType = $request->prefers(['text/html', 'application/json']);

        if ($responseType === 'text/html') {
            if (view()->exists('errors.generic')) {
                $lead = __('Errore :code', ['code' => 500]);
                
                return response()->view('errors.generic', ['lead' => $lead, 'exception' => $e], 500);
            }
            else {
                Log::debug(__METHOD__ . ': view "errors.generic" not found', ['exception' => $e]);
                return response('', 500);
            }
        }
        else if ($responseType === 'application/json') {
            $responseBody = '';

            if (config('app.debug')) {
                $responseBody = ['message' => $e->getMessage()];
            }

            return response()->json(
                data: $responseBody,
                status: 500,
                headers: [],
                options: JSON_UNESCAPED_UNICODE
            );
        }
        else {
            return response(config('app.debug') ? $e->getMessage() : '', 500);
        }
    }
}
