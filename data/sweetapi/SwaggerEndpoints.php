<?php

namespace App\Http\SweetApi\{{ api_name }};

use Fuzzy\Fzpkg\Classes\Utils\Utils;
use Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router\{RoutePrefix, Route, Get};
use cebe\openapi\Writer;
use cebe\openapi\spec\{OpenApi, Info, Paths, PathItem, Operation, Responses, Response};
use ReflectionClass;
use Throwable;

#[RoutePrefix(path: 'swagger')]
class SwaggerEndpoints extends Endpoints
{
    #[Get(path: '/docs', name: 'swagger_index')]
    public function index()
    {
        $urlParts = parse_url(url()->current());
        $url = $urlParts['scheme'] . '://' . $urlParts['host'] . ':' . $urlParts['port'];

        return response(str_replace(['{{ swagger_json_url }}', '{{ base_href }}'], [route('swagger_json'), $url], file_get_contents(Utils::makeFilePath(__DIR__, 'bootstrap', 'swagger_index.html'))), 200)->header('Content-Type', 'text/html');
    }

    #[Get(path: '/json', name: 'swagger_json')]
    public function json()
    {
        $jsonFilePath = Utils::makeFilePath(__DIR__, 'bootstrap', 'swagger.json');
    
        if (!file_exists($jsonFilePath)) {
            $urlParts = parse_url(url()->current());
            $this->generateSwaggerJson($urlParts);
        }

        return response(file_get_contents($jsonFilePath), 200)->header('Content-Type', 'application/json');
    }

    protected function generateSwaggerJson(array $urlParts) : void
    {
        try 
        {
            $schemes = ['http'];

            if ($urlParts['scheme'] === 'https') {
                $schemes[] = 'https';
            }

            $apiName = '{{ api_name }}';
            $apiDirectoryPath = app_path('Http/SweetApi/' . $apiName);
            $apiPrefix = '/' . strtolower($apiName);

            $openapi = new OpenApi([
                'swagger' => '2.0',
                'info' => new Info([
                    'title' => 'SweetAPI "' . $apiName . '"',
                    'version' => '1.0.0',
                ]),
                'host' => $urlParts['host'] . ($urlParts['port'] === 80 ? '' : ':' . $urlParts['port']),
                'basePath' => $apiPrefix,
                'schemes' => $schemes,
                'paths' => new Paths([])
            ]);

            $classes = glob($apiDirectoryPath . DIRECTORY_SEPARATOR . '?*Endpoints.php');

            if (count($classes) > 0) {
                $endpointsStruct = [];
                
                foreach ($classes as $idx => $class) {
                    $className = basename($class, '.php');

                    if ($className === 'SwaggerEndpoints') {
                        continue;
                    }

                    $namespace = Utils::makeNamespacePath('App', 'Http', 'SweetApi', $apiName, $className);

                    $endpointsStruct[$idx] = [];
                    $endpointsStruct[$idx]['prefix'] = '';
                    $endpointsStruct[$idx]['controller'] = $className;
                    $endpointsStruct[$idx]['methods'] = [];

                    $reflectionClass = new ReflectionClass('\\' . $namespace);

                    foreach ($reflectionClass->getAttributes() as $attribute) {
                        $attributeInstance = $attribute->newInstance();

                        if ($attributeInstance instanceof RoutePrefix) {
                            $endpointsStruct[$idx]['prefix'] = $attributeInstance->path;
                        }
                    }

                    foreach ($reflectionClass->getMethods() as $method) {
                        $methodName = $method->getName();
                        $routeVerbs = null;
                        $routePath = null;
                        $routeName = null;

                        foreach ($method->getAttributes() as $attribute) {
                            $attributeInstance = $attribute->newInstance();

                            if ($attributeInstance instanceof Route) {
                                $routeVerbs = strtolower($attributeInstance->verbs);
                                $routePath = $attributeInstance->path;
                                $routeName = $attributeInstance->name;
                                
                                if ($routeVerbs === '*') {
                                    $routeVerbs = 'any';
                                }
                                else {
                                    $routeVerbs = explode('|', $routeVerbs);
                                }
                            }
                        }

                        if (!is_null($routeVerbs)) {
                            $endpointsStruct[$idx]['methods'][] = ['methodName' => $methodName, 'routeVerbs' => $routeVerbs, 'routePath' => $routePath, 'routeName' => $routeName];
                        }
                    }
                }

                $endpointsStruct = array_reverse($endpointsStruct);

                foreach ($endpointsStruct as $idx => $controllerData) {
                    foreach ($controllerData['methods'] as $methodData) {
                        $pathName = '';

                        if (trim($endpointsStruct[$idx]['prefix'], '/') !== '') {
                            $pathName .= '/' . trim($endpointsStruct[$idx]['prefix'], '/') . '/';
                        }

                        $pathName .= trim($methodData['routePath'], ' /');
                        
                        if (is_string($methodData['routeVerbs'])) {
                            $verbs = ['get', 'post', 'put', 'patch', 'options', 'delete'];
                        }
                        else {
                            $verbs = $methodData['routeVerbs'];
                        }

                        $pathItemData = [
                            'summary' => 'endpoint name "' . $methodData['routeName'] . '"',
                            'description' => '',
                        ];

                        foreach ($verbs as $verb) {
                            $pathItemData[$verb] = new Operation([
                                'operationId' => $methodData['routeName'] . '_' . $verb, //### FIXME: Ma se è il nome della rotta???
                                'tags' => [
                                    $endpointsStruct[$idx]['controller']
                                ],
                                'responses' => new Responses([
                                    '200' => New Response([
                                        'content' => 'text/html'
                                    ])
                                ])
                            ]);
                        }

                        $openapi->paths->addPath($pathName, new PathItem($pathItemData));
                    }
                }
            }

            $json = Writer::writeToJson($openapi, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            file_put_contents(Utils::makeFilePath(__DIR__, 'bootstrap', 'swagger.json'), $json);
        }
        catch (Throwable $e) {
            echo $e->getMessage();
        }
    }
}
