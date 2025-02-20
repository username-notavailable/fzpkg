<?php

namespace App\Http\Endpoints;

use Fuzzy\Fzpkg\Classes\Utils\Utils;
use Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router\{RoutePrefix, Get, Route, Response, Tag, RouteTag, WithParam, WithBody, Schema};
use cebe\openapi\Writer;
use cebe\openapi\spec\OpenApi; 
use cebe\openapi\spec\PathItem as OpenApiPathItem;
use cebe\openapi\spec\Tag as OpenApiTag;
use cebe\openapi\spec\Schema as OpenApiSchema;
use cebe\openapi\spec\Parameter as OpenApiParameter;
use cebe\openapi\spec\Response as OpenApiResponse;
use cebe\openapi\spec\Operation as OpenApiOperation;
use cebe\openapi\spec\SecurityScheme as OpenApiSecurityScheme;
use cebe\openapi\spec\SecurityRequirement as OpenApiSecurityRequirement;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Client;
use App\Http\Classes\{ApiResponse, ApiErrorResponse};

#[RoutePrefix(path: '/swagger')]
class SwaggerEndpoints extends Endpoints
{
    #[Get(path: '/docs', name: 'swagger_index')]
    public function index()
    {
        $urlParts = parse_url(url()->current());
        $url = $urlParts['scheme'] . '://' . $urlParts['host'] . ($urlParts['port'] === 80 ? '' : ':' . $urlParts['port']);

        return response(str_replace(['{{ swagger_json_url }}', '{{ base_href }}'], [route('swagger_json'), $url], file_get_contents(base_path('sweetapi/swagger_index.html'))), 200)->header('Content-Type', 'text/html');
    }

    #[Get(path: '/json', name: 'swagger_json')]
    public function json()
    {
        $jsonFilePath = base_path(env('SWAGGER_FILE_PATH', 'sweetapi/swagger.json'));
    
        if (!file_exists($jsonFilePath) || (env('APP_ENV') === 'local' && env('SWAGGER_REPLACE_FILE'))) {
            self::generateSwaggerJson(parse_url(url()->current()), $jsonFilePath);
            chmod($jsonFilePath, 0666);
        }

        return response(file_get_contents($jsonFilePath), 200)->header('Content-Type', 'application/json');
    }

    #[Get(path: '/get-token', name: 'swagger_get_token')]
    public function getToken()
    {
        $ckClient = new Client(env('KC_LOGIN_HOSTNAME'));

        $jsonToken = $ckClient->getToken();

        if (!$jsonToken) {
            return new ApiErrorResponse('getToken() failed', [], 500);
        }
        else {
            return new ApiResponse($jsonToken);
        }
    }

    public static function generateSwaggerJson(array $urlParts, string $jsonFilePath) : void
    {
        try
        {
            $schemes = ['http'];

            if ($urlParts['scheme'] === 'https') {
                $schemes[] = 'https';
            }

            $classes = glob(__DIR__ . DIRECTORY_SEPARATOR . '?*Endpoints.php');
            $definitions = [];

            if (count($classes) > 0) {
                $endpointsStruct = [];
                
                foreach ($classes as $idx => $class) {
                    $className = basename($class, '.php');

                    if ($className === 'SwaggerEndpoints') {
                        continue;
                    }

                    $namespace = Utils::makeNamespacePath('App', 'Http', 'Endpoints', $className);

                    $endpointsStruct[$idx] = [];
                    $endpointsStruct[$idx]['prefix'] = '';
                    $endpointsStruct[$idx]['controller'] = $className;
                    $endpointsStruct[$idx]['methods'] = [];
                    $endpointsStruct[$idx]['tag'] = [];

                    $reflectionClass = new \ReflectionClass('\\' . $namespace);

                    foreach ($reflectionClass->getAttributes() as $attribute) {
                        $attributeInstance = $attribute->newInstance();

                        if ($attributeInstance instanceof RoutePrefix) {
                            $endpointsStruct[$idx]['prefix'] = $attributeInstance->path;
                        }

                        if ($attributeInstance instanceof Tag) {
                            $endpointsStruct[$idx]['tag'][] = new OpenApiTag($attributeInstance->schemaParams);
                        }

                        if ($attributeInstance instanceof Schema) {
                            $definitions[$attributeInstance->name] = new OpenApiSchema($attributeInstance->schemaParams);
                        }
                    }

                    foreach ($reflectionClass->getMethods() as $method) {
                        $methodName = $method->getName();
                        $routeVerbs = null;
                        $routePath = null;
                        $routeName = null;
                        $routeResponses = [];
                        $routeTags = [];
                        $routeParams = [];
                        $routeSchemaParams = [];

                        foreach ($method->getAttributes() as $attribute) {
                            $attributeInstance = $attribute->newInstance();

                            if ($attributeInstance instanceof Route) {
                                $routeVerbs = strtolower($attributeInstance->verbs);
                                $routePath = $attributeInstance->path;
                                $routeName = $attributeInstance->name;
                                $routeSchemaParams = $attributeInstance->schemaParams;
                                
                                if ($routeVerbs === '*') {
                                    $routeVerbs = 'any';
                                }
                                else {
                                    $routeVerbs = explode('|', $routeVerbs);
                                }
                            }

                            if ($attributeInstance instanceof Response) {
                                $routeResponses[$attributeInstance->statusCode] = new OpenApiResponse($attributeInstance->schemaParams);
                            }

                            if ($attributeInstance instanceof RouteTag) {
                                $routeTags[] = $attributeInstance->name;
                            }

                            if ($attributeInstance instanceof WithParam) {
                                $routeParams[] = new OpenApiParameter($attributeInstance->schemaParams);
                            }

                            if ($attributeInstance instanceof Schema) {
                                $components[$attributeInstance->name] = new OpenApiSchema($attributeInstance->schemaParams);
                            }
                        }

                        if (!is_null($routeVerbs)) {
                            $endpointsStruct[$idx]['methods'][] = ['methodName' => $methodName, 'routeVerbs' => $routeVerbs, 'routePath' => $routePath, 'routeName' => $routeName, 'routeResponses' => $routeResponses, 'routeTags' => $routeTags, 'routeParams' => $routeParams, 'routeSchemaParams' => $routeSchemaParams];
                        }
                    }
                }

                $info = [
                    'title' => env('SWEETAPI_TITLE', 'SweetAPI'),
                    'description' => env('SWEETAPI_DESCRIPTION', ''),
                    'termsOfService' => env('SWEETAPI_TERMS_OF_SERVICE', ''),
                    'contact' => [
                        'name' => env('SWEETAPI_CONTACT_NAME', ''),
                        'url' => env('SWEETAPI_CONTACT_URL', ''),
                        'email' => env('SWEETAPI_CONTACT_EMAIL', '')
                    ],
                    'license' => [
                        'name' => env('SWEETAPI_LICENSE_NAME', ''),
                        'url' => env('SWEETAPI_LICENSE_URL', '')
                    ],
                    'version' => env('SWEETAPI_OPEN_API_DOC_VERSION', '1.0.0'),
                ];

                $openapi = [
                    'swagger' => '2.0',
                    //'openapi' => '3.0.2',
                    'info' => $info,
                    'host' => $urlParts['host'] . ($urlParts['port'] === 80 ? '' : ':' . $urlParts['port']),
                    'basePath' => '/',
                    'securityDefinitions' => [
                        'Bearer' => new OpenApiSecurityScheme([
                            'type' => 'apiKey',
                            'description' => 'Realm bearer token',
                            'name' => 'Authorization',
                            'in' => 'header'
                        ])
                    ],
                    'security' => [
                        new OpenApiSecurityRequirement(['Bearer' => []])
                    ],
                    'tags' => [],
                    'schemes' => $schemes,
                    'paths' => [],
                    'externalDocs' => [
                        'url' => env('SWEETAPI_EXT_DOC_URL', ''),
                        'description' => env('SWEETAPI_EXT_DESCRIPTION', '')
                    ],
                    'definitions' => $definitions,
                    'x-routes' => []
                ];

                $endpointsStruct = array_reverse($endpointsStruct);

                foreach ($endpointsStruct as $idx => $controllerData) {
                    foreach ($controllerData['tag'] as $tag) {
                        $openapi['tags'][] = $tag;
                    }
                    
                    foreach ($controllerData['methods'] as $methodData) {
                        $pathName = '';

                        if (trim($controllerData['prefix'], '/') !== '') {
                            $pathName .= trim($controllerData['prefix'], ' ');
                        }

                        $pathName .= trim($methodData['routePath'], ' ');
                        
                        if (is_string($methodData['routeVerbs'])) {
                            $verbs = ['get', 'post', 'put', 'patch', 'options', 'delete'];
                        }
                        else {
                            $verbs = $methodData['routeVerbs'];
                        }

                        array_unshift($methodData['routeTags'], $controllerData['controller']);

                        /*$produces = [];

                        foreach ($methodData['routeResponses'] as $data) {
                            $produces[strtolower($data->content)] = true;
                        }

                        if (empty($produces)) {
                            $produces['string'] = true;
                        }*/

                        $pathItemData = [];

                        if (count($verbs) > 1) {
                            foreach (['$ref', 'summary', 'description', 'servers'] as $param) {
                                if (array_key_exists($param, $methodData['routeSchemaParams'])) {
                                    $pathItemData[$param] = $methodData['routeSchemaParams'][$param];
                                    unset($methodData['routeSchemaParams'][$param]);
                                }
                            }

                            $pathItemData['parameters'] = $methodData['routeParams'];
                        }

                        foreach ($verbs as $verb) {
                            $pData = array_merge($methodData['routeSchemaParams'], [
                                'tags' => $methodData['routeTags'],
                                'operationId' => $methodData['routeName'] . '_###_' . $verb,
                                //'produces' => array_keys($produces),
                                'responses' => $methodData['routeResponses'],
                                'x-endpoints' => $controllerData['controller'] . '::' . $methodData['methodName']
                            ]);

                            if (count($verbs) === 1) {
                                $pData['parameters'] = $methodData['routeParams'];
                            }

                            $pathItemData[$verb] = new OpenApiOperation($pData);
                        }

                        $openapi['paths'][$pathName] = new OpenApiPathItem($pathItemData);
                        $openapi['x-routes'][$methodData['routeName']] = $pathName;
                    }
                }

                $cebe = new OpenApi($openapi);

                $json = Writer::writeToJson($cebe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                file_put_contents($jsonFilePath, $json);
            }
        }
        catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }

    /*public static function generateSwaggerJsonV3(array $urlParts, string $jsonFilePath) : void
    {
        try
        {
            $schemes = ['http'];

            if ($urlParts['scheme'] === 'https') {
                $schemes[] = 'https';
            }

            $classes = glob(__DIR__ . DIRECTORY_SEPARATOR . '?*Endpoints.php');
            $components = [];

            if (count($classes) > 0) {
                $endpointsStruct = [];
                
                foreach ($classes as $idx => $class) {
                    $className = basename($class, '.php');

                    if ($className === 'SwaggerEndpoints') {
                        continue;
                    }

                    $namespace = Utils::makeNamespacePath('App', 'Http', 'Endpoints', $className);

                    $endpointsStruct[$idx] = [];
                    $endpointsStruct[$idx]['prefix'] = '';
                    $endpointsStruct[$idx]['controller'] = $className;
                    $endpointsStruct[$idx]['methods'] = [];
                    $endpointsStruct[$idx]['tag'] = [];

                    $reflectionClass = new \ReflectionClass('\\' . $namespace);

                    foreach ($reflectionClass->getAttributes() as $attribute) {
                        $attributeInstance = $attribute->newInstance();

                        if ($attributeInstance instanceof RoutePrefix) {
                            $endpointsStruct[$idx]['prefix'] = $attributeInstance->path;
                        }

                        if ($attributeInstance instanceof Tag) {
                            $endpointsStruct[$idx]['tag'][] = new OpenApiTag($attributeInstance->schemaParams);
                        }

                        if ($attributeInstance instanceof Schema) {
                            $components[$attributeInstance->name] = new OpenApiSchema($attributeInstance->schemaParams);
                        }
                    }

                    foreach ($reflectionClass->getMethods() as $method) {
                        $methodName = $method->getName();
                        $routeVerbs = null;
                        $routePath = null;
                        $routeName = null;
                        $routeResponses = [];
                        $routeTags = [];
                        $routeParams = [];
                        $routeSchemaParams = [];

                        foreach ($method->getAttributes() as $attribute) {
                            $attributeInstance = $attribute->newInstance();

                            if ($attributeInstance instanceof Route) {
                                $routeVerbs = strtolower($attributeInstance->verbs);
                                $routePath = $attributeInstance->path;
                                $routeName = $attributeInstance->name;
                                $routeSchemaParams = $attributeInstance->schemaParams;
                                
                                if ($routeVerbs === '*') {
                                    $routeVerbs = 'any';
                                }
                                else {
                                    $routeVerbs = explode('|', $routeVerbs);
                                }
                            }

                            if ($attributeInstance instanceof Response) {
                                $routeResponses[$attributeInstance->statusCode] = new OpenApiResponse($attributeInstance->schemaParams);
                            }

                            if ($attributeInstance instanceof RouteTag) {
                                $routeTags[] = $attributeInstance->name;
                            }

                            if ($attributeInstance instanceof WithParam) {
                                $routeParams[] = new OpenApiParameter($attributeInstance->schemaParams);
                            }

                            if ($attributeInstance instanceof Schema) {
                                $components[$attributeInstance->name] = new OpenApiSchema($attributeInstance->schemaParams);
                            }
                        }

                        if (!is_null($routeVerbs)) {
                            $endpointsStruct[$idx]['methods'][] = ['methodName' => $methodName, 'routeVerbs' => $routeVerbs, 'routePath' => $routePath, 'routeName' => $routeName, 'routeResponses' => $routeResponses, 'routeTags' => $routeTags, 'routeParams' => $routeParams, 'routeSchemaParams' => $routeSchemaParams];
                        }
                    }
                }

                $info = [
                    'title' => env('SWEETAPI_TITLE', 'SweetAPI'),
                    'summary' => env('SWEETAPI_SUMMARY', ''),
                    'description' => env('SWEETAPI_DESCRIPTION', ''),
                    'termsOfService' => env('SWEETAPI_TERMS_OF_SERVICE', ''),
                    'contact' => [
                        'name' => env('SWEETAPI_CONTACT_NAME', ''),
                        'url' => env('SWEETAPI_CONTACT_URL', ''),
                        'email' => env('SWEETAPI_CONTACT_EMAIL', '')
                    ],
                    'license' => [
                        'name' => env('SWEETAPI_LICENSE_NAME', ''),
                        'identifier' => env('SWEETAPI_LICENSE_SPDX', ''),
                        'url' => env('SWEETAPI_LICENSE_URL', '')
                    ],
                    'version' => env('SWEETAPI_OPEN_API_DOC_VERSION', '1.0.0'),
                ];

                $openapi = [
                    'swagger' => '3.0',
                    //'openapi' => '3.0.2',
                    'info' => $info,
                    'jsonSchemaDialect' => 'https://spec.openapis.org/oas/3.1/dialect/base',
                    'servers' => [
                        'url' => $urlParts['host'] . ($urlParts['port'] === 80 ? '' : ':' . $urlParts['port'])
                    ],
                    'paths' => [],
                    'webhooks' => [],
                    'components' => $components,
                    'security' => '',
                    'tags' => [],
                    //'host' => ,
                    //'basePath' => '/',
                    
                    //'schemes' => $schemes,
                    
                    'externalDocs' => [
                        'url' => env('SWEETAPI_EXT_DOC_URL', ''),
                        'description' => env('SWEETAPI_EXT_DESCRIPTION', '')
                    ],
                    'x-routes' => []
                ];

                $endpointsStruct = array_reverse($endpointsStruct);

                foreach ($endpointsStruct as $idx => $controllerData) {
                    foreach ($controllerData['tag'] as $tag) {
                        $openapi['tags'][] = $tag;
                    }
                    
                    foreach ($controllerData['methods'] as $methodData) {
                        $pathName = '';

                        if (trim($controllerData['prefix'], '/') !== '') {
                            $pathName .= trim($controllerData['prefix'], ' ');
                        }

                        $pathName .= trim($methodData['routePath'], ' ');
                        
                        if (is_string($methodData['routeVerbs'])) {
                            $verbs = ['get', 'post', 'put', 'patch', 'options', 'delete'];
                        }
                        else {
                            $verbs = $methodData['routeVerbs'];
                        }

                        array_unshift($methodData['routeTags'], $controllerData['controller']);

                        $produces = [];

                        foreach ($methodData['routeResponses'] as $data) {
                            $produces[strtolower($data->content)] = true;
                        }

                        if (empty($produces)) {
                            $produces['string'] = true;
                        }

                        $pathItemData = [];

                        if (count($verbs) > 1) {
                            foreach (['$ref', 'summary', 'description', 'servers'] as $param) {
                                if (array_key_exists($param, $methodData['routeSchemaParams'])) {
                                    $pathItemData[$param] = $methodData['routeSchemaParams'][$param];
                                    unset($methodData['routeSchemaParams'][$param]);
                                }
                            }

                            $pathItemData['parameters'] = $methodData['routeParams'];
                        }

                        foreach ($verbs as $verb) {
                            $pData = array_merge($methodData['routeSchemaParams'], [
                                'tags' => $methodData['routeTags'],
                                'operationId' => $methodData['routeName'] . '_###_' . $verb,
                                'produces' => array_keys($produces),
                                'responses' => $methodData['routeResponses'],
                                'x-endpoints' => $controllerData['controller'] . '::' . $methodData['methodName']
                            ]);

                            if (count($verbs) === 1) {
                                $pData['parameters'] = $methodData['routeParams'];
                            }

                            $pathItemData[$verb] = new OpenApiOperation($pData);
                        }

                        $openapi['paths'][$pathName] = new OpenApiPathItem($pathItemData);
                        $openapi['x-routes'][$methodData['routeName']] = $pathName;
                    }
                }

                $cebe = new OpenApi($openapi);

                $json = Writer::writeToJson($cebe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                file_put_contents($jsonFilePath, $json);
            }
        }
        catch (\Throwable $e) {
            echo $e->getMessage();
        }
    }*/
}
