<?php

namespace Fuzzy\Fzpkg\Classes\SweetApi\Classes;

use Fuzzy\Fzpkg\Classes\Utils\Utils;
use Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router\{
    RoutePrefix, 
    WithTag,
    AddServer,
    WithResponse,
    WithHeader,
    WithSchema,
    WithParam,
    WithExample,
    WithRequestBody,
    WithLink,
    WithCallback,
    WithSecurityScheme,
    WithSecurityRequirement,
    Get, 
    Route, 
    RouteResponse, 
    RouteTag, 
    RouteParam, 
    RouteRequestBody, 
    RouteSchema
};

use cebe\openapi\spec\OpenApi; 
use cebe\openapi\spec\PathItem as OpenApiPathItem;
use cebe\openapi\spec\Tag as OpenApiTag;
use cebe\openapi\spec\Schema as OpenApiSchema;
use cebe\openapi\spec\Parameter as OpenApiParameter;
use cebe\openapi\spec\Response as OpenApiResponse;
use cebe\openapi\spec\Operation as OpenApiOperation;
use cebe\openapi\spec\SecurityScheme as OpenApiSecurityScheme;
use cebe\openapi\spec\SecurityRequirement as OpenApiSecurityRequirement;
use cebe\openapi\spec\RequestBody as OpenApiRequestBody;
use cebe\openapi\spec\Server as OpenApiServer;
use cebe\openapi\spec\Example as OpenApiExample;
use cebe\openapi\spec\Link as OpenApiLink;
use cebe\openapi\spec\Callback as OpenApiCallback;
use cebe\openapi\Writer;

class SwaggerEndpoints
{
    public static function generateSwaggerJson(array $urlParts, string $jsonFilePath) : void
    {
        try
        {
            $schemes = [];

            if ($urlParts['scheme'] === 'https') {
                $schemes[] = 'https';
            }

            if (empty($schemes)) {
                $schemes[] = 'http';
            }

            $components = [
                'schemas' => [],
                'responses' => [],
                'parameters' => [],
                'examples' => [],
                'requestBodies' => [],
                'headers' => [],
                'securitySchemes' => [],
                'links' => [],
                'callbacks' => []
            ];

            $servers = [];
            $security = [];

            $classes = glob(app_path('Http/Controllers/?*Controller.php'));

            if (count($classes) > 0) {
                $endpointsStruct = [];
                
                foreach ($classes as $idx => $class) {
                    $className = basename($class, '.php');

                    $namespace = Utils::makeNamespacePath('App', 'Http', 'Controllers', $className);

                    $endpointsStruct[$idx] = [];
                    $endpointsStruct[$idx]['prefix'] = '';
                    $endpointsStruct[$idx]['controller'] = $className;
                    $endpointsStruct[$idx]['methods'] = [];
                    $endpointsStruct[$idx]['tag'] = [];

                    $reflectionClass = new \ReflectionClass('\\' . $namespace);

                    $rfClass = $reflectionClass;

                    $attributes = [];

                    while ($rfClass) {
                        foreach ($rfClass->getAttributes() as $attribute) {
                            $attributes[] = $attribute;
                        }

                        $rfClass = $rfClass->getParentClass();
                    }

                    foreach ($attributes as $attribute) {
                        $attributeInstance = $attribute->newInstance();

                        if ($attributeInstance instanceof RoutePrefix) {
                            $endpointsStruct[$idx]['prefix'] = $attributeInstance->path;
                        }

                        if ($attributeInstance instanceof AddServer) {
                            $servers[] = new OpenApiServer($attributeInstance->schemaParams);
                        }

                        if ($attributeInstance instanceof WithTag) {
                            $endpointsStruct[$idx]['tag'][] = new OpenApiTag($attributeInstance->schemaParams);
                        }

                        if ($attributeInstance instanceof WithSecurityRequirement) {
                            $security[] = new OpenApiSecurityRequirement($attributeInstance->schemaParams);
                        }

                        if ($attributeInstance instanceof WithHeader) {
                            $components['headers'][$attributeInstance->name] = new OpenApiParameter($attributeInstance->schemaParams);
                        }
                        else if ($attributeInstance instanceof WithParam) {
                            $components['parameters'][$attributeInstance->name] = new OpenApiParameter($attributeInstance->schemaParams);
                        }

                        if ($attributeInstance instanceof WithResponse) {
                            $components['responses'][$attributeInstance->name] = new OpenApiResponse($attributeInstance->schemaParams);
                        }

                        if ($attributeInstance instanceof WithExample) {
                            $components['examples'][$attributeInstance->name] = new OpenApiExample($attributeInstance->schemaParams);
                        }

                        if ($attributeInstance instanceof WithRequestBody) {
                            $components['requestBodies'][$attributeInstance->name] = new OpenApiRequestBody($attributeInstance->schemaParams);
                        }

                        if ($attributeInstance instanceof WithLink) {
                            $components['links'][$attributeInstance->name] = new OpenApiLink($attributeInstance->schemaParams);
                        }

                        if ($attributeInstance instanceof WithCallback) {
                            $components['callbacks'][$attributeInstance->name] = new OpenApiCallback($attributeInstance->schemaParams);
                        }

                        if ($attributeInstance instanceof WithSchema) {
                            $components['schemas'][$attributeInstance->name] = new OpenApiSchema($attributeInstance->schemaParams);
                        }

                        if ($attributeInstance instanceof WithSecurityScheme) {
                            $components['securitySchemes'][$attributeInstance->name] = new OpenApiSecurityScheme($attributeInstance->schemaParams);
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
                        $routeRequestBody = null;

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

                            if ($attributeInstance instanceof RouteResponse) {
                                $routeResponses[$attributeInstance->statusCode] = new OpenApiResponse($attributeInstance->schemaParams);
                            }

                            if ($attributeInstance instanceof RouteTag) {
                                $routeTags[] = $attributeInstance->name;
                            }

                            if ($attributeInstance instanceof RouteParam) {
                                $routeParams[] = new OpenApiParameter($attributeInstance->schemaParams);
                            }

                            if ($attributeInstance instanceof RouteSchema) {
                                $components['schemas'][$attributeInstance->name] = new OpenApiSchema($attributeInstance->schemaParams);
                            }

                            if ($attributeInstance instanceof RouteRequestBody) {
                                $routeRequestBody = new OpenApiRequestBody($attributeInstance->schemaParams);
                            }
                        }

                        if (!is_null($routeVerbs)) {
                            $endpointsStruct[$idx]['methods'][] = ['methodName' => $methodName, 'routeVerbs' => $routeVerbs, 'routePath' => $routePath, 'routeName' => $routeName, 'routeResponses' => $routeResponses, 'routeTags' => $routeTags, 'routeParams' => $routeParams, 'routeSchemaParams' => $routeSchemaParams, 'routeRequestBody' => $routeRequestBody];
                        }
                    }
                }

                $info = [
                    'title' => config('fz.default.sweetapi.info.title', 'SweetAPI'),
                    'description' => config('fz.default.sweetapi.info.description', ''),
                    'termsOfService' => config('fz.default.sweetapi.info.termsOfService', ''),
                    'contact' => [
                        'name' => config('fz.default.sweetapi.info.contact.name', ''),
                        'url' => config('fz.default.sweetapi.info.contact.url', ''),
                        'email' => config('fz.default.sweetapi.info.contact.email', '')
                    ],
                    'license' => [
                        'name' => config('fz.default.sweetapi.info.license.email', ''),
                        'url' => config('fz.default.sweetapi.info.license.url', '')
                    ],
                    'version' => config('fz.default.sweetapi.info.version', '1.0.0'),
                ];

                if (empty($servers)) {
                    if (!isset($urlParts['port'])) {
                        $port = '';
                    }
                    else {
                        $port = ':' . $urlParts['port'];
                    }

                    if ($urlParts['scheme'] === 'http' && $port === 80) {
                        $port = '';
                    }
                    else if ($urlParts['scheme'] === 'https' && $port === 443) {
                        $port = '';
                    }

                    $url = $urlParts['host'] . $port;

                    $servers[] = new OpenApiServer(['url' => $url]);
                }

                $openapi = [
                    'openapi' => '3.0.2',
                    'info' => $info,
                    'servers' => $servers,
                    'paths' => [],
                    'components' => $components,
                    'security' => $security,
                    'tags' => [],
                    'externalDocs' => [
                        'url' => config('fz.default.sweetapi.info.externalDocs.url', ''),
                        'description' => config('fz.default.sweetapi.info.externalDocs.description', '')
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
                            $verbs = ['get', 'post', 'put', 'patch', 'options', 'delete', 'head', 'trace'];
                        }
                        else {
                            $verbs = $methodData['routeVerbs'];
                        }

                        array_unshift($methodData['routeTags'], $controllerData['controller']);

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
                                'responses' => $methodData['routeResponses'],
                                'x-endpoints' => $controllerData['controller'] . '::' . $methodData['methodName']
                            ]);

                            if (count($verbs) === 1) {
                                $pData['parameters'] = $methodData['routeParams'];
                            }

                            if (!is_null($methodData['routeRequestBody'])) {
                                $pData['requestBody'] = $methodData['routeRequestBody'];
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
}
