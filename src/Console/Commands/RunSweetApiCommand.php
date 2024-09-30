<?php

namespace Fuzzy\Fzpkg\Console\Commands;

use Fuzzy\Fzpkg\Console\Commands\BaseCommand;
use Fuzzy\Fzpkg\Classes\Utils\Utils;
use Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router\{RoutePrefix, Route, BaseMiddleware, Trashed};

use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response as LaravelResponse;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;

use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

use Nyholm\Psr7\Factory\Psr17Factory;

use OpenSwoole\HTTP\Server as OpenSwooleHttpServer;
use OpenSwoole\Server as OpenSwooleServer;
use OpenSwoole\Http\Request as OpenSwooleRequest;
use OpenSwoole\Http\Response as OpenSwooleResponse;
use Ilex\SwoolePsr7\SwooleServerRequestConverter;
use Ilex\SwoolePsr7\SwooleResponseConverter;

use ReflectionClass;
use Throwable;

//### FIXME: Aggiungere altri attributi per le rotte... (where etc...) https://laravel.com/docs/11.x/routing

final class RunSweetApiCommand extends BaseCommand
{
    protected $signature = 'fz:run:sweetapi { apiName : SweetApi Folder (case sensitive) } { --logTz=Europe/Rome : Log timezone } { --disableSwagger : Disable Swagger docs } { --httpCompressionLevel=1 : Default compression level [1-9] } { --workerNum=4 : OpenSwoole worker number } { --taskWorkerNum=4 : OpenSwoole task worker number } { --backlog=128 : OpenSwoole TCP backlog connection number }';

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
            $logTz = $this->option('logTz');
            $host = null;
            $port = null;

            $apiDirectoryPath = app_path('Http/SweetApi/' . $apiName);
            $apiRuntimeDirectoryPath = Utils::makeDirectoryPath($apiDirectoryPath, 'runtime');
            $sweetApiDirectoryPath = Utils::makeDirectoryPath($apiRuntimeDirectoryPath, 'sweetapi');

            $envFilePath = Utils::makeFilePath($apiRuntimeDirectoryPath, '.env');
            $match = [];

            preg_match('@APP_URL=(.+)@m', file_get_contents($envFilePath), $match);

            if (count($match) !== 2) {
                throw new \InvalidArgumentException('Invalid APP_URL in .env');
            }
            else {
                $urlParts = parse_url($match[1]);
                
                $host = $urlParts['scheme'] . '://' . $urlParts['host'];
                $port = isset($urlParts['port']) ? $urlParts['port'] : 8080;
            }
            
            if (!file_exists($apiDirectoryPath)) {
                $this->fail('SweetAPI "' . $this->argument('apiName') . '" not exists (directory "' . $apiDirectoryPath . '" not found)');
            }
            else {
                $this->createRoutesFile($apiName, $apiDirectoryPath, Utils::makeFilePath($apiRuntimeDirectoryPath, 'routes', 'api.php'));
            }

            $builder = require_once Utils::makeFilePath($apiRuntimeDirectoryPath, 'bootstrap', 'builder.php');

            $psr17Factory = new Psr17Factory();
            $serverRequestFactory = new SwooleServerRequestConverter($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

            $server = new OpenSwooleHttpServer($host, $port, OpenSwooleServer::POOL_MODE);

            $server->set([
                'document_root' => Utils::makeDirectoryPath($sweetApiDirectoryPath, 'swagger'),
                'enable_static_handler' => true,
                'http_compression' => true,
                'http_compression_level' => $this->option('httpCompressionLevel'),
                'worker_num' => $this->option('workerNum'),             // The number of worker processes to start
                'task_worker_num' => $this->option('taskWorkerNum'),    // The amount of task workers to start
                'backlog' => $this->option('backlog'),                  // TCP backlog connection number
            ]);

            $server->on('start', function($server) use ($apiName, $host, $port, $logTz) {
                $this->outText("### SweetAPI: $apiName -- running at $host:$port (started at " . (new \DateTime('now', new \DateTimeZone($logTz)))->format('Y-m-d H:i:s') . ") ###\n");
            });

            $server->on('task', function (OpenSwooleServer $server, $task_id, $reactorId, $data) use ($apiName, $builder, $logTz, $serverRequestFactory, $psr17Factory) {
                try
                {
                    $taskLog = "TASK server_worker_id={$server->worker_id}\tonTask: [PID={$server->worker_pid}]: task_id=$task_id";

                    $OpenSwooleRequest = OpenSwooleRequest::Create();

                    $OpenSwooleRequest->parse($data[0]);
                    $psrServerRequest = $serverRequestFactory->createFromSwoole($OpenSwooleRequest);

                    $requestLog = '[' . $apiName . '][' . (new \DateTime('now', new \DateTimeZone($logTz)))->format('Y-m-d H:i:s') . '][' . strtoupper($psrServerRequest->getMethod()) . '] ' . $psrServerRequest->getUri()->getPath();
                    $sended = false;

                    $this->outText($requestLog . ' [' . $taskLog . ']');

                    $app = $builder->create();

                    try 
                    {
                        $symfonyRequest = (new HttpFoundationFactory())->createRequest($psrServerRequest);
                        
                        $symfonyResponse = $app->handle($symfonyRequest);

                        $psrResponse = (new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory))->createResponse($symfonyResponse);

                        (new SwooleResponseConverter(OpenSwooleResponse::create($data[1])))->send($psrResponse);

                        $sended = true;
                    }
                    catch (Throwable $e) {
                        $this->outText($requestLog . ' [' . $taskLog . '][Handle request exception: ' . $e->getMessage() . ']');
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
                            $this->outText($requestLog . ' [' . $taskLog . '][Terminate request exception: ' . $e->getMessage() . ']');
                        }
                    }
                }
                catch (Throwable $e) {
                    $this->outText($requestLog . ' [' . $taskLog . '][Create app exception: ' . $e->getMessage() . ']');
                }
            });

            $server->on('request', function(OpenSwooleRequest $OpenSwooleRequest, OpenSwooleResponse $OpenSwooleResponse) use ($server) {
                $OpenSwooleResponse->detach();
                $server->task([$OpenSwooleRequest->getData(), $OpenSwooleResponse->fd]);
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
