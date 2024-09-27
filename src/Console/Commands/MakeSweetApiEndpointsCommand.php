<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;

final class MakeSweetApiEndpointsCommand extends GeneratorCommand
{
    protected $signature = 'fz:make:sweetapi:endpoints { name : Endpoints name } { apiName : SweetApi Folder (case sensitive) }';

    protected $description = 'Create a new SweetAPI Endpoints';

    protected $type = 'SweetApi endpoints';

    protected function getStub() : string
    {
        return base_path('stubs/fz/sweetapi/new-endpoints.stub');
    }

    protected function getPath($name): string
    {
        return app_path('Http/SweetApi/' . $this->argument('apiName') . '/' . $this->argument('name') . 'Endpoints.php');
    }

    public function handle(): void
    {
        if (!$this->files->exists(app_path('Http/SweetApi/' . $this->argument('apiName')))) {
            $this->fail('SweetAPI "' . $this->argument('apiName') . '" not found');
        }

        if ($this->files->exists(app_path('Http/SweetApi/' . $this->argument('apiName') . '/' . $this->argument('name') . 'Endpoints.php'))) {
            $this->fail('Endpoints "' . $this->argument('name') . '" already exists into SweetAPI "' . $this->argument('apiName') . '"');
        }

        if ($this->files->missing(base_path('stubs/fz'))) {
            $this->runCommand('fz:install:stubs', [], $this->output);
        }

        parent::handle();
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Http\SweetApi\\' . $this->argument('apiName');
    }

    protected function replaceClass($stub, $name)
    {
        $stub = str_replace('{{ api_name }}', $this->argument('apiName'), $stub);
        $stub = str_replace('{{ class_name_lowercase }}', strtolower($this->argument('name')), $stub);

        return str_replace('{{ class_name }}', $this->argument('name'), $stub);
    }
}
