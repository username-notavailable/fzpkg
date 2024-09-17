<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Filesystem\Filesystem;

final class InstallStubsCommand extends BaseCommand
{
    protected $signature = 'fz:install:stubs';

    protected $description = 'Publish fz stubs';

    public function handle(): void
    {
        $fileSystem = new Filesystem();

        $this->checkEnvFlag('FZ_STUBS_INSTALLED', 'Fz stubs already installed');

        $fileSystem->ensureDirectoryExists(base_path('stubs'));
        $fileSystem->copyDirectory(__DIR__.'/../../../data/stubs', base_path('stubs'));

        /* --- */

        $targets = [
            'FZ_STUBS_INSTALLED=' => [
                'from' => 'FZ_STUBS_INSTALLED=.*$',
                'to' => 'FZ_STUBS_INSTALLED=true'
            ],
        ];

        $this->updateEnvFileOrAppend($targets);
    }
}
