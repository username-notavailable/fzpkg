<?php

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeLivewireFormCommand extends GeneratorCommand
{
    protected $signature = 'fz:make:livewire:form { name : Form name } { model : Model name }';

    protected $description = 'Create a new Livewire model form: $fillable and $attributes must be defined into the model';

    protected $type = 'Form';

    private $modelInstance;

    protected function getStub() : string
    {
        return base_path('stubs/fz/livewire/form.stub');
    }

    public function handle(): void
    {
        if ($this->files->missing(base_path('stubs/fz'))) {
            $this->runCommand('fz:install:stubs', [], $this->output);
        }

        if ($this->files->missing(app_path('Models/' . $this->argument('model') . '.php'))) {
            $this->fail('Model '. $this->argument('model') . ' not found.');
        }        
        else {
            $modelClassNamespace = '\App\Models\\' . $this->argument('model');
            $this->modelInstance = new $modelClassNamespace();
        }

        parent::handle();
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Livewire\Forms';
    }

    protected function replaceClass($stub, $name)
    {
        $model_lowercase = strtolower($this->argument('model'));

        $stub = str_replace('{{ model_name }}', $this->argument('model'), $stub);
        $stub = str_replace('{{ model_name_lowercase }}', $model_lowercase, $stub);
        $stub = str_replace('{{ form_name }}', $this->argument('name'), $stub);

        $attributes = $this->modelInstance->attributesToArray();

        $lastAttribute = array_key_last($attributes);
        $strAttributes = '';
        $strAttributesInit = '';
        
        foreach ($attributes as $name => $value) {
            $strAttributes .= 'public $' . $name . ' = ' . (is_string($value) ? "'" . str_replace("'", "\'", $value) . "'" : $value) . ';' . ($name !== $lastAttribute ? "\n\t" : "");
            $strAttributesInit .= '$this->' . $name . ' = $' . $model_lowercase . '->' . $name . ';' . ($name !== $lastAttribute ? "\n\t\t" : "");
        }

        $strAttributesRules = '';
        $fillable = $this->modelInstance->getFillable();
        $countFillable = count($fillable);

        if ($countFillable > 0) {
            $lastFillable = $fillable[$countFillable - 1];

            foreach ($fillable as $name) {
                $strAttributesRules .= "'" . $name . "' => []" . ($name !== $lastFillable ? ",\n\t\t\t" : "");
            }
        }

        $stub = str_replace('{{ attributes }}', $strAttributes, $stub);
        $stub = str_replace('{{ attributes_init }}', $strAttributesInit, $stub);
        $stub = str_replace('{{ attributes_rules_init }}', $strAttributesRules, $stub);

        return $stub;
    }
}

