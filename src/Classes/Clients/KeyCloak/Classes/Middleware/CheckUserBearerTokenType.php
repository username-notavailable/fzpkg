<?php

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Client;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckUserBearerTokenType
{
    protected $kcClient;

    public function __construct(Client $kcClient) 
    {
        $this->kcClient = $kcClient;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $result = $this->kcClient->getDecodedUserAccessTokenFromRequest($request);

        if ($result['code'] !== 200) {
            throw new HttpException($result['code'], $result['reason']);
        }
        else {
            return $next($request);
        }
    }
}
