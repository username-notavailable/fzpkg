<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes;

use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Client;
use Illuminate\Http\Request;
use Firebase\JWT\{JWT, JWK};
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;
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

                    $kcClient = Client::create();

                    $result = $kcClient->loadRealmCert($kcClient->authRealm);
    
                    if (is_null($result) || (!$result->fromCache && $result->rawResponse->getStatusCode() !== 200)) {
                        return ['code' => 500, 'reason' => 'Load realm certificate failed'];
                    }
                    else {
                        $jwks = $result->json;
                    }

                    return ['code' => 200, 'decoded' => JWT::decode($jsonToken, JWK::parseKeySet($jwks))];
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
            return ['code' => 500, 'reason' => 'Excetion error'];
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
                return ['code' => 200, 'decoded' => $decoded];
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

            if (array_key_exists('client_id', $decoded)) {
                return ['code' => 412, 'reason' => 'Invalid token type (Required user token type, "client_id" found)'];
            }
            else {
                return ['code' => 200, 'decoded' => $decoded];
            }
        }
    }
}