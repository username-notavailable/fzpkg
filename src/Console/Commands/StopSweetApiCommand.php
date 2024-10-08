<?php

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Env;
use Dotenv\Dotenv;
use Laravel\Octane\Commands\StopCommand;

final class StopSweetApiCommand extends StopCommand
{
    public $description = 'Stop the Octane/SweetAPI server';

    public function __construct()
    {
        if (!defined('RUN_ARTISAN_FROM_SWEET_API_DIR')) {
            $signature = str_replace('octane:stop', 'fz:sweetapi:stop { apiName : SweetApi Folder (case sensitive) }', $this->signature);
        }
        else {
            $signature = str_replace('octane:stop', 'fz:sweetapi:stop', $this->signature);
        }

        $signature = preg_replace('@{--server=[^}]+}@', '', $signature);

        $this->signature = $signature;

        parent::__construct();
    }

    public function handle(): void
    {
        if (!defined('RUN_ARTISAN_FROM_SWEET_API_DIR')) {
            $apiName = $this->argument('apiName');
            $apiDirectoryPath = base_path('sweets/' . $apiName);

            if (!file_exists($apiDirectoryPath)) {
                $this->fail('SweetAPI "' . $apiName . '" not exists (directory "' . $apiDirectoryPath . '" not found)');
            }

            app()->setBasePath($apiDirectoryPath);
            app()->useEnvironmentPath($apiDirectoryPath);

            Dotenv::create(Env::getRepository(), app()->environmentPath(), app()->environmentFile())->load();

            (new LoadConfiguration())->bootstrap(app());
        }
        
        $server = config('octane.server');

        $this->getDefinition()->addOption(new InputOption('--server', null, InputOption::VALUE_REQUIRED, $server));

        parent::handle();
    }
}
