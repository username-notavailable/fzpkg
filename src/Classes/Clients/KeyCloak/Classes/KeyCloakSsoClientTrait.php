<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes;

use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait KeyCloakSsoClientTrait
{
    public function callWithClientToken(Client $kcClient, string $hostname, string $uri, string $method, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $baseOptions = [
            'base_uri' => $hostname,
        ];

        $options = array_merge_recursive_distinct($baseOptions, $options);

        Log::debug(__METHOD__ . ': kcClient[doRequestWithClientToken] method = "'. $method . '" base_uri = "' . $options['base_uri'] . '" uri = "' . $uri . '"', $options);

        return $kcClient->doRequestWithClientToken($method, $uri, $options);
    }

    public function callWithUserToken(Client $kcClient, array &$userJsonToken, string $hostname, string $uri, string $method, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $baseOptions = [
            'base_uri' => $hostname,
        ];

        $options = array_merge_recursive_distinct($baseOptions, $options);

        Log::debug(__METHOD__ . ': kcClient[doRequestWithUserToken] method = "'. $method . '" base_uri = "' . $options['base_uri'] . '" uri = "' . $uri . '"', $options);

        return $kcClient->doRequestWithUserToken($userJsonToken, $method, $uri, $options);
    }

    public function callWithAccessToken(Client $kcClient, string $accessToken, string $hostname, string $uri, string $method, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $baseOptions = [
            'base_uri' => $hostname,
        ];

        $options = array_merge_recursive_distinct($baseOptions, $options);

        Log::debug(__METHOD__ . ': kcClient[doRequestWithAccessToken] method = "'. $method . '" base_uri = "' . $options['base_uri'] . '" uri = "' . $uri . '"', $options);

        return $kcClient->doRequestWithAccessToken($accessToken, $method, $uri, $options);
    }

    // ---

    public function callSweetApiWithClientToken(Client $kcClient, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, string $method, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $xRoutes = array_intersect_key(json_decode(file_get_contents(realpath(base_path($swaggerJsonFilePath))), true), ['x-routes' => null]);

        if (!array_key_exists($apiRouteName, $xRoutes['x-routes'])) {
            throw new \Exception(__METHOD__ . ': x-routes "' . $apiRouteName . '" not found');
        }

        $baseOptions = [
            'base_uri' => $apiHostname,
        ];

        $options = array_merge_recursive_distinct($baseOptions, $options);

        Log::debug(__METHOD__ . ': kcClient[doRequestWithClientToken] method = "'. $method . '" base_uri = "' . $options['base_uri'] . '" uri = "' . $xRoutes['x-routes'][$apiRouteName] . '" xroute = "' . $apiRouteName . '"', $options);

        return $kcClient->doRequestWithClientToken($method, $xRoutes['x-routes'][$apiRouteName], $options);
    }

    public function callSweetApiWithUserToken(Client $kcClient, array &$userJsonToken, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, string $method, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $xRoutes = array_intersect_key(json_decode(file_get_contents(realpath(base_path($swaggerJsonFilePath))), true), ['x-routes' => null]);

        if (!array_key_exists($apiRouteName, $xRoutes['x-routes'])) {
            throw new \Exception(__METHOD__ . ': x-routes "' . $apiRouteName . '" not found');
        }

        $baseOptions = [
            'base_uri' => $apiHostname,
        ];

        $options = array_merge_recursive_distinct($baseOptions, $options);

        Log::debug(__METHOD__ . ': kcClient[doRequestWithUserToken] method = "'. $method . '" base_uri = "' . $options['base_uri'] . '" uri = "' . $xRoutes['x-routes'][$apiRouteName] . '" xroute = "' . $apiRouteName . '"', $options);

        return $kcClient->doRequestWithUserToken($userJsonToken, $method, $xRoutes['x-routes'][$apiRouteName], $options);
    }

    // ---

    public function forwardToWithClientToken(Client $kcClient, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        Log::debug(__METHOD__ . ': kcClient[__WithClientToken] method = "'. $request->method() . '" base_uri = "' . $hostname . '" uri = "' . $uri . '"', $options);
        return $this->__WithClientToken($kcClient, $hostname, $uri, $request, $options);
    }

    public function forwardToWithUserToken(Client $kcClient, array &$userJsonToken, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        Log::debug(__METHOD__ . ': kcClient[__WithUserToken] method = "'. $request->method() . '" base_uri = "' . $hostname . '" uri = "' . $uri . '"', $options);
        return $this->__WithUserToken($kcClient, $userJsonToken, $hostname, $uri, $request, $options);
    }

    public function forwardToWithAccessToken(Client $kcClient, string $accessToken, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        Log::debug(__METHOD__ . ': kcClient[__WithAccessToken] method = "'. $request->method() . '" base_uri = "' . $hostname . '" uri = "' . $uri . '"', $options);
        return $this->__WithAccessToken($kcClient, $accessToken, $hostname, $uri, $request, $options);
    }

    public function forwardTo(Client $kcClient, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $baseOptions = [
            'base_uri' => $hostname,
            'query' => $request->query(),
            'body' => $request->getContent(false),
            'headers' => $request->header()
        ];

        foreach (['host'] as $headerName) {
            if (isset($baseOptions['headers'][$headerName])) {
                unset($baseOptions['headers'][$headerName]);
            }
        }

        $options = array_merge_recursive_distinct($baseOptions, $options);

        Log::debug(__METHOD__ . ': kcClient[doHttp2Request] method = "'. $request->method() . '" base_uri = "' . $options['base_uri'] . '" uri = "' . $uri . '"', $options);

        return $kcClient->doHttp2Request($request->method(), $uri, $options);
    }

    // ---

    public function forwardToSweetApiWithClientToken(Client $kcClient, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        Log::debug(__METHOD__ . ': kcClient[__SweetApiWithClientToken] method = "'. $request->method() . '" base_uri = "' . $apiHostname . '" xroute = "' . $apiRouteName . '"', $options);
        return $this->__SweetApiWithClientToken($kcClient, $swaggerJsonFilePath, $apiHostname, $apiRouteName, $request, $options);
    }

    public function forwardToSweetApiWithUserToken(Client $kcClient, array &$userJsonToken, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        Log::debug(__METHOD__ . ': kcClient[__SweetApiWithUserToken] method = "'. $request->method() . '" base_uri = "' . $apiHostname . '" xroute = "' . $apiRouteName . '"', $options);
        return $this->__SweetApiWithUserToken($kcClient, $userJsonToken, $swaggerJsonFilePath, $apiHostname, $apiRouteName, $request, $options);
    }

    public function forwardToSweetApi(Client $kcClient, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $xRoutes = array_intersect_key(json_decode(file_get_contents(realpath(base_path($swaggerJsonFilePath))), true), ['x-routes' => null]);

        if (!array_key_exists($apiRouteName, $xRoutes['x-routes'])) {
            throw new \Exception(__METHOD__ . ': x-routes "' . $apiRouteName . '" not found');
        }

        $baseOptions = [
            'base_uri' => $apiHostname,
            'query' => $request->query(),
            'body' => $request->getContent(false),
            'headers' => $request->header()
        ];

        foreach (['host'] as $headerName) {
            if (isset($baseOptions['headers'][$headerName])) {
                unset($baseOptions['headers'][$headerName]);
            }
        }

        $options = array_merge_recursive_distinct($baseOptions, $options);

        Log::debug(__METHOD__ . ': kcClient[doHttp2Request] method = "'. $request->method() . '" base_uri = "' . $options['base_uri'] . '" uri = "' . $xRoutes['x-routes'][$apiRouteName] . '" xroute = "' . $apiRouteName . '"', $options);

        return $kcClient->doHttp2Request($request->method(), $xRoutes['x-routes'][$apiRouteName], $options);
    }

    // ---

    protected function __WithClientToken(Client $kcClient, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $baseOptions = [
            'base_uri' => $hostname,
            'query' => $request->query(),
            'body' => $request->getContent(false),
            'headers' => $request->header()
        ];

        foreach (['authorization', 'host'] as $headerName) {
            if (isset($baseOptions['headers'][$headerName])) {
                unset($baseOptions['headers'][$headerName]);
            }
        }

        $options = array_merge_recursive_distinct($baseOptions, $options);

        Log::debug(__METHOD__ . ': kcClient[doRequestWithClientToken] method = "'. $request->method() . '" base_uri = "' . $options['base_uri'] . '" uri = "' . $uri . '"', $options);

        return $kcClient->doRequestWithClientToken($request->method(), $uri, $options);
    }

    protected function __WithUserToken(Client $kcClient, array &$userJsonToken, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $baseOptions = [
            'base_uri' => $hostname,
            'query' => $request->query(),
            'body' => $request->getContent(false),
            'headers' => $request->header()
        ];

        foreach (['authorization', 'host'] as $headerName) {
            if (isset($baseOptions['headers'][$headerName])) {
                unset($baseOptions['headers'][$headerName]);
            }
        }

        $options = array_merge_recursive_distinct($baseOptions, $options);

        Log::debug(__METHOD__ . ': kcClient[doRequestWithUserToken] method = "'. $request->method() . '" base_uri = "' . $options['base_uri'] . '" uri = "' . $uri . '"', $options);

        return $kcClient->doRequestWithUserToken($userJsonToken, $request->method(), $uri, $options);
    }

    protected function __SweetApiWithClientToken(Client $kcClient, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $xRoutes = array_intersect_key(json_decode(file_get_contents(realpath(base_path($swaggerJsonFilePath))), true), ['x-routes' => null]);

        if (!array_key_exists($apiRouteName, $xRoutes['x-routes'])) {
            throw new \Exception(__METHOD__ . ': x-routes "' . $apiRouteName . '" not found');
        }

        Log::debug(__METHOD__ . ': kcClient[__WithClientToken] method = "'. $request->method() . '" base_uri = "' . $apiHostname . '" uri = "' . $xRoutes['x-routes'][$apiRouteName] . ' " xroute = "' . $apiRouteName . '"', $options);

        return $this->__WithClientToken($kcClient, $apiHostname, $xRoutes['x-routes'][$apiRouteName], $request, $options);
    }

    protected function __SweetApiWithUserToken(Client $kcClient, array &$userJsonToken, string $swaggerJsonFilePath, string $apiHostname, string $apiRouteName, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $xRoutes = array_intersect_key(json_decode(file_get_contents(realpath(base_path($swaggerJsonFilePath))), true), ['x-routes' => null]);

        if (!array_key_exists($apiRouteName, $xRoutes['x-routes'])) {
            throw new \Exception(__METHOD__ . ': x-routes "' . $apiRouteName . '" not found');
        }

        Log::debug(__METHOD__ . ': kcClient[__WithUserToken] method = "'. $request->method() . '" base_uri = "' . $apiHostname . '" uri = "' . $xRoutes['x-routes'][$apiRouteName] . ' " xroute = "' . $apiRouteName . '"', $options);

        return $this->__WithUserToken($kcClient, $userJsonToken, $apiHostname, $xRoutes['x-routes'][$apiRouteName], $request, $options);
    }

    protected function __WithAccessToken(Client $kcClient, string $accessToken, string $hostname, string $uri, Request $request, array $options = []) : \Psr\Http\Message\ResponseInterface
    {
        $baseOptions = [
            'base_uri' => $hostname,
            'query' => $request->query(),
            'body' => $request->getContent(false),
            'headers' => $request->header()
        ];

        foreach (['authorization', 'host'] as $headerName) {
            if (isset($baseOptions['headers'][$headerName])) {
                unset($baseOptions['headers'][$headerName]);
            }
        }
        
        $options = array_merge_recursive_distinct($baseOptions, $options);

        Log::debug(__METHOD__ . ': kcClient[doRequestWithUserToken] method = "'. $request->method() . '" base_uri = "' . $options['base_uri'] . '" uri = "' . $uri . '"', $options);

        return $kcClient->doRequestWithAccessToken($accessToken, $request->method(), $uri, $options);
    }
}