<?php

namespace App\Http\Endpoints;

use Fuzzy\Fzpkg\Classes\Utils\Utils;
use Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router\{RoutePrefix, Get, Route, Info, Response, Tag, WithParam};
use cebe\openapi\Writer;
use cebe\openapi\spec\OpenApi; 
use cebe\openapi\spec\PathItem as OpenApiPathItem;
use cebe\openapi\spec\Tag as OpenApiTag;
use ReflectionClass;
use Throwable;

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
            $this->generateSwaggerJson(parse_url(url()->current()), $jsonFilePath);
        }

        return response(file_get_contents($jsonFilePath), 200)->header('Content-Type', 'application/json');
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
                    $endpointsStruct[$idx]['tag'] = ['name' => '', 'description' => '', 'externalDocs' => ['url' => '', 'description' => '']];
                    $endpointsStruct[$idx]['methods'] = [];

                    $reflectionClass = new ReflectionClass('\\' . $namespace);

                    foreach ($reflectionClass->getAttributes() as $attribute) {
                        $attributeInstance = $attribute->newInstance();

                        if ($attributeInstance instanceof RoutePrefix) {
                            $endpointsStruct[$idx]['prefix'] = $attributeInstance->path;
                        }

                        if ($attributeInstance instanceof Info) {
                            $endpointsStruct[$idx]['tag'] = ['name' => $className, 'description' => $attributeInstance->description, 'externalDocs' => ['url' => $attributeInstance->link, 'description' => $attributeInstance->linkDescription]];
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

                        foreach ($method->getAttributes() as $attribute) {
                            $attributeInstance = $attribute->newInstance();

                            if ($attributeInstance instanceof Route) {
                                $routeVerbs = strtolower($attributeInstance->verbs);
                                $routePath = $attributeInstance->path;
                                $routeName = $attributeInstance->name;
                                $routeConsumes = $attributeInstance->consumes;
                                $routeSummary = $attributeInstance->summary;
                                $routeDescription = $attributeInstance->description;
                                $routeDeprecated = $attributeInstance->deprecated;
                                
                                if ($routeVerbs === '*') {
                                    $routeVerbs = 'any';
                                }
                                else {
                                    $routeVerbs = explode('|', $routeVerbs);
                                }
                            }

                            if ($attributeInstance instanceof Response) {
                                $routeResponses[$attributeInstance->statusCode] = ['content' => $attributeInstance->content, 'description' => $attributeInstance->description];
                            }

                            if ($attributeInstance instanceof Tag) {
                                $routeTags[] = $attributeInstance->name;
                            }

                            if ($attributeInstance instanceof WithParam) {
                                $routeParams[] = ['name' => $attributeInstance->name, 'in' => $attributeInstance->in, 'description' => $attributeInstance->description, 'required' => $attributeInstance->required, 'deprecated' => $attributeInstance->deprecated, 'allowEmptyValue' => $attributeInstance->allowEmptyValue, 'example' => $attributeInstance->example];
                            }
                        }

                        if (!is_null($routeVerbs)) {
                            $endpointsStruct[$idx]['methods'][] = ['methodName' => $methodName, 'routeVerbs' => $routeVerbs, 'routePath' => $routePath, 'routeName' => $routeName, 'routeSummary' => $routeSummary, 'routeDescription' => $routeDescription, 'routeDeprecated' => $routeDeprecated, 'routeResponses' => $routeResponses, 'routeTags' => $routeTags, 'routeConsumes' => $routeConsumes, 'routeParams' => $routeParams];
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
                    'swagger' => '2.0',
                    //'openapi' => '3.0.2',
                    'info' => $info,
                    'host' => $urlParts['host'] . ($urlParts['port'] === 80 ? '' : ':' . $urlParts['port']),
                    'basePath' => '/',
                    'tags' => [],
                    'schemes' => $schemes,
                    'paths' => [],
                    'externalDocs' => [
                        'url' => env('SWEETAPI_EXT_DOC_URL', ''),
                        'description' => env('SWEETAPI_EXT_DESCRIPTION', '')
                    ],
                    'x-routes' => []
                ];

                $endpointsStruct = array_reverse($endpointsStruct);

                foreach ($endpointsStruct as $idx => $controllerData) {
                    $openapi['tags'][] = new OpenApiTag($controllerData['tag']);
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

                        $pathItemData = [];
                        array_unshift($methodData['routeTags'], $controllerData['controller']);

                        $produces = [];

                        foreach ($methodData['routeResponses'] as $statusCode => $data) {
                            $produces[strtolower($data['content'])] = true;
                        }

                        if (empty($produces)) {
                            $produces['string'] = true;
                        }

                        if (count($verbs) > 1) {
                            $pathItemData['parameters'] = $methodData['routeParams'];
                        }

                        foreach ($verbs as $verb) {
                            $pathItemData[$verb] = [
                                'summary' => $methodData['routeSummary'],
                                'description' => $methodData['routeDescription'],
                                'operationId' => $methodData['routeName'] . '_###_' . $verb,
                                'consumes' => $methodData['routeConsumes'],
                                'produces' => array_keys($produces),
                                'tags' => $methodData['routeTags'],
                                'deprecated' => $methodData['routeDeprecated'],
                                'responses' => $methodData['routeResponses']
                            ];

                            if (count($verbs) === 1) {
                                $pathItemData[$verb]['parameters'] = $methodData['routeParams'];
                            }
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
        catch (Throwable $e) {
            echo $e->getMessage();
        }
    }
}
