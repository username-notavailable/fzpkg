<?php

namespace Fuzzy\Fzpkg\Console\Commands;

use Fuzzy\Fzpkg\Classes\Utils\Utils;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Env;
use Dotenv\Dotenv;
use Laravel\Octane\Commands\StatusCommand;

final class StatusSweetApiCommand extends StatusCommand
{
    public $description = 'Get the current status of the Octane/SweetAPI server (Octane package REQUIRED)';

    public function __construct()
    {
        $signature = str_replace('octane:status', 'fz:sweetapi:status { apiName : SweetApi Folder (case sensitive) }', $this->signature);
        $signature = preg_replace('@{--server=[^}]+}@', '', $signature);

        $this->signature = $signature;

        parent::__construct();
    }

    public function handle(): void
    {
        $apiName = $this->argument('apiName');
        $apiDirectoryPath = app_path('Http/SweetApi/' . $apiName);
        $apiRuntimeDirectoryPath = Utils::makeDirectoryPath($apiDirectoryPath, 'runtime');

        if (!file_exists($apiDirectoryPath)) {
            $this->fail('SweetAPI "' . $this->argument('apiName') . '" not exists (directory "' . $apiDirectoryPath . '" not found)');
        }

        app()->setBasePath($apiRuntimeDirectoryPath);
        app()->useEnvironmentPath($apiRuntimeDirectoryPath);

        Dotenv::create(Env::getRepository(), app()->environmentPath(), app()->environmentFile())->load();

        (new LoadConfiguration())->bootstrap(app());

        $server = config('octane.server');

        $this->getDefinition()->addOption(new InputOption('--server', null, InputOption::VALUE_REQUIRED, $server));

        parent::handle();
    }
}
