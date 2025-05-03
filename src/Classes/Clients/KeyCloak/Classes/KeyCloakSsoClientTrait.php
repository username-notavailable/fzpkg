<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes;

use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Client;
use Illuminate\Http\Request;
use Nyholm\Psr7\Response;
use Illuminate\Support\Facades\Log;

trait KeyCloakSsoClientTrait
{
    protected function __WithClientToken(Client $kcClient, string $hostname, string $uri, Request $request, bool $copyHeaders = true, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $baseOptions = [
            'base_uri' => $hostname,
            'query' => $request->query(),
            'body' => $request->getContent(false),
        ];

        if ($copyHeaders) {
            $baseOptions['headers'] = $request->header();

            if ($request->hasHeader('authorization')) {
                unset($baseOptions['headers']['authorization']);
            }
        }

        $options = array_merge($baseOptions, $options);

        return $kcClient->doRequestWithClientToken($request->method(), $uri, $options);
    }

    protected function __WithUserToken(Client $kcClient, array &$userJsonToken, string $hostname, string $uri, Request $request, bool $copyHeaders = true, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $baseOptions = [
            'base_uri' => $hostname,
            'query' => $request->query(),
            'body' => $request->getContent(false),
        ];

        if ($copyHeaders) {
            $baseOptions['headers'] = $request->header();

            if ($request->hasHeader('authorization')) {
                unset($baseOptions['headers']['authorization']);
            }
        }

        $options = array_merge($baseOptions, $options);

        return $kcClient->doRequestWithUserToken($userJsonToken, $request->method(), $uri, $options);
    }

    protected function __SweetApiWithClientToken(Client $kcClient, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, Request $request, bool $copyHeaders = true, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        try {
            $xRoutes = array_intersect_key(json_decode(file_get_contents(realpath(base_path($swaggerJsonFilePath))), true), ['x-routes' => null]);

            if (!array_key_exists($apiRouteName, $xRoutes['x-routes'])) {
                Log::error(__METHOD__ . ': x-routes "' . $apiRouteName . '" not found');

                return new Response(404, [], 'x-route "' . $apiRouteName . '" not exists');
            }

            return $this->__WithClientToken($kcClient, $apiHostname, $xRoutes['x-routes'][$apiRouteName], $request, $copyHeaders, $options);
        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ': ' . $e->getMessage());

            return new Response(500, [], $e->getMessage());
        }
    }

    protected function __SweetApiWithUserToken(Client $kcClient, array &$userJsonToken, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, Request $request, bool $copyHeaders = true, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        try {
            $xRoutes = array_intersect_key(json_decode(file_get_contents(realpath(base_path($swaggerJsonFilePath))), true), ['x-routes' => null]);

            if (!array_key_exists($apiRouteName, $xRoutes['x-routes'])) {
                Log::error(__METHOD__ . ': x-routes "' . $apiRouteName . '" not found');

                return new Response(404, [], 'x-route "' . $apiRouteName . '" not exists');
            }

            return $this->__WithUserToken($kcClient, $userJsonToken, $apiHostname, $xRoutes['x-routes'][$apiRouteName], $request, $copyHeaders, $options);
        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ': ' . $e->getMessage());

            return new Response(500, [], $e->getMessage());
        }
    }

    protected function __WithAccessToken(Client $kcClient, string $accessToken, string $hostname, string $uri, Request $request, bool $copyHeaders = true, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $baseOptions = [
            'base_uri' => $hostname,
            'query' => $request->query(),
            'body' => $request->getContent(false),
        ];

        if ($copyHeaders) {
            $baseOptions['headers'] = $request->header();

            if ($request->hasHeader('authorization')) {
                unset($baseOptions['headers']['authorization']);
            }
        }

        $options = array_merge($baseOptions, $options);

        return $kcClient->doRequestWithAccessToken($accessToken, $request->method(), $uri, $options);
    }

    // ---

    public function callWithClientToken(Client $kcClient, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        return $this->__WithClientToken($kcClient, $hostname, $uri, $request, false, $options);
    }

    public function forwardWithClientToken(Client $kcClient, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        return $this->__WithClientToken($kcClient, $hostname, $uri, $request, true, $options);
    }

    public function callWithUserToken(Client $kcClient, array &$userJsonToken, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        return $this->__WithUserToken($kcClient, $userJsonToken, $hostname, $uri, $request, false, $options);
    }

    public function forwardWithUserToken(Client $kcClient, array &$userJsonToken, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        return $this->__WithUserToken($kcClient, $userJsonToken, $hostname, $uri, $request, true, $options);
    }

    public function callSweetApiWithClientToken(Client $kcClient, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        return $this->__SweetApiWithClientToken($kcClient, $swaggerJsonFilePath, $apiHostname, $apiRouteName, $request, false, $options);
    }

    public function forwardSweetApiWithClientToken(Client $kcClient, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        return $this->__SweetApiWithClientToken($kcClient, $swaggerJsonFilePath, $apiHostname, $apiRouteName, $request, true, $options);
    }

    public function callSweetApiWithUserToken(Client $kcClient, array &$userJsonToken, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        return $this->__SweetApiWithUserToken($kcClient, $userJsonToken, $swaggerJsonFilePath, $apiHostname, $apiRouteName, $request, false, $options);
    }

    public function forwardSweetApiWithUserToken(Client $kcClient, array &$userJsonToken, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        return $this->__SweetApiWithUserToken($kcClient, $userJsonToken, $swaggerJsonFilePath, $apiHostname, $apiRouteName, $request, true, $options);
    }

    public function callWithAccessToken(Client $kcClient, string $accessToken, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        return $this->__WithAccessToken($kcClient, $accessToken, $hostname, $uri, $request, false, $options);
    }

    public function forwardWithAccessToken(Client $kcClient, string $accessToken, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        return $this->__WithAccessToken($kcClient, $accessToken, $hostname, $uri, $request, true, $options);
    }
}