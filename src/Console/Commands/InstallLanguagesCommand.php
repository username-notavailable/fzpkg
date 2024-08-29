<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Filesystem\Filesystem;

final class InstallUtilsCommand extends BaseCommand
{
    protected $signature = 'fz:install:langs';

    protected $description = 'Install languages';

    public function handle(): void
    {
        $fileSystem = new Filesystem();

        $envFilePath = base_path('.env');

        if ($fileSystem->exists($envFilePath)) {
            $data = $fileSystem->get($envFilePath);

            if (mb_stripos($data, 'FZ_LANGS_INSTALLED') !== false) {
                $fzUtilsAlreadyInstalled = env('FZUTILS_INSTALLED');

                if (is_bool($fzUtilsAlreadyInstalled)) {
                    if ($fzUtilsAlreadyInstalled) {
                        $this->fail('Fz langs already installed');
                    }
                }
                else {
                    $this->fail('FZ_LANGS_INSTALLED into .env must be boolean');
                }
            }
        }
        else {
            $this->fail('.env file not found');
        }

        $fileSystem->ensureDirectoryExists(base_path('lang'));
        $fileSystem->copyDirectory(__DIR__.'/../../../data/utils/lang', base_path('lang'));

        /* --- */

        $targets = [
            'FZ_LANGS_INSTALLED=' => [
                'from' => 'FZ_LANGS_INSTALLED=.*$',
                'to' => 'FZ_LANGS_INSTALLED=true'
            ],
        ];

        $data .= PHP_EOL;

        foreach ($targets as $search => $fromTo) {
            if (mb_stripos($data, $search) !== false) {
                $data = preg_replace('@' . $fromTo['from'] . '@', $fromTo['to'], $data);
    
                if (!is_null($data)) {
                    $fileSystem->put($envFilePath, $data);
                }
            }
            else {
                $data .= (PHP_EOL . $fromTo['to']);
                $fileSystem->put($envFilePath, $data);
            }
        }
    }
}
