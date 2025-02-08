<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Fuzzy\Fzpkg\Console\Commands\BaseCommand;
use Fuzzy\Fzpkg\Classes\Utils\Utils;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class InstallSweetApiCommand extends BaseCommand
{
    protected $signature = 'fz:install:sweetapi { apiName : SweetApi Folder (case sensitive) }';

    protected $description = 'Install new SweetAPI directory';

    public function handle(): void
    {
        if (defined('RUN_ARTISAN_FROM_SWEET_API_DIR')) {
            $this->fail('Command unavailable from inside a SweetAPI directory');
        }

        $filesystem = new Filesystem();

        $apiName = $this->argument('apiName');
        $sweetApiPath = base_path('sweets');
        $newSweetApiPath = base_path('sweets/' . $apiName);

        $filesystem->ensureDirectoryExists($sweetApiPath);

        if ($filesystem->exists($newSweetApiPath)) {
            $this->fail('SweetAPI "' . $apiName . '" already exists');
        }

        $filesystem->copyDirectory(__DIR__ . '/../../../data/sweetapi/runtime', $newSweetApiPath);

        foreach (glob($newSweetApiPath . '/storage/*/*') as $item) {
             $filesystem->chmod($item, 0755);
        }

        $filesystem->move($newSweetApiPath . '/.env.default', $newSweetApiPath . '/.env');

        $filesystem->chmod($newSweetApiPath . '/bootstrap/cache', 0755);
        $filesystem->chmod($newSweetApiPath . '/.env', 0600);

        $commands = [];
        
        $commands[] = ['cmd' => ['composer', 'install'], 'env' => ['COMPOSER_MEMORY_LIMIT' => '-1']];
        $commands[] = ['cmd' => ['npm', 'install'], 'env' => []];
        $commands[] = ['cmd' => ['php', 'artisan', 'config:clear'], 'env' => []];
        $commands[] = ['cmd' => ['php', 'artisan', 'config:cache'], 'env' => []];
        $commands[] = ['cmd' => ['php', 'artisan', 'key:generate'], 'env' => []];
        $commands[] = ['cmd' => ['php', 'artisan', 'migrate'], 'env' => []];

        $done = true;

        for ($i = 0; $i < count($commands); $i++) {
            if ((new Process($commands[$i]['cmd'], $newSweetApiPath, $commands[$i]['env']))
                ->setTimeout(null)
                ->run(function ($type, $output) {
                    $this->output->write($output);
                }) !== 0) {
                    $done = false;

                    $this->outLabelledWarning('Fuzzy SweetAPI "' . $apiName . '" installed but init commands failed, run those manually...');

                    for ($i2 = $i; $i2 < count($commands); $i2++) {
                        echo implode(' ', $commands[$i2]['cmd']) . PHP_EOL;
                    }

                    echo PHP_EOL;
                    break;
            }
        }

        if ($done) {
            $this->outLabelledSuccess('Fuzzy SweetAPI "' . $apiName . '" installed');
        }
    }
}
