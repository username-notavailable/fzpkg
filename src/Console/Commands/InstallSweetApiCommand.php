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

        $filesystem->chmod($newSweetApiPath . '/bootstrap/cache', 0755);
        $filesystem->chmod($newSweetApiPath . '/.env', 0600);

        if ((new Process(['composer', 'install'], $newSweetApiPath, ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output) {
                $this->output->write($output);
            }) === 0) {
                if ((new Process(['npm', 'install'], $newSweetApiPath, []))
                    ->setTimeout(null)
                    ->run(function ($type, $output) {
                        $this->output->write($output);
                    }) === 0) {
                        if ((new Process(['php', 'artisan', 'key:generate'], $newSweetApiPath, []))
                            ->setTimeout(null)
                            ->run(function ($type, $output) {
                                $this->output->write($output);
                            }) === 0) {
                                if ((new Process(['php', 'artisan', 'migrate'], $newSweetApiPath, []))
                                    ->setTimeout(null)
                                    ->run(function ($type, $output) {
                                        $this->output->write($output);
                                    }) === 0) {
                                        $this->outLabelledSuccess('Fuzzy SweetAPI "' . $apiName . '" installed');
                                }
                                else {
                                    $this->outLabelledWarning('Fuzzy SweetAPI "' . $apiName . '" installed but "php artisan migrate" command failed, run it manually');
                                }
                        }
                        else {
                            $this->outLabelledWarning('Fuzzy SweetAPI "' . $apiName . '" installed but "php artisan key:generate" command failed, run it manually');
                        }
                }
                else {
                    $this->outLabelledWarning('Fuzzy SweetAPI "' . $apiName . '" installed but "npm install" command failed, run it manually');
                }
        }
        else {
            $this->outLabelledWarning('Fuzzy SweetAPI "' . $apiName . '" installed but "composer install" command failed, run it manually');
        }
    }
}
