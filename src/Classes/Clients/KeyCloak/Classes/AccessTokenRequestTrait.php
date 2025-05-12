<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes;

use Closure;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Client;
use Illuminate\Http\Request;
use Firebase\JWT\{JWT, JWK};
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;
use Illuminate\Support\Facades\Redis;
use Fuzzy\Fzpkg\Classes\Utils\Redis\RedisLock;
use Illuminate\Support\Facades\Log;

trait AccessTokenRequestTrait
{
    public function readAccessToken(Request $request) : array
    {
        try {
            $header = $request->header('Authorization', '');

            if (empty($header)) {
                return ['code' => 412, 'reason' => 'Invalid authorization header'];
            }
            else {
                $parts = explode(' ', $header);

                if (count($parts) !== 2) {
                    return ['code' => 412, 'reason' => 'Invalid authorization header'];
                }
                else {
                    $jsonToken = trim($parts[1]);

                    $client = new Client();

                    $redis = Redis::connection();

                    $cacheKey = 'kc_realm_' . strtolower($client->authRealm) . '_cert';

                    if (RedisLock::lock($redis, $cacheKey)) {
                        if($redis->executeRaw(['EXISTS', $cacheKey]) !== 1) {
                            $result = $client->loadRealmCert($client->authRealm);
    
                            if (!$result || (!$result->fromCache && $result->rawResponse->getStatusCode() !== 200)) {
                                RedisLock::unlock($redis, $cacheKey);
                                
                                Log::error(__METHOD__ . ': Load realm cert failed');
                                return ['code' => 500, 'reason' => 'Internal error'];
                            }
                            else {
                                $redis->executeRaw(['SET', $cacheKey, json_encode($result->json, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR), 'EX', 31536000]);
                                RedisLock::unlock($redis, $cacheKey);
                                $jwks = $result->json;
                            }
                        }
                        else {
                            $jwks = json_decode($redis->executeRaw(['GET', $cacheKey]), true);
                            RedisLock::unlock($redis, $cacheKey);
                        }
    
                        return ['code' => 200, 'decoded' => JWT::decode($jsonToken, JWK::parseKeySet($jwks))];
                    }
                    else {
                        Log::error(__METHOD__ . ': Set cache lock failed');
                        return ['code' => 500, 'reason' => 'Internal error'];
                    }
                }
            }
    
        } catch (InvalidArgumentException $e) {
            // provided key/key-array is empty or malformed.
            return ['code' => 422, 'reason' => 'Provided key/key-array is empty or malformed'];
        } catch (DomainException $e) {
            // provided algorithm is unsupported OR
            // provided key is invalid OR
            // unknown error thrown in openSSL or libsodium OR
            // libsodium is required but not available.
            return ['code' => 422, 'reason' => 'Provided algorithm is unsupported OR provided key is invalid OR SSL error'];
        } catch (SignatureInvalidException $e) {
            // provided JWT signature verification failed.
            return ['code' => 401, 'reason' => 'Provided JWT signature verification failed'];
        } catch (BeforeValidException $e) {
            // provided JWT is trying to be used before "nbf" claim OR
            // provided JWT is trying to be used before "iat" claim.
            return ['code' => 422, 'reason' => 'Provided JWT is trying to be used before "nbf" claim OR provided JWT is trying to be used before "iat" claim'];
        } catch (ExpiredException $e) {
            // provided JWT is trying to be used after "exp" claim.
            return ['code' => 403, 'reason' => 'Provided JWT is trying to be used after "exp" claim'];
        } catch (UnexpectedValueException $e) {
            // provided JWT is malformed OR
            // provided JWT is missing an algorithm / using an unsupported algorithm OR
            // provided JWT algorithm does not match provided key OR
            // provided key ID in key/key-array is empty or invalid.
            return ['code' => 422, 'reason' => 'Provided JWT is malformed OR Provided JWT is missing an algorithm / using an unsupported algorithm OR provided JWT algorithm does not match provided key OR provided key ID in key/key-array is empty or invalid'];
        } catch(\Throwable $e) {
            Log::error(__METHOD__ . ': ' . $e->getMessage() . ' - FILE: ' . $e->getFile() . ' - LINE: ' . $e->getLine());
            return ['code' => 500, 'reason' => 'Internal error'];
        }
    }

    public function readClientAccessToken(Request $request) : array
    {
        $result = $this->readAccessToken($request);

        if ($result['code'] !== 200) {
            return $result;
        }
        else {
            $decoded = json_decode(json_encode($result['decoded']), true); // StdClass2Array

            if (!array_key_exists('client_id', $decoded)) {
                return ['code' => 412, 'reason' => 'Invalid token type (Required client token type, "client_id" not found)'];
            }
            else {
                return $decoded;
            }
        }
    }

    public function readUserAccessToken(Request $request) : array
    {
        $result = $this->readAccessToken($request);

        if ($result['code'] !== 200) {
            return $result;
        }
        else {
            $decoded = json_decode(json_encode($result['decoded']), true); // StdClass2Array

            if (!array_key_exists('preferred_username', $decoded)) {
                return ['code' => 412, 'reason' => 'Invalid token type (Required user token type, "preferred_username" not found)'];
            }
            else {
                return $decoded;
            }
        }
    }
}