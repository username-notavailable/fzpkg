<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
//use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\BadResponseException;
use Firebase\JWT\JWT;

class Client
{
    protected $httpClient;
    protected $requestCountMax;
    protected $requestSleepValue;

    private $keyCloakHost;
    private $openIdConfigurations;

    public function __construct(string $keyCloakHost)
    {
        $this->httpClient = new GuzzleClient();
        $this->requestCountMax = 5;
        $this->requestSleepValue = 2;

        $this->keyCloakHost = rtrim($keyCloakHost, '/');
        $this->openIdConfigurations = [];
    }

    public function loadOpenIdConfiguration(string $realm) : bool
    {
        $response = $this->makeHttpRequest('GET', $this->keyCloakHost . '/realms/' . $realm . '/.well-known/openid-configuration');

        if ($response->getStatusCode() === 200) {
            $response = json_decode((string) $response->getBody(), true);
            $this->openIdConfigurations[$realm] = $response;
            return true;
        }

        return false;
    }

    public function getOpenIdConfiguration(string $realm) : mixed
    {
        if (array_key_exists($realm, $this->openIdConfigurations)) {
            return $this->openIdConfigurations[$realm];
        }
        else {
            if ($this->loadOpenIdConfiguration($realm)) {
                return $this->openIdConfigurations[$realm];
            }
            else {
                return false;
            }
        }
    }

    public function loadRealmCert(string $realm) : mixed
    {
        if (!array_key_exists($realm, $this->openIdConfigurations) && !$this->loadOpenIdConfiguration($realm)) {
            Log::error(__METHOD__ . ': Get realm config for realm "' . $realm . '" failed');
            return false;
        }
        
        $response = $this->makeHttpRequest('GET', $this->openIdConfigurations[$realm]['jwks_uri'], []);

        if ($response->getStatusCode() === 200) {
            return new RequestResult($response, json_decode((string) $response->getBody(), true));
        }

        return new RequestResult($response, []);
    }

    public function doClientSecretAuthentication(string $realm, string $clientId, string $clientSecret) : mixed
    {
        try
        {
            if (!array_key_exists($realm, $this->openIdConfigurations) && !$this->loadOpenIdConfiguration($realm)) {
                Log::error(__METHOD__ . ': Get realm config for realm "' . $realm . '" failed');
                return false;
            }
    
            if (!in_array('client_credentials', $this->openIdConfigurations[$realm]['grant_types_supported'])) {
                Log::error(__METHOD__ . ': Grant type "client_credentials" not supported for realm "' . $realm . '"');
                return false;
            }

            $data = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials'
            ];
    
            $body = http_build_query($data);
    
            $response = $this->makeHttpRequest('POST', $this->openIdConfigurations[$realm]['token_endpoint'], [
                'body' => $body,
                'headers' => [
                    'ACCEPT' => '*/*',
                    'CONTENT-TYPE' => 'application/x-www-form-urlencoded'
                ]
            ]);
    
            if ($response->getStatusCode() === 200) {
                return new RequestResult($response, json_decode((string) $response->getBody(), true));
            }
    
            return new RequestResult($response, []);
        }catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $realm . '") Exception: ' . $e->getMessage());
            return false;
        }
    }

    public function doClientSignedJwtAuthorization(string $realm, string $clientId, string $pvtKeyPath,  array $payload = [], string $signatureAlgorithm = 'RS256') : mixed
    {
        try
        {
            if (!array_key_exists($realm, $this->openIdConfigurations) && !$this->loadOpenIdConfiguration($realm)) {
                Log::error(__METHOD__ . ': Get realm config for realm "' . $realm . '" failed');
                return false;
            }
    
            if (!in_array('client_credentials', $this->openIdConfigurations[$realm]['grant_types_supported'])) {
                Log::error(__METHOD__ . ': Grant type "client_credentials" not supported for realm "' . $realm . '"');
                return false;
            }

            $iat = time();
            $exp = $iat + 300;
    
            //### https://www.iana.org/assignments/jwt/jwt.xhtml
    
            $payload = array_merge([
                'iss' => $clientId,
                'sub' => $clientId,
                'jti' => uniqid(),
                'nbf' => 0,
                'aud' => $this->keyCloakHost . '/realms/' . $realm,
                'iat' => $iat,
                'exp' => $exp
            ], $payload);
    
            $jwt = JWT::encode($payload, file_get_contents($pvtKeyPath), $signatureAlgorithm);
    
            $data = [
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                'client_assertion' => $jwt,
                'grant_type' => 'client_credentials',
            ];
    
            $body = http_build_query($data);
    
            $response = $this->makeHttpRequest('POST', $this->openIdConfigurations[$realm]['token_endpoint'], [
                'body' => $body,
                'headers' => [
                    'Accept' => '*/*',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ]);
    
            if ($response->getStatusCode() === 200) {
                return new RequestResult($response, json_decode((string) $response->getBody(), true));
            }
    
            return new RequestResult($response, []);
        }catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $realm . '") Exception: ' . $e->getMessage());
            return false;
        }
    }

    public function doSignedJwtClientSecretAuthentication(string $realm, string $clientId, string $clientSecret, array $payload = [], string $signatureAlgorithm = 'HS256') : mixed
    {
        try
        {
            if (!array_key_exists($realm, $this->openIdConfigurations) && !$this->loadOpenIdConfiguration($realm)) {
                Log::error(__METHOD__ . ': Get realm config for realm "' . $realm . '" failed');
                return false;
            }
    
            if (!in_array('client_credentials', $this->openIdConfigurations[$realm]['grant_types_supported'])) {
                Log::error(__METHOD__ . ': Grant type "client_credentials" not supported for realm "' . $realm . '"');
                return false;
            }

            $iat = time();
            $exp = $iat + 300;
    
            //### https://www.iana.org/assignments/jwt/jwt.xhtml
    
            $payload = array_merge([
                'iss' => $clientId,
                'sub' => $clientId,
                'jti' => uniqid(),
                'nbf' => 0,
                'aud' => $this->keyCloakHost . '/realms/' . $realm,
                'iat' => $iat,
                'exp' => $exp
            ], $payload);
    
            $jwt = JWT::encode($payload, $clientSecret, $signatureAlgorithm);
    
            $data = [
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                'client_assertion' => $jwt,
                'grant_type' => 'client_credentials',
            ];
    
            $body = http_build_query($data);
    
            $response = $this->makeHttpRequest('POST', $this->openIdConfigurations[$realm]['token_endpoint'], [
                'body' => $body,
                'headers' => [
                    'Accept' => '*/*',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ]);
    
            if ($response->getStatusCode() === 200) {
                return new RequestResult($response, json_decode((string) $response->getBody(), true));
            }
    
            return new RequestResult($response, []);
        }catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $realm . '") Exception: ' . $e->getMessage());
            return false;
        }
    }

    public function setRequestCountMax(int $countMax)
    {
        if ($countMax <= 0) {
            throw new \Exception('countMax must be greater than 0');
        }
        else {
            $this->requestCountMax = $countMax;
        }
    }

    public function setRequestSleepValue(int $sleepValue)
    {
        if ($sleepValue <= 0) {
            throw new \Exception('sleepValue must be greater than 0');
        }
        else {
            $this->requestSleepValue = $sleepValue;
        }
    }

    public function makeHttpRequest(string $method, string $requestUrl, array $options = []) : mixed
    {
        $requestCount = 0;
        $requestDone = false;
        $response = null;

        do {
            try {
                $response = $this->httpClient->request($method, $requestUrl, $options); 

                $requestDone = true;
                Log::info(__METHOD__ . ': HTTP Request OK: ' . $method . ' "' . $requestUrl . '" Status code = ' . $response->getStatusCode() . ' -- Status message = ' . $response->getReasonPhrase());
            }
            catch (BadResponseException $e) {
                $response = $e->getResponse();

                Log::error(__METHOD__ . ': HTTP Request FAILED: ' . $method . ' "' . $requestUrl . '" Status code = ' . $response->getStatusCode() . ' -- Status message = ' . $response->getReasonPhrase() . ' -- Body = ' . $response->getBody());

                $requestCount++;

                if ($requestCount === $this->requestCountMax) {
                    $requestDone = true;
                }
                else {
                    sleep($this->requestSleepValue);
                }
            }

        }while(!$requestDone);

        return $response;
    }
}