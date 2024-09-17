<?php

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Illuminate\Foundation\Inspiring;

class MakeVoltComponentCommand extends GeneratorCommand
{
    protected $signature = 'fz:make:volt:component {name : Component name --class : Create a class based component }';

    protected $description = 'Create a new Volt component';

    protected $type = 'Volt Component';

    protected function getStub()
    {
        if ($this->option('class')) {
            return base_path('stubs/fz/volt/class-component.stub');
        }
        else {
            return base_path('stubs/fz/volt/functional-component.stub');
        }
    }

    protected function getPath($name): string
    {
        $paths = Volt::paths();

        $mountPath = isset($paths[0]) ? $paths[0]->path : config('livewire.view_path', resource_path('views/livewire'));

        return $mountPath.'/'.Str::lower(Str::finish($this->argument('name'), '.blade.php'));
    }

    public function handle(): void
    {
        if ($this->files->missing(base_path('stubs/fz'))) {
            $this->runCommand('fz:install:stubs', [], $this->output);
        }

        parent::handle();
    }

    protected function replaceClass($stub, $name)
    {
        if ($this->option('class')) {
            return str_replace('{{ inspire_quote }}', Inspiring::quotes()->random(), $stub);
        }
        else {
            return $stub;
        }
    }
}

