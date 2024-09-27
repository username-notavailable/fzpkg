<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Fuzzy\Fzpkg\Console\Commands\BaseCommand;
use Illuminate\Filesystem\Filesystem;

final class InstallSweetApiCommand extends BaseCommand
{
    protected $signature = 'fz:install:sweetapi { apiName : SweetApi Folder (case sensitive) } { --copyenv : Copy laravel application .env }';

    protected $description = 'Install new SweetAPI';

    public function handle(): void
    {
        $filesystem = new Filesystem();

        $apiName = $this->argument('apiName');
        $sweetApiPath = app_path('Http/SweetApi');
        $newSweetApiPath = app_path('Http/SweetApi/' . $apiName);

        $filesystem->ensureDirectoryExists($sweetApiPath);

        if ($filesystem->exists($newSweetApiPath)) {
            $this->fail('SweetAPI "' . $apiName . '" already exists');
        }

        $filesystem->copyDirectory(__DIR__.'/../../../data/sweetapi', $newSweetApiPath);

        if ($this->option('copyenv')) {
            $filesystem->copy(base_path('.env'), app_path('Http/SweetApi/' . $apiName . '/bootstrap/.env'));
        }

        $filesystem->replaceInFile('{{ api_name }}', $apiName, app_path('Http/SweetApi/' . $apiName . '/Endpoints.php'));
        $filesystem->replaceInFile('{{ api_name }}', $apiName, app_path('Http/SweetApi/' . $apiName . '/SwaggerEndpoints.php'));

        $this->outLabelledSuccess('Fuzzy SweetAPI "' . $apiName . '" installed');
    }
}
