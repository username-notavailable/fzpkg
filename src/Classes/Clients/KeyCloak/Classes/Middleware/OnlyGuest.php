<?php

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OnlyGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard(config('fz.default.keycloak.authGuardName'))->guest()) {
            return $next($request);
        }
        else {
            return redirect()->route(config('fz.default.keycloak.onlyGuestFailRouteName'));
        }
    }
}
