<?php

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\AccessTokenRequestTrait;


class CheckUserBearerTokenType
{
    use AccessTokenRequestTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $result = $this->readUserAccessToken($request);

        if ($result['code'] !== 200) {
            return response($result['reason'], $result['code']);
        }
        else {
            return $next($request);
        }
    }
}
