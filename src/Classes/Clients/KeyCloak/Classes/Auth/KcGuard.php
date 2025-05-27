<?php

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Authenticatable;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Client;
use Illuminate\Support\Facades\Log;

class KcGuard implements Guard
{
    public $kcClient;

    public function __construct(Client $kcClient)
    {
        $this->kcClient = $kcClient;
    }

    public function doUserBackendLogin(array $credentials) : bool // password
    {
        if (session()->exists('__fz_kcUserToken_' . $this->kcClient->getCurrentKeycloakClientIdx())) {
            return true;
        }

        $result = $this->kcClient->doUserClientCredentialsAuth($credentials['email'], $credentials['password']);

        if (!is_null($result)) {
            if ($result->rawResponse->getStatusCode() === 200) {
                session()->put('__fz_kcUserToken_' . $this->kcClient->getCurrentKeycloakClientIdx(), $result->json);
                return true;
            }
        }
        
        return false;
    }

    public function doUserFrontendLogin(string $authorizationCode, string $redirectUri, string $sessionState) : bool // authorization_code
    {
        if (session()->exists('__fz_kcUserToken_' . $this->kcClient->getCurrentKeycloakClientIdx())) {
            return true;
        }

        $result = $this->kcClient->doUserAuthorizationCodeAuth($authorizationCode, $redirectUri, $sessionState);

        if (!is_null($result)) {
            if ($result->rawResponse->getStatusCode() === 200) {
                $result->json['authorization_code'] = $authorizationCode;

                session()->put('__fz_kcUserToken_' . $this->kcClient->getCurrentKeycloakClientIdx(), $result->json);
                return true;
            }
        }
        
        return false;
    }

    public function doUserLogout(string $clientIdx) : bool
    {
        if($this->unsetClientIdxUserToken($clientIdx)) {
            session()->regenerate();

            return true;
        }

        return false;
    }

    public function unsetClientIdxUserToken(string $clientIdx) : bool
    {
        if (session()->exists('__fz_kcUserToken_' . $clientIdx)) {
            session()->forget('__fz_kcUserToken_' . $clientIdx);

            if (session()->exists('__fz_kcUserTokenProfile_' . $clientIdx)) {
                session()->forget('__fz_kcUserTokenProfile_' . $clientIdx);
            }

            if (session()->exists('__fz_kcUserTokenProfileId_' . $clientIdx)) {
                session()->forget('__fz_kcUserTokenProfileId_' . $clientIdx);
            }

            return true;
        }
        else {
            return false;
        }
    }

    public function getClientIdxUserToken(?string $clientIdx = null) : ?array
    {
        if (is_null($clientIdx)) {
            $clientIdx = $this->kcClient->getCurrentKeycloakClientIdx();
        }

        if (session()->exists('__fz_kcUserToken_' . $clientIdx)) {
            return session()->get('__fz_kcUserToken_' . $clientIdx);
        }
        else {
            return null;
        }
    }

    public function check()
    {
        return session()->exists('__fz_kcUserToken_' . $this->kcClient->getCurrentKeycloakClientIdx());
    }

    public function user()
    {
        return null;
    }

    public function hasUser()
    {
        return false;
    }

    public function setUser(Authenticatable $user)
    {
        return null;
    }

    public function getClientIdxUserTokenProfile() : ?array
    {
        try {
            if (session()->exists('__fz_kcUserToken_' . $this->kcClient->getCurrentKeycloakClientIdx())) {
                if (session()->exists('__fz_kcUserTokenProfile_' . $this->kcClient->getCurrentKeycloakClientIdx())) {
                    return session()->get('__fz_kcUserTokenProfile_' . $this->kcClient->getCurrentKeycloakClientIdx());
                }
                else {
                    $userJsonToken = $this->getClientIdxUserToken($this->kcClient->getCurrentKeycloakClientIdx());

                    if (!is_null($userJsonToken)) {
                        $openIdConfiguration = $this->kcClient->getOpenIdConfiguration($this->kcClient->authRealm);

                        if (is_null($openIdConfiguration)) {
                            Log::error(__METHOD__ . ': Get realm config for realm "' . $this->kcClient->authRealm . '" failed');
                        }
                        else {
                            $response = $this->kcClient->doRequestWithAccessToken($userJsonToken['access_token'], 'GET', $openIdConfiguration['userinfo_endpoint']);

                            if ($response->getStatusCode() === 200) {
                                $jsonBody = json_decode((string)$response->getBody(), true);

                                if (!is_null($jsonBody)) {
                                    session()->put('__fz_kcUserTokenProfile_' . $this->kcClient->getCurrentKeycloakClientIdx(), $jsonBody);
                                }

                                return $jsonBody;    
                            }
                        }
                    }
                    else {
                        Log::warning(__METHOD__ . ': Read bearer token from sesson failed');
                    }
                }
            }
        } catch(\Throwable $e) {
            Log::error(__METHOD__ . ': ' . $e->getMessage());
        }

        return null;
    }

    public function getClientIdxUserTokenProfileId() : ?string
    {
        if (session()->exists('__fz_kcUserTokenProfileId_' . $this->kcClient->getCurrentKeycloakClientIdx())) {
            return session()->get('__fz_kcUserTokenProfileId_' . $this->kcClient->getCurrentKeycloakClientIdx());
        }
        else {
            if (session()->exists('__fz_kcUserToken_' . $this->kcClient->getCurrentKeycloakClientIdx())) {
                if (session()->exists('__fz_kcUserTokenProfile_' . $this->kcClient->getCurrentKeycloakClientIdx())) {
                    $profile = session()->get('__fz_kcUserTokenProfile_' . $this->kcClient->getCurrentKeycloakClientIdx());
                }
                else {
                    $profile = $this->getClientIdxUserTokenProfile();

                    if (is_null($profile)) {
                        return null;
                    }
                }

                session()->put('__fz_kcUserTokenProfileId_' . $this->kcClient->getCurrentKeycloakClientIdx(), $profile['sub']);

                return $profile['sub'];
            }
            else {
                return null;
            }
        }
    }

    public function guest()
    {
        return !session()->exists('__fz_kcUserToken_' . $this->kcClient->getCurrentKeycloakClientIdx());
    }

    public function id()
    {
        return null;
    }

    public function validate(array $credentials = [])
    {
        return false;
    }
}