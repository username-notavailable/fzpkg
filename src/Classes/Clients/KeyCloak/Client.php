<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Exception\BadResponseException;
use Firebase\JWT\JWT;
use Fuzzy\Fzpkg\Classes\Redis\RedisLock;

class Client
{
    protected $httpClient;
    protected $requestCountMax;
    protected $requestSleepValue;
    protected $redis;

    private $keyCloakHost;
    private $openIdConfigurations;

    public $authType;
    public $authRealm;
    public $authClientId;
    public $authClientSecret;
    public $authPayload;
    public $authPubKeyPath;
    public $authPvtKeyPath;
    public $authSignatureAlgorithm;

    public function __construct(string $keyCloakHost)
    {
        $this->httpClient = new GuzzleClient();
        $this->requestCountMax = 1;
        $this->requestSleepValue = 2;
        $this->redis = Redis::connection();

        $this->keyCloakHost = rtrim($keyCloakHost, '/');
        $this->openIdConfigurations = [];

        $this->authType = env('KC_AUTH_TYPE', ''); // ClientSecret, SignedJwt, SignedJwtClientSecret
        $this->authRealm = env('KC_REALM_NAME', 'master');
        $this->authClientId = env('KC_CLIENT_ID', '');
        $this->authClientSecret = env('KC_CLIENT_SECRET', '');
        $this->authPayload = [];
        $this->authPubKeyPath = storage_path('app/' . env('KC_PUB_KEY_NAME', ''));
        $this->authPvtKeyPath = storage_path('app/' . env('KC_PVT_KEY_NAME', ''));
        $this->authSignatureAlgorithm = 'RS256';

        if ($this->authType === 'SignedJwtClientSecret') {
            $this->authSignatureAlgorithm = 'HS256';
        }
    }

    protected function loadOpenIdConfiguration(string $realm) : bool
    {
        $cacheKey = 'kc_' . strtolower($realm) . '_openid_conf';

        if($this->redis->exists($cacheKey)) {
            $data = $this->redis->get($cacheKey);

            if (!is_null($data)) {
                $this->openIdConfigurations[$realm] = json_decode($data, null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
                return true;
            }
            else {
                Log::error(__METHOD__ . ': Read from redis "' . $cacheKey . '" failed');

                $response = $this->makeHttpRequest('GET', $this->keyCloakHost . '/realms/' . $realm . '/.well-known/openid-configuration');

                if ($response->getStatusCode() === 200) {
                    $this->openIdConfigurations[$realm] = json_decode((string) $response->getBody(), null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
                    return true;
                }
            }
        }
        else {
            $response = $this->makeHttpRequest('GET', $this->keyCloakHost . '/realms/' . $realm . '/.well-known/openid-configuration');

            if ($response->getStatusCode() === 200) {
                $this->redis->set($cacheKey, (string) $response->getBody(), 'EX', 600);
                $this->openIdConfigurations[$realm] = json_decode((string) $response->getBody(), null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
                return true;
            }
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
        }
        else {
            $cacheKey = 'kc_' . strtolower($realm) . '_cert';

            if($this->redis->exists($cacheKey)) {
                $data = $this->redis->get($cacheKey);

                if (!is_null($data)) {
                    return new RequestResult(true, null, json_decode($data, null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR));
                }
                else {
                    Log::error(__METHOD__ . ': Read from redis "' . $cacheKey . '" failed');

                    $response = $this->makeHttpRequest('GET', $this->openIdConfigurations[$realm]['jwks_uri'], []);

                    if ($response->getStatusCode() === 200) {
                        return new RequestResult(false, $response, json_decode((string) $response->getBody(), null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR));
                    }
                }
            }
            else {
                $response = $this->makeHttpRequest('GET', $this->openIdConfigurations[$realm]['jwks_uri'], []);

                if ($response->getStatusCode() === 200) {
                    $this->redis->set($cacheKey, (string) $response->getBody(), 'EX', 3600);
                    return new RequestResult(false, $response, json_decode((string) $response->getBody(), null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR));
                }
            }

            return new RequestResult(false, $response, []);
        }

        return false;
    }

    protected function doClientAuth() : mixed
    {
        switch ($this->authType) {
            case 'ClientSecret':
                return $this->doClientSecretAuth($this->authRealm, $this->authClientId, $this->authClientSecret);

            case 'SignedJwt':
                return $this->doClientSignedJwtAuth($this->authRealm, $this->authClientId, $this->authPvtKeyPath, $this->authPayload, $this->authSignatureAlgorithm);

            case 'SignedJwtClientSecret':
                return $this->doSignedJwtClientSecretAuth($this->authRealm, $this->authClientId, $this->authClientSecret, $this->authPayload, $this->authSignatureAlgorithm);

            default:
                throw new \Exception('Unsupported authType "' . $this->authType . '"');
        }
    }

    protected function doClientSecretAuth(string $realm, string $clientId, string $clientSecret) : mixed
    {
        try
        {
            foreach (['realm', 'clientId', 'clientSecret'] as $param) {
                if (empty($$param)) {
                    throw new \Exception('Param "' . $param . '" cannot be empty, check auth params');
                }
            }

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
                return new RequestResult(false, $response, json_decode((string) $response->getBody(), null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR));
            }
    
            return new RequestResult(false, $response, []);
        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $realm . '") Exception: ' . $e->getMessage());
            return false;
        }
    }

    protected function doClientSignedJwtAuth(string $realm, string $clientId, string $pvtKeyPath, array $payload = [], string $signatureAlgorithm = 'RS256') : mixed
    {
        try
        {
            foreach (['realm', 'clientId', 'pvtKeyPath', 'signatureAlgorithm'] as $param) {
                if (empty($$param)) {
                    throw new \Exception('Param "' . $param . '" cannot be empty, check auth params');
                }
            }

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
                return new RequestResult(false, $response, json_decode((string) $response->getBody(), null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR));
            }
    
            return new RequestResult(false, $response, []);
        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $realm . '") Exception: ' . $e->getMessage());
            return false;
        }
    }

    protected function doSignedJwtClientSecretAuth(string $realm, string $clientId, string $clientSecret, array $payload = [], string $signatureAlgorithm = 'HS256') : mixed
    {
        try
        {
            foreach (['realm', 'clientId', 'clientSecret', 'signatureAlgorithm'] as $param) {
                if (empty($$param)) {
                    throw new \Exception('Param "' . $param . '" cannot be empty, check auth params');
                }
            }

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
                return new RequestResult(false, $response, json_decode((string) $response->getBody(), null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR));
            }
    
            return new RequestResult(false, $response, []);
        }catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $realm . '") Exception: ' . $e->getMessage());
            return false;
        }
    }

    public function doTokenRefresh(array $jsonToken) : mixed
    {
        try
        {
            if (!array_key_exists($this->authRealm, $this->openIdConfigurations) && !$this->loadOpenIdConfiguration($this->authRealm)) {
                Log::error(__METHOD__ . ': Get realm config for realm "' . $this->authRealm . '" failed');
                return false;
            }
    
            if (!in_array('refresh_token', $this->openIdConfigurations[$this->authRealm]['grant_types_supported'])) {
                Log::error(__METHOD__ . ': Grant type "refresh_token" not supported for realm "' . $this->authRealm . '"');
                return false;
            }

            list($headersB64, $payloadB64, $sig) = explode('.', $jsonToken['access_token']);
            $decoded = json_decode(base64_decode($payloadB64), true);

            if ($this->authType === 'ClientSecret') {
                $data = [
                    'client_id' => $decoded['client_id'],
                    'client_secret' => $this->authClientSecret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $jsonToken['refresh_token']
                ];
            }
            else {
                $iat = time();
                $exp = $iat + 300;
        
                //### https://www.iana.org/assignments/jwt/jwt.xhtml
        
                $payload = [
                    'iss' => $decoded['client_id'],
                    'sub' => $decoded['client_id'],
                    'jti' => $decoded['jti'],
                    'nbf' => 0,
                    'aud' => $decoded['iss'],
                    'iat' => $iat,
                    'exp' => $exp
                ];
        
                if ($this->authType === 'SignedJwt') {
                    $jwt = JWT::encode($payload, file_get_contents($this->authPvtKeyPath), $this->authSignatureAlgorithm);
                }
                else { // SignedJwtClientSecret
                    $jwt = JWT::encode($payload, $this->authClientSecret, $this->authSignatureAlgorithm);
                }
        
                $data = [
                    'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                    'client_assertion' => $jwt,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $jsonToken['refresh_token']
                ];
            }

            $body = http_build_query($data);

            $response = $this->makeHttpRequest('POST', $this->openIdConfigurations[$this->authRealm]['token_endpoint'], [
                'body' => $body,
                'headers' => [
                    'Accept' => '*/*',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ]);
    
            if ($response->getStatusCode() === 200) {
                return new RequestResult(false, $response, json_decode((string) $response->getBody(), null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR));
            }
    
            return new RequestResult(false, $response, []);
        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $this->authRealm . '") Exception: ' . $e->getMessage());
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
            
            } catch (BadResponseException $e) {
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

        } while(!$requestDone);

        return $response;
    }

    public function makeHttpRequestWithBearerToken(string $method, string $requestUrl, array $options = []) : mixed
    {
        $cacheKey = 'kc_' . strtolower($this->authRealm) . '_auth_token';

        if (RedisLock::lock($this->redis, $cacheKey)) {
            $jsonToken = $this->getJsonToken($cacheKey);
        }
        else {
            return false;
        }

        RedisLock::unlock($this->redis, $cacheKey);

        if (!$jsonToken) {
            return false;
        }
        else if ($jsonToken['token_type'] !== 'Bearer') {
            Log::error(__METHOD__ . ': Unsupported token type "' . $jsonToken['token_type']. '" (requested Bearer)');
            return false;
        }
        else {
            $options['headers']['AUTHORIZATION'] = 'Bearer:' . $jsonToken['access_token'];

            $response = $this->makeHttpRequest($method, $requestUrl, $options);

            if ($response->getStatusCode() === 403) {
                if (RedisLock::lock($this->redis, $cacheKey)) {
                    if($this->redis->exists($cacheKey)) {
                        $this->redis->del($cacheKey);
                    }

                    if (array_key_exists('refresh_token', $jsonToken)) {
                        $authResponse = $this->doTokenRefresh($jsonToken);

                        if ($authResponse !== false && $authResponse->rawResponse->getStatusCode() === 200) {
                            $this->redis->set($cacheKey, json_encode($authResponse->json, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR), 'EX', $authResponse->json['expires_in']);
                            RedisLock::unlock($this->redis, $cacheKey);

                            $response = $this->makeHttpRequestWithBearerToken($method, $requestUrl, $options);
                        }
                        else {
                            RedisLock::unlock($this->redis, $cacheKey);
                            return false;
                        }
                    }
                    else {
                        RedisLock::unlock($this->redis, $cacheKey);
                        $response = $this->makeHttpRequestWithBearerToken($method, $requestUrl, $options);
                    }
                }
                else {
                    return false;
                }
            }

            return $response;
        }
    }

    private function getJsonToken(string $cacheKey) : mixed
    {
        if($this->redis->exists($cacheKey)) {
            $data = $this->redis->get($cacheKey);

            if (!is_null($data)) {
                $jsonToken = json_decode($data, null, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
            }
            else {
                Log::error(__METHOD__ . ': Read from redis "' . $cacheKey . '" failed');

                $authResponse = $this->doClientAuth();

                if ($authResponse !== false && $authResponse->rawResponse->getStatusCode() === 200) {
                    $jsonToken = $authResponse->json;
                }
                else {
                    return false;
                }
            }
        }
        else
        {
            $authResponse = $this->doClientAuth();

            if ($authResponse !== false && $authResponse->rawResponse->getStatusCode() === 200) {
                $this->redis->set($cacheKey, json_encode($authResponse->json, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR), 'EX', $authResponse->json['expires_in']);
                $jsonToken = $authResponse->json;
            }
            else {
                return false;
            }
        }

        return $jsonToken;
    }
}