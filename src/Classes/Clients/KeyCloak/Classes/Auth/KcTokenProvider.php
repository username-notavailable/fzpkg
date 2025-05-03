<?php

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\Auth;

use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Client;

class KcTokenProvider
{
    private $kcClient;

    public function __construct(Client $kcClient)
    {
        $this->kcClient = $kcClient;
    }

    /*public function attempt(array $credentials)
    {
        if (session()->exists('__kcToken')) {
            return true;
        }

        $result = $this->kcClient->doUserAuth($credentials['email'], $credentials['password']);

        if (!is_null($result)) {
            if ($result->rawResponse->getStatusCode() === 200) {
                session()->put('__kcToken', $result->json);
                return true;
            }
        }
        else {
            return false;
        }
    }*/
}