<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Filesystem\Filesystem;

final class InstallMiddlewaresCommand extends BaseCommand
{
    protected $signature = 'fz:install:middlewares';

    protected $description = 'Install fz middlewares';

    public function handle(): void
    {
        $filesystem = new Filesystem();

        $this->checkEnvFlag('FZ_MIDDLEWARES_INSTALLED', 'Fz stubs already installed');

        $filesystem->ensureDirectoryExists(app_path('Http/Middlewares'));
        $filesystem->copyDirectory(__DIR__.'/../../../data/middlewares', app_path('Http/Middlewares'));

        /* --- */

        $targets = [
            'FZ_MIDDLEWARES_INSTALLED=' => [
                'from' => 'FZ_MIDDLEWARES_INSTALLED=.*$',
                'to' => 'FZ_MIDDLEWARES_INSTALLED=true'
            ],
        ];

        $this->updateEnvFileOrAppend($targets);

        $this->outLabelledSuccess('Fuzzy middlewares installed');
    }
}
