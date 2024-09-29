<?php

namespace Fuzzy\Fzpkg\Console\Commands;

use Fuzzy\Fzpkg\Console\Commands\BaseCommand;
use Fuzzy\Fzpkg\Classes\Utils\Utils;
use Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router\{RoutePrefix, Route, BaseMiddleware, Trashed};

use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response as LaravelResponse;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Support\Facades\View;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\FileViewFinder;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

use Nyholm\Psr7\Factory\Psr17Factory;

use OpenSwoole\HTTP\Server as OpenSwooleHttpServer;
use OpenSwoole\Server as OpenSwooleServer;
use OpenSwoole\Http\Request as OpenSwooleRequest;
use OpenSwoole\Http\Response as OpenSwooleResponse;
use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Ilex\SwoolePsr7\SwooleResponseConverter;

use Fruitcake\Cors\CorsService;

use ReflectionClass;
use Throwable;

//### FIXME: Aggiungere altri attributi per le rotte... (where etc...) https://laravel.com/docs/11.x/routing

final class RunSweetApiCommand extends BaseCommand
{
    protected $signature = 'fz:run:sweetapi { apiName : SweetApi Folder (case sensitive) } { --host=127.0.0.1 : Host address } { --port=8080 : Port number } { --logTz=Europe/Rome : Log timezone } { --disableSwagger : Disable Swagger docs } { --httpCompressionLevel=1 : Default compression level [1-9] } { --workerNum=4 : OpenSwoole worker number } { --taskWorkerNum=4 : OpenSwoole task worker number } { --backlog=128 : OpenSwoole TCP backlog connection number } { --disableCors : Disable CORS }';

    protected $description = 'Avvia un HTTP server SweetAPI';

    public function handle(): void
    {
        $this->enableOutLogs();

        require Utils::makeFilePath(__DIR__, '..', '..', '..', 'vendor', 'autoload.php');

        $exts = get_loaded_extensions();
        $requiredExts = ['raphf', 'openswoole'];

        foreach ($requiredExts as $ext) {
            if (!in_array($ext, $exts)) {
                $this->fail('SweetAPI require "' . implode(', ', $requiredExts) . '" (' . $ext . ' missing)');
            }
        }

        try 
        {
            $apiName = $this->argument('apiName');
            $host = $this->option('host');
            $port = $this->option('port');
            $logTz = $this->option('logTz');
            $allowCors = !$this->option('disableCors');

            $apiDirectoryPath = app_path('Http/SweetApi/' . $apiName);
            $apiBootstrapDirectoryPath = Utils::makeFilePath($apiDirectoryPath, 'bootstrap');
            $apiViewDirecoryPath = Utils::makeFilePath($apiDirectoryPath, 'views');

            if (!file_exists($apiDirectoryPath)) {
                $this->fail('SweetAPI "' . $this->argument('apiName') . '" not exists (directory "' . $apiDirectoryPath . '" not found)');
            }
            else {
                $this->createRoutesFile($apiName, $apiDirectoryPath, Utils::makeFilePath($apiBootstrapDirectoryPath, 'routes.php'));
            }

            $builder = require_once Utils::makeFilePath($apiBootstrapDirectoryPath, 'builder.php');
            $apiCorsConfig = require_once Utils::makeFilePath($apiBootstrapDirectoryPath, 'cors.php');

            $psr17Factory = new Psr17Factory();
            $serverRequestFactory = new SwooleServerRequestConverter($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

            $server = new OpenSwooleHttpServer($host, $port, OpenSwooleServer::POOL_MODE);

            $server->set([
                'document_root' => Utils::makeDirectoryPath($apiBootstrapDirectoryPath, 'swagger'),
                'enable_static_handler' => true,
                'http_compression' => true,
                'http_compression_level' => $this->option('httpCompressionLevel'),
                'worker_num' => $this->option('workerNum'),             // The number of worker processes to start
                'task_worker_num' => $this->option('taskWorkerNum'),    // The amount of task workers to start
                'backlog' => $this->option('backlog'),                  // TCP backlog connection number
            ]);

            $server->on('start', function($server) use ($apiName, $host, $port, $logTz) {
                $this->outText("### SweetAPI: $apiName -- running at http://$host:$port (started at " . (new \DateTime('now', new \DateTimeZone($logTz)))->format('Y-m-d H:i:s') . ") ###\n");
            });

            $server->on('task', function (OpenSwooleServer $server, $task_id, $reactorId, $data) {
                //echo "Task Worker Process received data";

                //echo "#{$server->worker_id}\tonTask: [PID={$server->worker_pid}]: task_id=$task_id, data_len=" . strlen($data) . "." . PHP_EOL;

                //$server->finish($data);
            });

            /*$server->on('finish', function (OpenSwooleServer $server, $task_id, $data) {
                echo "Task#$task_id finished, data_len=" . strlen($data) . PHP_EOL;
            });*/

            $server->on('request', function(OpenSwooleRequest $OpenSwooleRequest, OpenSwooleResponse $OpenSwooleResponse) use ($apiName, $builder, $apiBootstrapDirectoryPath, $apiViewDirecoryPath, $allowCors, $apiCorsConfig, $logTz, $serverRequestFactory, $psr17Factory) {
                $requestLog = '[' . $apiName . '][' . (new \DateTime('now', new \DateTimeZone($logTz)))->format('Y-m-d H:i:s') . '][' . strtoupper($OpenSwooleRequest->server['request_method']) . '] ' . $OpenSwooleRequest->server['request_uri'];

                $this->outText($requestLog);
                $sended = false;

                try 
                {
                    $app = $builder->create();
                    $app->useEnvironmentPath($apiBootstrapDirectoryPath);
                    
                    View::setFinder(new FileViewFinder(new Filesystem(), [$apiViewDirecoryPath]));

                    $psrServerRequest = $serverRequestFactory->createFromSwoole($OpenSwooleRequest);
                
                    $httpFoundationFactory = new HttpFoundationFactory();
                    $symfonyRequest = $httpFoundationFactory->createRequest($psrServerRequest);

                    $cors = new CorsService($apiCorsConfig);

                    if ($cors->isCorsRequest($symfonyRequest)) {
                        if ($allowCors) {
                            if ($cors->isPreflightRequest($symfonyRequest)) {
                                $symfonyResponse = $cors->handlePreflightRequest($symfonyRequest);

                                $cors->varyHeader($symfonyResponse, 'Access-Control-Request-Method');
                            }
                            else {
                                if ($cors->isOriginAllowed($symfonyRequest)) {
                                    $symfonyResponse = $app->handle($symfonyRequest);

                                    if ($symfonyRequest->getMethod() === 'OPTIONS') {
                                        $cors->varyHeader($symfonyResponse, 'Access-Control-Request-Method');
                                    }

                                    if (!$symfonyResponse->headers->has('Access-Control-Allow-Origin')) {
                                        $symfonyResponse = $cors->addActualRequestHeaders($symfonyResponse, $symfonyRequest);
                                    }
                                }
                                else {
                                    $symfonyResponse = new Response(null, 403);
                                }
                            }
                        }
                        else {
                            $symfonyResponse = new Response(null, 403);
                        }
                    }
                    else {
                        $symfonyResponse = $app->handle($symfonyRequest);
                    }

                    $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
                            
                    $psrResponse = $psrHttpFactory->createResponse($symfonyResponse);

                    $converter = new SwooleResponseConverter($OpenSwooleResponse);
                    $converter->send($psrResponse);

                    $sended = true;
                }
                catch (Throwable $e) {
                    $this->outText($requestLog . ' [Handle request exception: ' . $e->getMessage() . ']');
                }

                if ($sended) {
                    try 
                    {
                        $kernel = $app->make(HttpKernelContract::class);

                        $laravelRequest = LaravelRequest::createFromBase($symfonyRequest);
                        $laravelResponse = new LaravelResponse($symfonyResponse->getContent(), $symfonyResponse->getStatusCode(), $symfonyResponse->headers->all());

                        $kernel->terminate($laravelRequest, $laravelResponse);
                    }
                    catch (Throwable $e) {
                        $this->outText($requestLog . ' [Terminate request exception: ' . $e->getMessage() . ']');
                    }
                }
            });

            $server->start();
        }
        catch(Throwable $e) {
            $this->outLabelledError('SweetAPI "' . $this->argument('apiName') . '" Init failed: ' . $e->getMessage());

            throw $e;
        }
    }

    protected function createRoutesFile(string $apiName, string $apiDirectoryPath, string $apiRoutesFilename) 
    {
        $classes = glob($apiDirectoryPath . DIRECTORY_SEPARATOR . '?*Endpoints.php');

        file_put_contents($apiRoutesFilename, '');

        if (count($classes) > 0) {
            $endpointsStruct = [];
            $uses = [];

            foreach ($classes as $idx => $class) {
                $className = basename($class, '.php');

                if ($className === 'SwaggerEndpoints' && $this->option('disableSwagger')) {
                    continue;
                }

                $endpointsStruct[$idx] = [];
                $endpointsStruct[$idx]['use'] = Utils::makeNamespacePath('App', 'Http', 'SweetApi', $apiName, $className);
                $endpointsStruct[$idx]['prefix'] = '';
                $endpointsStruct[$idx]['middleware'] = ['add' => [], 'exclude' => []];
                $endpointsStruct[$idx]['controller'] = $className;
                $endpointsStruct[$idx]['methods'] = [];

                $reflectionClass = new ReflectionClass('\\' . $endpointsStruct[$idx]['use']);

                foreach ($reflectionClass->getAttributes() as $attribute) {
                    $attributeInstance = $attribute->newInstance();

                    if ($attributeInstance instanceof RoutePrefix) {
                        $endpointsStruct[$idx]['prefix'] = $attributeInstance->path;
                    }
                    else if ($attributeInstance instanceof BaseMiddleware) {
                        $name = $attributeInstance->getName();

                        if (!empty($name)) {
                            $endpointsStruct[$idx]['middleware'][($attributeInstance->exclude ? 'exclude' : 'add')][] = $name;
                        }
                    }
                }

                foreach ($reflectionClass->getMethods() as $method) {
                    $methodName = $method->getName();

                    $routeVerbs = null;
                    $routePath = null;
                    $routeName = null;
                    $routeMiddleware = ['add' => [], 'exclude' => []];
                    $withTrashed = false;

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
                                $parts = explode('|', $routeVerbs);

                                if (count($parts) > 1) {
                                    $routeVerbs = array_map(function($item) {return "'$item'"; }, $parts);
                                }
                                else {
                                    $routeVerbs = $parts;
                                }
                            }
                        }
                        else if ($attributeInstance instanceof BaseMiddleware) {
                            $name = $attributeInstance->getName();

                            if (!empty($name)) {
                                $routeMiddleware[($attributeInstance->exclude ? 'exclude' : 'add')][] = $name;
                            }
                        }
                        else if ($attributeInstance instanceof Trashed) {
                            $withTrashed = true;
                        }
                    }

                    if (!is_null($routeVerbs)) {
                        $endpointsStruct[$idx]['methods'][] = ['methodName' => $methodName, 'routeVerbs' => $routeVerbs, 'routePath' => $routePath, 'routeName' => $routeName, 'middleware' => $routeMiddleware, 'withTrashed' => $withTrashed];
                    }
                }
            }

            file_put_contents($apiRoutesFilename, "<?php\n\n", FILE_APPEND);
            file_put_contents($apiRoutesFilename, "use Illuminate\Support\Facades\Route;\n", FILE_APPEND);

            $endpointsStruct = array_reverse($endpointsStruct);
            
            foreach ($endpointsStruct as $idx => $data) {
                file_put_contents($apiRoutesFilename, "use " . $data['use'] . ";\n", FILE_APPEND);
            }

            foreach ($uses as $use) {
                file_put_contents($apiRoutesFilename, $use . "\n", FILE_APPEND);
            }

            file_put_contents($apiRoutesFilename, "\n", FILE_APPEND);

            foreach ($endpointsStruct as $idx => $controllerData) {
                $item = '';
                $tCount = 0;

                if (count($controllerData['middleware']['add']) > 0) {
                    $item .= str_repeat("\t", $tCount++) . "Route::middleware([" . implode(',', $controllerData['middleware']['add']) . "])->group(function () {\n";
                }

                if (count($controllerData['middleware']['exclude']) > 0) {
                    $item .= str_repeat("\t", $tCount++) . "Route::withoutMiddleware([" . implode(',', $controllerData['middleware']['exclude']) . "])->group(function () {\n";
                }

                $item .= str_repeat("\t", $tCount++) . "Route::prefix('" . $controllerData['prefix'] . "')->group(function () {\n";
                $item .= str_repeat("\t", $tCount++) . "Route::controller(" . $controllerData['controller'] . "::class)->group(function () {\n";

                foreach ($controllerData['methods'] as $idx => $methodData) {
                    if (is_string($methodData['routeVerbs'])) {
                        $item .= str_repeat("\t", $tCount) . "Route::any('" . $methodData['routePath'] . "', '" . $methodData['methodName'] . "')";
                    }
                    else if (count($methodData['routeVerbs']) === 1) {
                        $item .= str_repeat("\t", $tCount) . "Route::" . $methodData['routeVerbs'][0] . "('" . $methodData['routePath'] . "', '" . $methodData['methodName'] . "')";
                    }
                    else { // count($methodData['routeVerbs']) > 1
                        $item .= str_repeat("\t", $tCount) . "Route::match([" . implode(',', $methodData['routeVerbs']) . "], '" . $methodData['routePath'] . "', '" . $methodData['methodName'] . "')";
                    }

                    if ($methodData['routeName'] !== '') {
                        $item .= "->name('" . $methodData['routeName'] . "')";
                    } 

                    if (count($methodData['middleware']['add']) > 0) {
                        $item .= "->middleware([" . implode(',', $methodData['middleware']['add']) . "])";
                    }

                    if (count($methodData['middleware']['exclude']) > 0) {
                        $item .= "->withoutMiddleware([" . implode(',', $methodData['middleware']['exclude']) . "])";
                    }

                    if ($methodData['withTrashed']) {
                        $item .= "->withTrashed()";
                    }

                    $item .= ";\n";
                }

                $item .= str_repeat("\t", --$tCount) . "});\n";
                $item .= str_repeat("\t", --$tCount) . "});\n";

                if (count($controllerData['middleware']['exclude']) > 0) {
                    $item .= str_repeat("\t", --$tCount) . "});\n";
                }

                if (count($controllerData['middleware']['add']) > 0) {
                    $item .= str_repeat("\t", --$tCount) . "});\n";
                }

                $item .= "\n";

                file_put_contents($apiRoutesFilename, $item, FILE_APPEND);
            }
        }
    }
}
