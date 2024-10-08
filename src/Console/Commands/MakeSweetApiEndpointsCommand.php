<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;

final class MakeSweetApiEndpointsCommand extends GeneratorCommand
{
    protected $description = 'Create a new SweetAPI Endpoints';

    protected $type = 'SweetApi endpoints';

    public function __construct(Filesystem $files)
    {
        if (!defined('RUN_ARTISAN_FROM_SWEET_API_DIR')) {
            $signature = 'fz:make:sweetapi:endpoints { name : Endpoints name } { apiName : SweetApi Folder (case sensitive) } { --type=Json : Type of Request/Response [ Json | Htmx ] }';
        }
        else {
            $signature = 'fz:make:sweetapi:endpoints { name : Endpoints name } { --type=Json : Type of Request/Response [ Json | Htmx ] }';
        }

        $this->signature = $signature;

        parent::__construct($files);
    }

    protected function getStub() : string
    {
        return base_path('stubs/fz/sweetapi/new-endpoints.stub');
    }

    protected function getPath($name): string
    {
        if (!defined('RUN_ARTISAN_FROM_SWEET_API_DIR')) {
            return base_path('sweets/' . $this->argument('apiName') . '/app/Http/Endpoints/' . $this->argument('name') . 'Endpoints.php');
        }
        else {
            return app_path('Http/Endpoints/' . $this->argument('name') . 'Endpoints.php');   
        }
    }

    public function handle(): void
    {
        if (!defined('RUN_ARTISAN_FROM_SWEET_API_DIR')) {
            $apiName = $this->argument('apiName');
            
            if (!$this->files->exists(base_path('sweets/' . $apiName))) {
                $this->fail('SweetAPI "' . $this->argument('apiName') . '" not found');
            }

            $endpointsPath = base_path('sweets/' . $this->argument('apiName') . '/app/Http/Endpoints/' . $this->argument('name') . 'Endpoints.php');
        }
        else {
            $apiName = RUN_ARTISAN_FROM_SWEET_API_DIR;
            $endpointsPath = app_path('Http/Endpoints/' . $this->argument('name') . 'Endpoints.php'); 
        }

        if ($this->files->exists($endpointsPath)) {
            $this->fail('Endpoints "' . $this->argument('name') . '" already exists into SweetAPI "' . $apiName . '"');
        }

        if (!in_array($this->option('type'), ['Json', 'Htmx'])) {
            $this->fail('SweetAPI Invalid type (' . $this->option('type') . ')');
        }

        if ($this->files->missing(base_path('stubs/fz'))) {
            $this->runCommand('fz:install:stubs', [], $this->output);
        }

        parent::handle();
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return 'App\Http\Endpoints';
    }

    protected function replaceClass($stub, $name)
    {
        $stub = str_replace('{{ class_name_lowercase }}', strtolower($this->argument('name')), $stub);

        if ($this->option('type') === 'Json') {
            $requestType = 'JsonRequest';
            $responseType = 'JsonResponse';
        }
        else {
            $requestType = 'HtmxRequest';
            $responseType = 'HtmxResponse';
        }

        $stub = str_replace('{{ request_type }}', $requestType, $stub);
        $stub = str_replace('{{ response_type }}', $responseType, $stub);

        return str_replace('{{ class_name }}', $this->argument('name'), $stub);
    }
}
