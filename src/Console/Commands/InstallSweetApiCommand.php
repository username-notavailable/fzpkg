<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Fuzzy\Fzpkg\Console\Commands\BaseCommand;
use Illuminate\Filesystem\Filesystem;

final class InstallSweetApiCommand extends BaseCommand
{
    protected $signature = 'fz:install:sweetapi { apiName : SweetApi Folder (case sensitive) }';

    protected $description = 'Install new Octane/SweetAPI (Octane package REQUIRED)';

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

        $filesystem->copyDirectory(__DIR__ . '/../../../data/sweetapi/runtime', $newSweetApiPath);

        $filesystem->replaceInFile('{{ api_name }}', $apiName, app_path('Http/SweetApi/' . $apiName . '/Endpoints.php'));
        $filesystem->replaceInFile('{{ api_name }}', $apiName, app_path('Http/SweetApi/' . $apiName . '/SwaggerEndpoints.php'));
        $filesystem->replaceInFile('{{ api_name_lowercase }}', strtolower($apiName), app_path('Http/SweetApi/' . $apiName . '/runtime/config/cors.php'));

        foreach (glob($newSweetApiPath . '/runtime/storage/*/*') as $item) {
            $filesystem->chmod($item, 0755);
        }

        $filesystem->chmod($newSweetApiPath . '/runtime/bootstrap/cache', 0755);
        $filesystem->chmod($newSweetApiPath . '/runtime/.env', 0600);

        $filesystem->link(base_path('vendor'), app_path('Http/SweetApi/' . $apiName . '/runtime/vendor'));

        $this->outLabelledSuccess('Fuzzy SweetAPI "' . $apiName . '" installed');
    }
}
