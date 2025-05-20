<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use Firebase\JWT\JWT;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\ClientCache\CacheInterface;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\{GuzzleClientHandlers, GuzzleClientHandler, GlobalClientIdx, RequestResult};
use Nyholm\Psr7\Response;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\ClientCache\{RedisCache, MemcachedCache};
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;

class Client
{
    protected $httpClient;
    protected $requestCountMax;
    protected $requestsMsDelayValue;
    protected $cache;

    private $openIdConfigurations;

    private $currentKeycloakClientIdx;
    private $oldKeycloakClientIdx;
    private $oldKeycloakClientIdxData;

    public $keycloakLoginHostname;
    public $authType;
    public $authRealm;
    public $authClientId;
    public $authClientSecret;
    public $authPayload;
    public $authPubKeyPath;
    public $authPvtKeyPath;
    public $authCert;
    public $authSignatureAlgorithm;

    public $guzzleHandlerName;
    public $guzzleHandlerClass;

    public function __construct(CacheInterface $cache, ?string $keycloakClientIdx = null)
    {
        $this->httpClient = new GuzzleClient();
        $this->requestCountMax = 1;
        $this->requestsMsDelayValue = 1000;
        $this->cache = $cache;

        $this->openIdConfigurations = [];

        $this->currentKeycloakClientIdx = null;
        $this->oldKeycloakClientIdx = null;
        $this->oldKeycloakClientIdxData = [];

        $this->keycloakLoginHostname = '';
        $this->authType = ''; // ClientSecret, SignedJwt, SignedJwtClientSecret
        $this->authRealm = '';
        $this->authClientId = '';
        $this->authClientSecret = '';
        $this->authPayload = [];
        $this->authPubKeyPath = '';
        $this->authPvtKeyPath = '';
        $this->authCert = '';
        $this->authSignatureAlgorithm = '';

        $this->guzzleHandlerName = 'default';
        $this->guzzleHandlerClass = GuzzleClientHandler::class;

        if (is_null($keycloakClientIdx)) {
            $keycloakClientIdx = app(GlobalClientIdx::class)->get();
        }

        if (!$this->setKeycloakClientIdxData($keycloakClientIdx)) {
            throw new \Exception(__METHOD__ . ': Selected clientIdx not exists ("' . $keycloakClientIdx . '"');
        }
    }

    public static function create(?string $keycloakClientIdx = null, ?CacheInterface $cache = null)
    {
        if (is_null($cache)) {
            if (config('fz..default.keycloak.clientCacheType') === 'redis') {
                return new self(new RedisCache(), $keycloakClientIdx);
            }
            else {
                return new self(new MemcachedCache(), $keycloakClientIdx);
            }
        }
        else {
            return new self($cache, $keycloakClientIdx);
        }
    }

    public function getRealmAuthorizationCodeEndpointUrl(string $redirectUri = '') : ?string
    {
        try {
            if (!array_key_exists($this->authRealm, $this->openIdConfigurations) && !$this->loadOpenIdConfiguration($this->authRealm)) {
                throw new \Exception(__METHOD__ . ': Get realm config for realm "' . $this->authRealm . '" failed');
            }

            if (!in_array('authorization_code', $this->openIdConfigurations[$this->authRealm]['grant_types_supported'])) {
                throw new \Exception(__METHOD__ . ': Grant type "authorization_code" not supported for realm "' . $this->authRealm . '"');
            }

            $data = [
                'nonce' => uniqid(),
                'client_id' => $this->authClientId,
                'scope' => 'openid',
                'response_type' => 'code',
                'ui_locales' => str_replace('_', '-', App::currentLocale()),
                'redirect_uri' => !empty($redirectUri) ? $redirectUri : route('fz_authorization_code_callback'),
                'state' => $this->currentKeycloakClientIdx . '|' . hash('md5', config('app.key') . (string)$this->currentKeycloakClientIdx)
            ];

            $data = http_build_query($data);

            return $this->openIdConfigurations[$this->authRealm]['authorization_endpoint'] . '?' . $data;

        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $this->authRealm . '") Exception: ' . $e->getMessage());
            return null;
        }
    }

    public function getRealmEndSessioEndpointUrl(string $idTokenHint, string $redirectUri = '') : ?string
    {
        try {
            if (!array_key_exists($this->authRealm, $this->openIdConfigurations) && !$this->loadOpenIdConfiguration($this->authRealm)) {
                throw new \Exception(__METHOD__ . ': Get realm config for realm "' . $this->authRealm . '" failed');
            }

            $data = [
                'client_id' => $this->authClientId,
                'scope' => 'openid',
                'ui_locales' => str_replace('_', '-', App::currentLocale()),
                'post_logout_redirect_uri' => !empty($redirectUri) ? $redirectUri : route('fz_end_user_session_callback'),
                'state' => $this->currentKeycloakClientIdx . '|' . hash('md5', config('app.key') . (string)$this->currentKeycloakClientIdx)
            ];

            if (!empty($idTokenHint)) {
                $data['id_token_hint'] = $idTokenHint;
            }

            $data = http_build_query($data);

            return $this->openIdConfigurations[$this->authRealm]['end_session_endpoint'] . '?' . $data;

        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $this->authRealm . '") Exception: ' . $e->getMessage());
            return null;
        }
    }

    public function getCurrentKeycloakClientIdx() : ?string
    {
        return $this->currentKeycloakClientIdx;
    }

    public function getOldKeycloakClientIdx() : ?string
    {
        return $this->oldKeycloakClientIdx;
    }

    public function setKeycloakClientIdxData(?string $keycloakClientIdx = null) : bool
    {
        if (is_null($keycloakClientIdx)) {
            $keycloakClientIdx = config('fz.default.keycloak.clientIdx');
        }

        $clientIdxData = config('fz.keycloak.client.idxs')[$keycloakClientIdx] ?? [];

        if (!empty($clientIdxData)) {
            if (!is_null($this->currentKeycloakClientIdx)) {
                $this->oldKeycloakClientIdxData = [
                    'loginHostname' => $this->keycloakLoginHostname,
                    'authType' => $this->authType,
                    'realmName' => $this->authRealm,
                    'clientId' => $this->authClientId,
                    'clientSecret' => $this->authClientSecret,
                    'authPayload' => $this->authPayload,
                    'authAlg' => $this->authSignatureAlgorithm,
                    'pubKeyName' => $this->authPubKeyPath,
                    'pvtKeyName' => $this->authPvtKeyPath,
                    'certName' => $this->authCert
                ];
    
                $this->oldKeycloakClientIdx = $this->currentKeycloakClientIdx;
            }
        
            $this->keycloakLoginHostname = $clientIdxData['loginHostname'];
            $this->authType = $clientIdxData['authType'];
            $this->authRealm = $clientIdxData['realmName'];
            $this->authClientId = $clientIdxData['clientId'];
            $this->authClientSecret = $clientIdxData['clientSecret'];
            $this->authPayload = $clientIdxData['authPayload'];
            $this->authSignatureAlgorithm = $clientIdxData['authAlg'];
            $this->authPubKeyPath = storage_path('app/' . $clientIdxData['pubKeyName']);
            $this->authPvtKeyPath = storage_path('app/' . $clientIdxData['pvtKeyName']);
            $this->authCert = $clientIdxData['certName'];

            $this->currentKeycloakClientIdx = $keycloakClientIdx;

            return true;
        }
        else {
            Log::error(__METHOD__ . ': Keycloak client idx fz.keycloak.client[' . $keycloakClientIdx . '] not found');
            return false;
        }
    }

    public function restoreKeycloakClientIdxData() : ?string
    {
        if (!is_null($this->oldKeycloakClientIdx)) {
            $this->keycloakLoginHostname = $this->oldKeycloakClientIdxData['loginHostname'];
            $this->authType = $this->oldKeycloakClientIdxData['authType'];
            $this->authRealm = $this->oldKeycloakClientIdxData['realmName'];
            $this->authClientId = $this->oldKeycloakClientIdxData['clientId'];
            $this->authClientSecret = $this->oldKeycloakClientIdxData['clientSecret'];
            $this->authPayload = $this->oldKeycloakClientIdxData['authPayload'];
            $this->authSignatureAlgorithm = $this->oldKeycloakClientIdxData['authAlg'];
            $this->authPubKeyPath = $this->oldKeycloakClientIdxData['pubKeyName'];
            $this->authPvtKeyPath = $this->oldKeycloakClientIdxData['pvtKeyName'];
            $this->authCert = $this->oldKeycloakClientIdxData['certName'];

            $oldIdx = $this->oldKeycloakClientIdx;
            $this->currentKeycloakClientIdx = $this->oldKeycloakClientIdx;

            return $oldIdx;
        }  
        
        return null;
    }

    public function getCacheInterface() : CacheInterface
    {
        return $this->cache;
    }

    public function getOpenIdConfiguration(string $realm) : ?array
    {
        if (array_key_exists($realm, $this->openIdConfigurations)) {
            return $this->openIdConfigurations[$realm];
        }
        else {
            if ($this->loadOpenIdConfiguration($realm)) {
                return $this->openIdConfigurations[$realm];
            }
            else {
                return null;
            }
        }
    }

    public function loadRealmCert(string $realm) : ?RequestResult
    {
        if (!array_key_exists($realm, $this->openIdConfigurations) && !$this->loadOpenIdConfiguration($realm)) {
            Log::error(__METHOD__ . ': Get realm config for realm "' . $realm . '" failed');
        }
        else {
            $cacheKey = 'kc_' . strtolower($realm) . '_cert';

            if($this->cache->EXISTS($cacheKey)) {
                $data = $this->cache->GET($cacheKey);

                if (!is_null($data)) {
                    return new RequestResult(true, null, json_decode($data, true));
                }
                else {
                    Log::error(__METHOD__ . ': Read from cache "' . $cacheKey . '" failed');

                    $response = $this->doHttp2Request('GET', $this->openIdConfigurations[$realm]['jwks_uri']);

                    if ($response->getStatusCode() === 200) {
                        return new RequestResult(false, $response, json_decode((string) $response->getBody(), true));
                    }
                }
            }
            else {
                $response = $this->doHttp2Request('GET', $this->openIdConfigurations[$realm]['jwks_uri']);

                if ($response->getStatusCode() === 200) {
                    $this->cache->SET($cacheKey, (string) $response->getBody(), 36000);
                    return new RequestResult(false, $response, json_decode((string) $response->getBody(), true));
                }
            }

            return new RequestResult(false, $response, []);
        }

        return null;
    }

    protected function loadOpenIdConfiguration(string $realm) : bool
    {
        $cacheKey = 'kc_' . strtolower($realm) . '_openid_conf';

        if($this->cache->EXISTS($cacheKey)) {
            $data = $this->cache->GET($cacheKey);

            if (!is_null($data)) {
                $this->openIdConfigurations[$realm] = json_decode($data, true);
                return true;
            }
            else {
                Log::error(__METHOD__ . ': Read from cache "' . $cacheKey . '" failed');

                $response = $this->doHttp2Request('GET', $this->keycloakLoginHostname . '/realms/' . $realm . '/.well-known/openid-configuration');

                if ($response->getStatusCode() === 200) {
                    $this->openIdConfigurations[$realm] = json_decode((string) $response->getBody(), true);
                    return true;
                }
            }
        }
        else {
            $response = $this->doHttp2Request('GET', $this->keycloakLoginHostname . '/realms/' . $realm . '/.well-known/openid-configuration');

            if ($response->getStatusCode() === 200) {
                $this->cache->SET($cacheKey, (string) $response->getBody(), 36000);
                $this->openIdConfigurations[$realm] = json_decode((string) $response->getBody(), true);
                return true;
            }
        }

        return false;
    }

    public function readClientToken() : ?RequestResult
    {
        $cacheKey = 'kc_' . strtolower($this->authRealm . '_' . $this->authClientId) . '_client_auth';
        $lockCacheKeyToken = $cacheKey . '_token';

        if ($this->cache->LOCK($lockCacheKeyToken)) {
            $result = $this->loadClientJsonToken($cacheKey);

            $this->cache->UNLOCK($lockCacheKeyToken);
            return $result;
        }
        else {
            Log::error(__METHOD__ . ': ClientCache::LOCK failed ("' . $cacheKey . '")');
            return null;
        }
    }

    public function getClientToken() : ?array
    {
        $result = $this->readClientToken();

        if (!is_null($result)) {
            if ($result->fromCache) {
                return $result->json;
            }
            else {
                if ($result->rawResponse->getStatusCode() === 200) {
                    return $result->json;
                }
                else {
                    Log::error(__METHOD__ . ': Get token failed');
                }
            }
        }
        else {
            Log::error(__METHOD__ . ': Call readClientToken() failed');
        }

        return null;
    }
    
    public function getAccessTokenFromRequest(Request $request) : ?string
    {
        $parts = explode(' ', $request->header('Authorization'));

        if (count($parts) !== 2 || strtolower(trim($parts[0])) !== 'bearer') {
            return null;
        }
        else {
            return trim($parts[1]);
        }
    }

    public function userClientCredentialsAuth(string $username, string $password) : ?RequestResult
    {
        return $this->doClientAuth(['username' => $username, 'password' => $password, 'grant_type' => 'password', 'scope' => 'openid']);
    }

    public function userAuthorizationCodeAuth(string $authorizationCode, string $redirectUri, string $sessionState) : ?RequestResult
    {
        return $this->doClientAuth(['code' => $authorizationCode, 'redirect_uri' => $redirectUri, 'session_state' => $sessionState, 'grant_type' => 'authorization_code']);
    }

    public function setRequestCountMax(int $countMax)
    {
        if ($countMax <= 0) {
            Log::error(__METHOD__ . ': countMax must be greater than 0');
            $this->requestCountMax = 1;
        }
        else {
            $this->requestCountMax = $countMax;
        }
    }

    public function setRequestsDelayValue(int $msDelayValue)
    {
        if ($msDelayValue <= 0) {
            Log::error(__METHOD__ . ': msDelayValue must be greater than 0');
            $this->requestsMsDelayValue = 1000;
        }
        else {
            $this->requestsMsDelayValue = $msDelayValue;
        }
    }

    public function doHttpRequest(string $method, string $requestUrl, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $requestCount = 0;
        $requestDone = false;
        $response = null;

        //$options['debug'] = true;

        if (!isset($options['connect_timeout'])) {
            $options['connect_timeout'] = 30;
        }

        $options['handler'] = app(GuzzleClientHandlers::class)->getHandler($this->guzzleHandlerName, $this->guzzleHandlerClass, $options);

        do {
            try {
                $response = $this->httpClient->request($method, $requestUrl, $options); 

                $requestDone = true;
                Log::debug(__METHOD__ . ': HTTP Request OK: ' . $method . ' "' . $requestUrl . '" Status code = ' . $response->getStatusCode() . ' -- Status message = ' . $response->getReasonPhrase(), $options);
            } 
            catch (ConnectException $e) {
                Log::error(__METHOD__ . ': HTTP Request FAILED: ' . $method . ' "' . $requestUrl . '" (ConnectException)', ['options' => $options, 'exception' => $e]);

                $requestCount++;

                if ($requestCount === $this->requestCountMax) {
                    throw $e;
                }
                else {
                    usleep(($this->requestsMsDelayValue * $requestCount) * 1000);
                }
            }
            catch (BadResponseException $e) {
                $response = $e->getResponse();

                Log::error(__METHOD__ . ': HTTP Request FAILED: ' . $method . ' "' . $requestUrl . '" Status code = ' . $response->getStatusCode() . ' -- Status message = ' . $response->getReasonPhrase() . ' -- Body = ' . $response->getBody(), ['options' => $options, 'exception' => $e]);

                if ($response->getStatusCode() >= 400 && $response->getStatusCode() <= 499) {
                    $requestDone = true;
                }
                else {
                    $requestCount++;

                    if ($requestCount === $this->requestCountMax) {
                        $requestDone = true;
                    }
                    else {
                        usleep(($this->requestsMsDelayValue * $requestCount) * 1000);
                    }
                }
            }
            catch (\Throwable $e) {
                Log::error(__METHOD__ . ': HTTP Request FAILED: ' . $method . ' "' . $requestUrl . '" (Exception)', ['options' => $options, 'exception' => $e]);

                $requestCount++;

                if ($requestCount === $this->requestCountMax) {
                    throw $e;
                }
                else {
                    usleep(($this->requestsMsDelayValue * $requestCount) * 1000);
                }
            }
        } while(!$requestDone);

        return $response;
    }

    public function doHttp1Request(string $method, string $requestUrl, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $options['version'] = 1.1;
        return $this->doHttpRequest($method, $requestUrl, $options);   
    }

    public function doHttp2Request(string $method, string $requestUrl, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $options['version'] = 2.0;
        return $this->doHttpRequest($method, $requestUrl, $options); 
    }

    public function doRequestWithClientToken(string $method, string $requestUrl, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $cacheKey = 'kc_' . strtolower($this->authRealm . '_' . $this->authClientId) . '_client_auth';
        $lockCacheKeyToken = $cacheKey . '_token';

        if ($this->cache->LOCK($lockCacheKeyToken)) {
            $result = $this->loadClientJsonToken($cacheKey);

            if (!is_null($result)) {
                if ($result->fromCache) {
                    $jsonToken = $result->json;
                }
                else {
                    if ($result->rawResponse->getStatusCode() === 200) {
                        $jsonToken = $result->json;
                    }
                    else {
                        Log::error(__METHOD__ . ': Get token failed ("' . $requestUrl . '")');

                        $this->cache->UNLOCK($lockCacheKeyToken);
                        Log::debug(__METHOD__ . ': Return rawResponse "' . $cacheKey . '"');
                        return $result->rawResponse;
                    }
                }
            }
            else {
                Log::error(__METHOD__ . ': Call loadClientJsonToken() failed ("' . $requestUrl . '")');

                $this->cache->UNLOCK($lockCacheKeyToken);
                return new Response(511, [], 'Call loadClientJsonToken() failed ("' . $requestUrl . '")');
            }
        }
        else {
            Log::error(__METHOD__ . ': ClientCache::LOCK failed for loadClientJsonToken() ("' . $requestUrl . '")');
            return new Response(511, [], 'ClientCache::LOCK failed for loadClientJsonToken() ("' . $requestUrl . '")');
        }

        if ($jsonToken['token_type'] !== 'Bearer') {
            Log::error(__METHOD__ . ': Unsupported token type "' . $jsonToken['token_type']. '" (requested Bearer - "' . $requestUrl . '")');

            $this->cache->UNLOCK($lockCacheKeyToken);
            return new Response(511, [], 'Unsupported token type "' . $jsonToken['token_type']. '" (requested Bearer - "' . $requestUrl . '")');
        }
        else {
            $decoded = self::decodeAccessTokenPayload($jsonToken['access_token']);

            $rTime = $decoded['exp'] - time();

            Log::debug(__METHOD__ . ': rTime = "' . $rTime . '" (before request)');

            if ($rTime <= 0) { //Scaduto
                Log::debug(__METHOD__ . ': Token expired (before request)');

                do
                {
                    $done = true;

                    if($this->cache->EXISTS($cacheKey)) {
                        Log::debug(__METHOD__ . ': Key cache entry exists for key "' . $cacheKey . '" (before request)');
                        $done = $this->cache->DEL($cacheKey);
                    }

                    if (!$done) {
                        usleep(100000);
                    }

                } while (!$done);

                if ($done) {
                    Log::debug(__METHOD__ . ': DEL done for key "' . $cacheKey . '"');
                }
                else {
                    Log::debug(__METHOD__ . ': DEL failed for key "' . $cacheKey . '"');
                }
                
                if (array_key_exists('refresh_token', $jsonToken)) {
                    Log::debug(__METHOD__ . ': Try refresh_token for key "' . $cacheKey . '" (before_request)');

                    $result = $this->getRefreshToken($cacheKey, $jsonToken);

                    if (!is_null($result)) {
                        if ($result->fromCache) {
                            $jsonToken = $result->json;
                        }
                        else {
                            if ($result->rawResponse->getStatusCode() === 200) {
                                $jsonToken = $result->json;
                            }
                            else {
                                Log::error(__METHOD__ . ': Token expired renew failed (before request - "' . $requestUrl . '")');

                                $this->cache->UNLOCK($lockCacheKeyToken);
                                Log::debug(__METHOD__ . ': Return rawResponse "' . $cacheKey . '"');
                                return $result->rawResponse;
                            }
                        }
                    }
                    else {
                        Log::error(__METHOD__ . ': Call getRefreshToken() failed (before request - "' . $requestUrl . '")');

                        $this->cache->UNLOCK($lockCacheKeyToken);
                        return new Response(511, [], 'Call getRefreshToken() failed (before request - "' . $requestUrl . '")');
                    }
                    
                    $this->cache->UNLOCK($lockCacheKeyToken);
                    $tokenIsLocked = false;
                }
                else {
                    $this->cache->UNLOCK($lockCacheKeyToken);
                    Log::debug(__METHOD__ . ': No "refresh_token" into JWT token (before request - "' . $requestUrl . '")', $jsonToken);
                    return $this->doRequestWithClientToken($method, $requestUrl, $options);
                }
            }
            else if ($rTime >= 15) {
                $this->cache->UNLOCK($lockCacheKeyToken);
                $tokenIsLocked = false;
            }
            else {
                $tokenIsLocked = true;
            }

	        $decoded = self::decodeAccessTokenPayload($jsonToken['access_token']);

            Log::debug(__METHOD__ . ': AccessToken utilizzato per la richiesta (time corrente ' . time() . ')', $decoded);

            $response = $this->doRequestWithAccessToken($jsonToken['access_token'], $method, $requestUrl, $options);

            if ($response->getStatusCode() === 403) {
                Log::debug(__METHOD__ . ': Token expired (after request)');

                if ($tokenIsLocked) {
                    Log::debug(__METHOD__ . ': Token is locked (after request)');

                    do
                    {
                        $done = true;

                        if($this->cache->EXISTS($cacheKey)) {
                            Log::debug(__METHOD__ . ': Key cache entry exists for key "' . $cacheKey . '" (after request)');
                            $done = $this->cache->DEL($cacheKey);
                        }

                        if (!$done) {
                            usleep(100000);
                        }

                    } while (!$done);

                    if ($done) {
                        Log::debug(__METHOD__ . ': DEL done for key "' . $cacheKey . '"');
                    }
                    else {
                        Log::debug(__METHOD__ . ': DEL failed for key "' . $cacheKey . '"');
                    }
                }
                else {
                    Log::debug(__METHOD__ . ': Token is not locked (after request)');
                }

                if (array_key_exists('refresh_token', $jsonToken)) {
                    Log::debug(__METHOD__ . ': Try refresh_token for key "' . $cacheKey . '" (after request)');

                    if ($tokenIsLocked) {
                        $result = $this->getRefreshToken($cacheKey, $jsonToken);

                        if (!is_null($result)) {
                            if ($result->fromCache) {
                                $this->cache->UNLOCK($lockCacheKeyToken);
                                return $this->doRequestWithClientToken($method, $requestUrl, $options);
                            }
                            else {
                                if ($result->rawResponse->getStatusCode() === 200) {
                                    $this->cache->UNLOCK($lockCacheKeyToken);
                                    return $this->doRequestWithClientToken($method, $requestUrl, $options);
                                }
                                else {
                                    Log::error(__METHOD__ . ': Token expired renew failed (after request - "' . $requestUrl . '")');

                                    $this->cache->UNLOCK($lockCacheKeyToken);
                                    Log::debug(__METHOD__ . ': Return rawResponse "' . $cacheKey . '"');
                                    return $result->rawResponse;
                                }
                            }
                        }
                        else {
                            Log::error(__METHOD__ . ': Call getRefreshToken() failed (after request - "' . $requestUrl . '")');

                            $this->cache->UNLOCK($lockCacheKeyToken);
                        }
                    }
                    else {
                        $lockCacheKeyRefresh = $cacheKey . '_refresh';

                        Log::debug(__METHOD__ . ': Try lock before call getRefreshToken() (token is unlocked)');

                        if ($this->cache->LOCK($lockCacheKeyRefresh)) {
                            $result = $this->getRefreshToken($cacheKey, $jsonToken);

                            if (!is_null($result)) {
                                if ($result->fromCache) {
                                    $this->cache->UNLOCK($lockCacheKeyRefresh);
                                    return $this->doRequestWithClientToken($method, $requestUrl, $options);
                                }
                                else {
                                    if ($result->rawResponse->getStatusCode() === 200) {
                                        $this->cache->UNLOCK($lockCacheKeyRefresh);
                                        return $this->doRequestWithClientToken($method, $requestUrl, $options);
                                    }
                                    else {
                                        Log::error(__METHOD__ . ': Token expired renew failed (after request - "' . $requestUrl . '")');

                                        $this->cache->UNLOCK($lockCacheKeyRefresh);
                                        Log::debug(__METHOD__ . ': Return rawResponse "' . $cacheKey . '"');
                                        return $result->rawResponse;
                                    }
                                }
                            }
                            else {
                                Log::error(__METHOD__ . ': Call getRefreshToken() failed (after request - "' . $requestUrl . '")');

                                $this->cache->UNLOCK($lockCacheKeyRefresh);
                            }
                        }
                        else {
                            Log::error(__METHOD__ . ': ClientCache::LOCK failed for getRefreshToken() ("' . $requestUrl . '")');
                        }
                    }
                }
                else {
                    Log::debug(__METHOD__ . ': No "refresh_token" into JWT token (after request - "' . $requestUrl . '")', $jsonToken);
                }
            }
            
            if ($tokenIsLocked) {
                $this->cache->UNLOCK($lockCacheKeyToken);
            }

            return $response;
        }
    }

    public function doRequestWithUserToken(array &$jsonToken, string $method, string $requestUrl, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        if ($jsonToken['token_type'] !== 'Bearer') {
            Log::error(__METHOD__ . ': Unsupported token type "' . $jsonToken['token_type'] . '" (requested Bearer)');
            return new Response(511, [], 'Unsupported token type "' . $jsonToken['token_type'] . '" (requested Bearer)');
        }
        else {
            $response = $this->doRequestWithAccessToken($jsonToken['access_token'], $method, $requestUrl, $options);

            if ($response->getStatusCode() === 403 && $this->userTokenIsExpired($jsonToken)) {
                if (array_key_exists('refresh_token', $jsonToken)) {
                    $cacheKey = 'kc_' . hash('md5', $jsonToken['access_token']) . '_user_auth';
                    $lockCacheKeyRefresh = $cacheKey . '_refresh';
    
                    if ($this->cache->LOCK($lockCacheKeyRefresh)) {
                        $result = $this->refreshUserToken($jsonToken);
    
                        if (!is_null($result)) {
                            if ($result->fromCache) {
                                $jsonToken = $result->json;
                                $this->cache->UNLOCK($lockCacheKeyRefresh);
                                $response = $this->doRequestWithUserToken($jsonToken, $method, $requestUrl, $options);
                            }
                            else {
                                if ($result->rawResponse->getStatusCode() === 200) {
                                    $jsonToken = $result->json;
                                    $this->cache->UNLOCK($lockCacheKeyRefresh);
                                    $response = $this->doRequestWithUserToken($jsonToken, $method, $requestUrl, $options);
                                }
                                else {
                                    Log::error(__METHOD__ . ': Token expired renew failed (after request - "' . $requestUrl . '")');

                                    $this->cache->UNLOCK($lockCacheKeyRefresh);
                                    Log::debug(__METHOD__ . ': Return rawResponse "' . $cacheKey . '"');
                                    return $result->rawResponse;
                                }
                            }
                        }
                        else {
                            Log::error(__METHOD__ . ': Call getRefreshToken() failed ("' . $requestUrl . '")');
    
                            $this->cache->UNLOCK($lockCacheKeyRefresh);
                        }
                    }
                    else {
                        Log::error(__METHOD__ . ': ClientCache::LOCK failed for getRefreshToken() ("' . $requestUrl . '")');
                    }
                }
                else {
                    Log::debug(__METHOD__ . ': No "refresh_token" into JWT token ("' . $requestUrl . '")', $jsonToken);
                }
            }

            return $response;
        }
    }

    public function doRequestWithAccessToken(string $accessToken, string $method, string $requestUrl, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $options['headers']['authorization'] = 'Bearer ' . $accessToken;

        return $this->doHttp2Request($method, $requestUrl, $options);
    }

    public function userTokenIsExpired(array $jsonToken) : bool
    {
        $decoded = self::decodeAccessTokenPayload($jsonToken['access_token']);

        return ($decoded['exp'] - time()) <= 0;
    }

    public function refreshUserToken(array $jsonToken) : ?RequestResult
    {
        return $this->getRefreshToken(null, $jsonToken);
    }

    public function proxyKeyCloakAdminRequest(Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $idpUri = $request->header('X-Idp-Uri');

        if (empty($idpUri)) {
            Log::error(__METHOD__ . ': IdpUri is empty');
            throw new \Exception(__METHOD__ . ': IdpUri is empty for realm "' . $this->authRealm . '"');
        }
        else if (!str_starts_with($idpUri, '/admin/realms')) {
            Log::error(__METHOD__ . ': Invalid IdpUri "' . $idpUri . '"');
            throw new \Exception(__METHOD__ . ': Invalid IdpUri for realm "' . $this->authRealm . '" (IdpUri = "' . substr($idpUri, 0, 20) . '..."');
        }
        else {
            $accessToken = $this->getAccessTokenFromRequest($request);

            if (!is_null($accessToken)) {
                $baseOptions = [
                    'base_uri' => $this->keycloakLoginHostname,
                    'query' => $request->query(),
                    'body' => $request->getContent(false),
                ];

                $baseOptions['headers'] = $request->header();

                if ($request->hasHeader('authorization')) {
                    unset($baseOptions['headers']['authorization']);
                }

                $options = array_merge($baseOptions, $options);

                return $this->doRequestWithAccessToken($accessToken, $request->method(), $idpUri, $options);
            }
            else {
                throw new \Exception(__METHOD__ . ': access_token not found into request for idp-uri"' . $idpUri . '"');
            }
        }
    }

    // ---

    public static function decodeAccessToken(string $accessToken) : array
    {
        list($headersB64, $payloadB64, $sig) = explode('.', $accessToken);
        
        return [
            'header' => json_decode(base64_decode($headersB64), true),
            'payload' => json_decode(base64_decode($payloadB64), true),
            'sig' => $sig
        ];
    }

    public static function decodeAccessTokenPayload(string $accessTokenB64) : array
    {
        list($headersB64, $payloadB64, $sig) = explode('.', $accessTokenB64);
        return json_decode(base64_decode($payloadB64), true);
    }

    // ---

    protected function doClientAuth(array $extraData = []) : ?RequestResult
    {
        switch ($this->authType) {
            case 'ClientSecret':
                return $this->doClientSecretAuth($extraData, $this->authRealm, $this->authClientId, $this->authClientSecret);

            case 'SignedJwt':
                return $this->doClientSignedJwtAuth($extraData, $this->authRealm, $this->authClientId, $this->authPvtKeyPath, $this->authPayload, $this->authSignatureAlgorithm);

            case 'SignedJwtClientSecret':
                return $this->doSignedJwtClientSecretAuth($extraData, $this->authRealm, $this->authClientId, $this->authClientSecret, $this->authPayload, $this->authSignatureAlgorithm);

            default:
                Log::error(__METHOD__ . 'Unsupported authType "' . $this->authType . '"');
                return null;
        }
    }

    protected function doClientSecretAuth(array $extraData, string $realm, string $clientId, string $clientSecret) : ?RequestResult
    {
        try
        {
            foreach (['realm', 'clientId', 'clientSecret'] as $param) {
                if (empty($$param)) {
                    throw new \Exception(__METHOD__ . ': Param "' . $param . '" cannot be empty, check auth params');
                }
            }

            if (!array_key_exists($realm, $this->openIdConfigurations) && !$this->loadOpenIdConfiguration($realm)) {
                throw new \Exception(__METHOD__ . ': Get realm config for realm "' . $realm . '" failed');
            }
    
            if (!in_array('client_credentials', $this->openIdConfigurations[$realm]['grant_types_supported'])) {
                throw new \Exception(__METHOD__ . ': Grant type "client_credentials" not supported for realm "' . $realm . '"');
            }

            $data = [
                'scope' => 'openid',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials'
            ];

            if (!empty($extraData)) {
                $data = array_merge($data, $extraData);
            }

            if ($data['grant_type'] !== 'client_credentials') {
                if (!in_array($data['grant_type'], $this->openIdConfigurations[$realm]['grant_types_supported'])) {
                    throw new \Exception(__METHOD__ . ': Grant type "' . $data['grant_type'] . '" not supported for realm "' . $realm . '"');
                }
            }

            $body = http_build_query($data);
    
            $response = $this->doHttp2Request('POST', $this->openIdConfigurations[$realm]['token_endpoint'], [
                'body' => $body,
                'headers' => [
                    'ACCEPT' => '*/*',
                    'CONTENT-TYPE' => 'application/x-www-form-urlencoded'
                ]
            ]);
    
            if ($response->getStatusCode() === 200) {
                return new RequestResult(false, $response, json_decode((string) $response->getBody(), true));
            }
    
            return new RequestResult(false, $response, []);
        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $realm . '") Exception: ' . $e->getMessage());
            return null;
        }
    }

    protected function doClientSignedJwtAuth(array $extraData, string $realm, string $clientId, string $pvtKeyPath, array $payload = [], string $signatureAlgorithm = 'RS256') : ?RequestResult
    {
        try
        {
            foreach (['realm', 'clientId', 'pvtKeyPath', 'signatureAlgorithm'] as $param) {
                if (empty($$param)) {
                    throw new \Exception(__METHOD__ . ': Param "' . $param . '" cannot be empty, check auth params');
                }
            }

            if (!array_key_exists($realm, $this->openIdConfigurations) && !$this->loadOpenIdConfiguration($realm)) {
                throw new \Exception(__METHOD__ . ': Get realm config for realm "' . $realm . '" failed');
            }
    
            if (!in_array('client_credentials', $this->openIdConfigurations[$realm]['grant_types_supported'])) {
                throw new \Exception(__METHOD__ . ': Grant type "client_credentials" not supported for realm "' . $realm . '"');
            }

            $iat = time();
            $exp = $iat + 300;
    
            //### https://www.iana.org/assignments/jwt/jwt.xhtml
    
            $payload = array_merge([
                'iss' => $clientId,
                'sub' => $clientId,
                'jti' => uniqid(),
                'nbf' => 0,
                'aud' => $this->keycloakLoginHostname . '/realms/' . $realm,
                'iat' => $iat,
                'exp' => $exp
            ], $payload);
    
            $jwt = JWT::encode($payload, file_get_contents($pvtKeyPath), $signatureAlgorithm);
    
            $data = [
                'scope' => 'openid',
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                'client_assertion' => $jwt,
                'grant_type' => 'client_credentials',
            ];

            if (!empty($extraData)) {
                $data = array_merge($data, $extraData);
            }

            if ($data['grant_type'] !== 'client_credentials') {
                if (!in_array($data['grant_type'], $this->openIdConfigurations[$realm]['grant_types_supported'])) {
                    throw new \Exception(__METHOD__ . ': Grant type "' . $data['grant_type'] . '" not supported for realm "' . $realm . '"');
                }
            }
    
            $body = http_build_query($data);
    
            $response = $this->doHttp2Request('POST', $this->openIdConfigurations[$realm]['token_endpoint'], [
                'body' => $body,
                'headers' => [
                    'Accept' => '*/*',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ]);
    
            if ($response->getStatusCode() === 200) {
                return new RequestResult(false, $response, json_decode((string) $response->getBody(), true));
            }
    
            return new RequestResult(false, $response, []);
        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $realm . '") Exception: ' . $e->getMessage());
            return null;
        }
    }

    protected function doSignedJwtClientSecretAuth(array $extraData, string $realm, string $clientId, string $clientSecret, array $payload = [], string $signatureAlgorithm = 'HS256') : ?RequestResult
    {
        try
        {
            foreach (['realm', 'clientId', 'clientSecret', 'signatureAlgorithm'] as $param) {
                if (empty($$param)) {
                    throw new \Exception(__METHOD__ . ': Param "' . $param . '" cannot be empty, check auth params');
                }
            }

            if (!array_key_exists($realm, $this->openIdConfigurations) && !$this->loadOpenIdConfiguration($realm)) {
                throw new \Exception(__METHOD__ . ': Get realm config for realm "' . $realm . '" failed');
            }
    
            if (!in_array('client_credentials', $this->openIdConfigurations[$realm]['grant_types_supported'])) {
                throw new \Exception(__METHOD__ . ': Grant type "client_credentials" not supported for realm "' . $realm . '"');
            }

            $iat = time();
            $exp = $iat + 300;
    
            //### https://www.iana.org/assignments/jwt/jwt.xhtml
    
            $payload = array_merge([
                'iss' => $clientId,
                'sub' => $clientId,
                'jti' => uniqid(),
                'nbf' => 0,
                'aud' => $this->keycloakLoginHostname . '/realms/' . $realm,
                'iat' => $iat,
                'exp' => $exp
            ], $payload);
    
            $jwt = JWT::encode($payload, $clientSecret, $signatureAlgorithm);
    
            $data = [
                'scope' => 'openid',
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                'client_assertion' => $jwt,
                'grant_type' => 'client_credentials',
            ];

            if (!empty($extraData)) {
                $data = array_merge($data, $extraData);
            }

            if ($data['grant_type'] !== 'client_credentials') {
                if (!in_array($data['grant_type'], $this->openIdConfigurations[$realm]['grant_types_supported'])) {
                    throw new \Exception(__METHOD__ . ': Grant type "' . $data['grant_type'] . '" not supported for realm "' . $realm . '"');
                }
            }
    
            $body = http_build_query($data);
    
            $response = $this->doHttp2Request('POST', $this->openIdConfigurations[$realm]['token_endpoint'], [
                'body' => $body,
                'headers' => [
                    'Accept' => '*/*',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ]);
    
            if ($response->getStatusCode() === 200) {
                return new RequestResult(false, $response, json_decode((string) $response->getBody(), true));
            }
    
            return new RequestResult(false, $response, []);
        }catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $realm . '") Exception: ' . $e->getMessage());
            return null;
        }
    }

    protected function doTokenRefresh(array $jsonToken) : ?RequestResult
    {
        try
        {
            if (!array_key_exists($this->authRealm, $this->openIdConfigurations) && !$this->loadOpenIdConfiguration($this->authRealm)) {
                throw new \Exception(__METHOD__ . ': Get realm config for realm "' . $this->authRealm . '" failed');
            }
    
            if (!in_array('refresh_token', $this->openIdConfigurations[$this->authRealm]['grant_types_supported'])) {
                throw new \Exception(__METHOD__ . ': Grant type "client_credentials" not supported for realm "' . $this->authRealm . '"');
            }

            $decoded = self::decodeAccessTokenPayload($jsonToken['access_token']);

            if (array_key_exists('client_id', $decoded)) {
                if ($this->authType === 'ClientSecret') {
                    $data = [
                        'scope' => 'openid',
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
                        'iat' => $decoded['iat'],//$iat,
                        'exp' => $exp
                    ];
            
                    if ($this->authType === 'SignedJwt') {
                        $jwt = JWT::encode($payload, file_get_contents($this->authPvtKeyPath), $this->authSignatureAlgorithm);
                    }
                    else { // SignedJwtClientSecret
                        $jwt = JWT::encode($payload, $this->authClientSecret, $this->authSignatureAlgorithm);
                    }
            
                    $data = [
                        'scope' => 'openid',
                        'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                        'client_assertion' => $jwt,
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $jsonToken['refresh_token']
                    ];
                }
            }
            else { // user
                $data = [
                    'scope' => 'openid',
                    'client_id' => $this->authClientId,
                    'client_secret' => $this->authClientSecret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $jsonToken['refresh_token']
                ];
            }

            $body = http_build_query($data);

            $response = $this->doHttp2Request('POST', $this->openIdConfigurations[$this->authRealm]['token_endpoint'], [
                'body' => $body,
                'headers' => [
                    'Accept' => '*/*',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ]);
    
            if ($response->getStatusCode() === 200) {
                return new RequestResult(false, $response, json_decode((string) $response->getBody(), true));
            }
    
            return new RequestResult(false, $response, []);
        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ' (realm = "' . $this->authRealm . '") Exception: ' . $e->getMessage());
            return null;
        }
    }

    // ---

    private function loadClientJsonToken(string $cacheKey) : ?RequestResult
    {
        Log::debug(__METHOD__ . ': Requested token "' . $cacheKey . '"');

        if($this->cache->EXISTS($cacheKey)) {
            Log::debug(__METHOD__ . ': Token exists into cache with key "' . $cacheKey . '"');
            $data = $this->cache->GET($cacheKey);

            if (!is_null($data)) {
                Log::debug(__METHOD__ . ': GET done from cache for key "' . $cacheKey . '"');
                return new RequestResult(true, null, json_decode($data, true));
            }
            else {
                Log::debug(__METHOD__ . ': GET fail from cache for key "' . $cacheKey . '"');
                Log::error(__METHOD__ . ': Read "' . $cacheKey . '" from cache failed');
            }
        }
        else {
            Log::debug(__METHOD__ . ': Token not exists into cache with key "' . $cacheKey . '"');
        }

        $authResponse = $this->doClientAuth();

        if (!is_null($authResponse) && $authResponse->rawResponse->getStatusCode() === 200) {
            Log::debug(__METHOD__ . ': Save new token into cache with key "' . $cacheKey . '"');

            do
            {
                $done = $this->cache->SET($cacheKey, json_encode($authResponse->json, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR), $authResponse->json['expires_in']);

                if (!$done) {
                    usleep(100000);
                }

            } while (!$done);

            if ($done) {
                Log::debug(__METHOD__ . ': SET done from cache for key "' . $cacheKey . '"');
            }
            else {
                Log::debug(__METHOD__ . ': SET done from cache for key "' . $cacheKey . '"');
            }
        }
        else {
            if (is_null($authResponse)) {
                Log::debug(__METHOD__ . ': Auth is null, new token NOT saved into cache with key "' . $cacheKey . '"');
            }
            else {
                Log::debug(__METHOD__ . ': Auth response != 200, new token NOT saved into cache with key "' . $cacheKey . '"', ['statusCode' => $authResponse->rawResponse->getStatusCode()]);
            }
        }

        return $authResponse;
    }

    private function getRefreshToken(?string $cacheKey, array $oldJsonToken) : ?RequestResult
    {
        Log::debug(__METHOD__ . ': Requested refresh token "' . $cacheKey . '"', self::decodeAccessToken($oldJsonToken['access_token']));

        $decoded = self::decodeAccessTokenPayload($oldJsonToken['access_token']);

        $rTime = $decoded['exp'] - time();

        if ($rTime > 0) { //Non Scaduto
            Log::debug(__METHOD__ . ': Requested refersh_token but old token is not expired "' . $cacheKey . '"');
            return new RequestResult(true, null, $oldJsonToken);
        }

        $authResponse = $this->doTokenRefresh($oldJsonToken);

        if (!is_null($authResponse) && $authResponse->rawResponse->getStatusCode() === 200) {
            if (!is_null($cacheKey)) {
                Log::debug(__METHOD__ . ': Save fresh token into cache for key "' . $cacheKey . '"');

                do
                {
                    $done = $this->cache->SET($cacheKey, json_encode($authResponse->json, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR), $authResponse->json['expires_in']);

                    if (!$done) {
                        usleep(100000);
                    }

                } while (!$done);

                if ($done) {
                    Log::debug(__METHOD__ . ': SET done from cache for key "' . $cacheKey . '"');
                }
                else {
                    Log::debug(__METHOD__ . ': SET done from cache for key "' . $cacheKey . '"');
                }
            }
            else {
                Log::debug(__METHOD__ . ': CacheKey is null, cache not required');
            }
        }
        else {
            if (is_null($authResponse)) {
                Log::debug(__METHOD__ . ': Auth is null, new token NOT saved into cache for key "' . $cacheKey . '"');
            }
            else {
                Log::debug(__METHOD__ . ': Auth response != 200, new token NOT saved into cache for key "' . $cacheKey . '"', ['statusCode' => $authResponse->rawResponse->getStatusCode()]);
            }
        }

        return $authResponse;
    }
}
