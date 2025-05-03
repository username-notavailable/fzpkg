<?php

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\Middleware;

use Closure;
use App\Http\Classes\ApiErrorResponse;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Client;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
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

class CheckClientBearerTokenType
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $header = $request->header('Authorization', '');

            if (empty($header)) {
                return new ApiErrorResponse('Invalid authorization header', [], 412);
            }
            else {
                $parts = explode(' ', $header);

                if (count($parts) !== 2) {
                    return new ApiErrorResponse('Invalid authorization header', [], 412);
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
                                return new ApiErrorResponse('Load realm cert failed', [], 500);
                            }
                            else {
                                $redis->executeRaw(['SET', $cacheKey, json_encode($result->json, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR), 'EX', 31536000]);
                                RedisLock::unlock($redis, $cacheKey);
                                $jwks = $result->json;
                            }
                        }
                        else {
                            $jwks = json_decode($redis->executeRaw(['GET', $cacheKey]), JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
                            RedisLock::unlock($redis, $cacheKey);
                        }
    
                        $decoded = JWT::decode($jsonToken, JWK::parseKeySet($jwks));
                        $decoded = json_decode(json_encode($decoded), true); // StdClass2Array

                        if (!array_key_exists('client_id', $decoded)) {
                            return new ApiErrorResponse('Invalid token type (Required client token type, "client_id" not found)', [], 412);
                        }
                    }
                    else {
                        return new ApiErrorResponse('Set cache lock failed', [], 500);
                    }

                    return $next($request);
                }
            }
    
        } catch (InvalidArgumentException $e) {
            // provided key/key-array is empty or malformed.
            return new ApiErrorResponse('Provided key/key-array is empty or malformed', [], 412);
        } catch (DomainException $e) {
            // provided algorithm is unsupported OR
            // provided key is invalid OR
            // unknown error thrown in openSSL or libsodium OR
            // libsodium is required but not available.
            return new ApiErrorResponse('Provided algorithm is unsupported OR provided key is invalid OR SSL error', [], 412);
        } catch (SignatureInvalidException $e) {
            // provided JWT signature verification failed.
            return new ApiErrorResponse('Provided JWT signature verification failed', [], 401);
        } catch (BeforeValidException $e) {
            // provided JWT is trying to be used before "nbf" claim OR
            // provided JWT is trying to be used before "iat" claim.
            return new ApiErrorResponse('Provided JWT is trying to be used before "nbf" claim OR provided JWT is trying to be used before "iat" claim', [], 412);
        } catch (ExpiredException $e) {
            // provided JWT is trying to be used after "exp" claim.
            return new ApiErrorResponse('Provided JWT is trying to be used after "exp" claim', [], 403);
        } catch (UnexpectedValueException $e) {
            // provided JWT is malformed OR
            // provided JWT is missing an algorithm / using an unsupported algorithm OR
            // provided JWT algorithm does not match provided key OR
            // provided key ID in key/key-array is empty or invalid.
            return new ApiErrorResponse('Provided JWT is malformed OR Provided JWT is missing an algorithm / using an unsupported algorithm OR provided JWT algorithm does not match provided key OR provided key ID in key/key-array is empty or invalid', [], 412);
        } catch(\Throwable $e) {
            Log::error(__METHOD__. ': ' . $e->getMessage() . ' - FILE: ' . $e->getFile() . ' - LINE: ' . $e->getLine());
            return new ApiErrorResponse('Elaboration error', [], 500);
        }
    }
}
